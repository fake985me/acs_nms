// src/index.mjs - Enhanced ACS server with database integration
// This is the enhanced version with full database support and REST API

import express from 'express';
import bodyParser from 'body-parser';
import cors from 'cors';
import crypto from 'crypto';

import {
  parseCwmpXml,
  buildInformResponse,
  buildCwmpEnvelope,
} from './cwmpUtils.js';

import {
  buildRpcFromTask,
  buildRebootRpc,
  buildSetParameterValuesRpc,
  buildGetParameterValuesRpc,
  buildGetParameterNamesRpc,
  buildDownloadRpc,
  buildFactoryResetRpc,
} from './rpcs.js';

import {
  upsertDevice,
  getDevice,
  getDevices,
  updateDeviceTags,
  upsertSession,
  findSessionByIp,
  upsertParameters,
  getParameters,
  createTask,
  getNextPendingTask,
  getTasks,
  updateTaskStatus,
  markLastSentTaskDone,
} from './database.js';

const app = express();
const PORT = process.env.ACS_PORT || 7547;

// Logger
app.use((req, res, next) => {
  console.log(`[${new Date().toISOString()}] ${req.method} ${req.url}`);
  next();
});

// Body parsers
app.use(
  bodyParser.text({
    type: ['text/xml', 'application/xml', '*/xml'],
  })
);
app.use(express.json());
app.use(cors());

/* ==================== HELPER FUNCTIONS ==================== */

function extractDeviceIdFromInform(method) {
  try {
    const deviceId = method.DeviceId || method.DeviceID;
    const serial = deviceId.SerialNumber || '';
    const oui = deviceId.OUI || '';
    const product = deviceId.ProductClass || '';
    const manufacturer = deviceId.Manufacturer || '';

    const uniqueId = `${oui}-${product}-${serial}`;
    return {
      id: uniqueId,
      serial,
      oui,
      product,
      manufacturer,
    };
  } catch (e) {
    console.error('Failed to extract DeviceId from Inform:', e);
    return null;
  }
}

function extractParametersFromInform(rpc) {
  const paramList = rpc?.ParameterList?.ParameterValueStruct || [];
  const arr = Array.isArray(paramList) ? paramList : [paramList];

  const parameters = [];
  const deviceInfo = {};

  for (const p of arr) {
    const name = p.Name;
    let value = p.Value;

    if (!name) continue;

    // Extract type if available
    let type = 'xsd:string';
    if (typeof value === 'object' && value !== null) {
      type = (value.$ && value.$['xsi:type']) || 'xsd:string';
      value = value._;
    }

    parameters.push({ name, value, type });

    // Extract device info from parameters
    if (name.includes('DeviceInfo.Manufacturer')) {
      deviceInfo.manufacturer = value;
    } else if (name.includes('DeviceInfo.ModelName')) {
      deviceInfo.modelName = value;
    } else if (name.includes('DeviceInfo.SoftwareVersion')) {
      deviceInfo.softwareVersion = value;
    }
  }

  return { parameters, deviceInfo };
}

function normalizeIp(ip) {
  if (!ip) return ip;
  if (ip.startsWith('::ffff:')) return ip.replace('::ffff:', '');
  return ip;
}

/* ==================== HEALTH & BASIC ROUTES ==================== */

app.get('/health', async (req, res) => {
  const devices = await getDevices();
  return res.json({
    status: 'ok',
    devices: devices.length,
    timestamp: new Date().toISOString(),
  });
});

app.get(['/acs', '/'], (req, res) => {
  return res.status(200).send('ACS OK (HTTP GET)');
});

/* ==================== TR-069 SOAP ENDPOINT ==================== */

app.post(['/acs', '/'], async (req, res) => {
  const xml = req.body;
  const remoteIp = normalizeIp(req.ip || req.connection?.remoteAddress || '');

  // Empty POST → CPE polling for tasks
  if (!xml || typeof xml !== 'string' || !xml.trim()) {
    console.log('Empty POST /acs → CPE polling for tasks');
    await handleRequestForTask(remoteIp, null, null, null, res);
    return;
  }

  console.log('\\n=== Incoming CWMP ===');
  console.log(xml.substring(0, 500) + '...'); // Log first 500 chars

  try {
    const parsed = await parseCwmpXml(xml);
    const { header, methodName, method } = parsed;

    const headerId = header?.['cwmp:ID']?._ || header?.ID?._ || null;
    console.log('Method:', methodName, 'Header ID:', headerId, 'From:', remoteIp);

    switch (methodName) {
      case 'cwmp:Inform':
      case 'Inform':
        await handleInform(method, headerId, remoteIp, res);
        break;

      case 'cwmp:TransferComplete':
      case 'TransferComplete':
        await handleTransferComplete(remoteIp, method, res);
        break;

      case 'cwmp:GetParameterValuesResponse':
      case 'GetParameterValuesResponse':
        await handleGetParameterValuesResponse(remoteIp, method, res);
        break;

      case 'cwmp:SetParameterValuesResponse':
      case 'SetParameterValuesResponse':
        await handleSetParameterValuesResponse(remoteIp, res);
        break;

      case 'cwmp:RebootResponse':
      case 'RebootResponse':
        await handleRebootResponse(remoteIp, res);
        break;

      case 'cwmp:GetParameterNamesResponse':
      case 'GetParameterNamesResponse':
        await handleGetParameterNamesResponse(remoteIp, method, res);
        break;

      case 'cwmp:Fault':
      case 'Fault':
        console.log('Fault from CPE:', JSON.stringify(method, null, 2));
        return res.status(204).end();

      default:
        console.log('Unknown or empty method, CPE polling for tasks');
        await handleRequestForTask(remoteIp, methodName, method, headerId, res);
        break;
    }
  } catch (e) {
    console.error('Error parsing CWMP:', e);
    return res.status(500).send('Invalid CWMP');
  }
});

/* ==================== CWMP HANDLERS ==================== */

async function handleInform(method, headerId, remoteIp, res) {
  const deviceInfo = extractDeviceIdFromInform(method);

  if (!deviceInfo) {
    console.log('Cannot extract DeviceId');
    const respXml = buildInformResponse(headerId);
    res.set('Content-Type', 'text/xml; charset="utf-8"');
    return res.status(200).send(respXml);
  }

  console.log('Inform from:', deviceInfo);

  // Extract parameters and device info from Inform
  const { parameters, deviceInfo: extractedInfo } = extractParametersFromInform(method);

  // Upsert device
  try {
    await upsertDevice({
      deviceId: deviceInfo.id,
      oui: deviceInfo.oui,
      productClass: deviceInfo.product,
      serialNumber: deviceInfo.serial,
      manufacturer: extractedInfo.manufacturer || deviceInfo.manufacturer,
      modelName: extractedInfo.modelName,
      softwareVersion: extractedInfo.softwareVersion,
      ipAddress: remoteIp,
    });

    // Update session
    await upsertSession(deviceInfo.id, remoteIp, headerId);

    // Store parameters
    if (parameters.length > 0) {
      await upsertParameters(deviceInfo.id, parameters);
    }

    console.log(`✓ Device ${deviceInfo.id} updated with ${parameters.length} parameters`);
  } catch (e) {
    console.error('DB error in Inform:', e.message);
  }

  // Send InformResponse
  const respXml = buildInformResponse(headerId);
  console.log('\\n=== Sending InformResponse ===');

  res.set('Content-Type', 'text/xml; charset="utf-8"');
  return res.status(200).send(respXml);
}

async function handleRequestForTask(remoteIp, methodName, method, headerId, res) {
  try {
    // Find session by IP
    const session = await findSessionByIp(remoteIp);

    if (!session || !session.device_id) {
      console.log('No session found for IP:', remoteIp);
      return res.status(204).end();
    }

    // Get next pending task
    const task = await getNextPendingTask(session.device_id);

    if (!task) {
      console.log('No pending task for device:', session.device_id);
      return res.status(204).end();
    }

    console.log(`Sending task ${task.type} (ID: ${task.id}) to device ${session.device_id}`);

    // Build RPC from task
    const rpcXml = buildRpcFromTask(task);

    // Mark task as sent
    await updateTaskStatus(task.id, 'sent');

    console.log('\\n=== Sending Task RPC ===');
    console.log(rpcXml.substring(0, 500) + '...');

    res.set('Content-Type', 'text/xml; charset="utf-8"');
    return res.status(200).send(rpcXml);
  } catch (e) {
    console.error('Error handling task request:', e);
    return res.status(204).end();
  }
}

async function handleGetParameterValuesResponse(remoteIp, method, res) {
  try {
    const session = await findSessionByIp(remoteIp);
    if (!session?.device_id) {
      return res.status(204).end();
    }

    // Extract parameters from response
    const { parameters } = extractParametersFromInform(method);

    if (parameters.length > 0) {
      await upsertParameters(session.device_id, parameters);
      console.log(`✓ Updated ${parameters.length} parameters for ${session.device_id}`);
    }

    await markLastSentTaskDone(session.device_id, 'getParameterValues');
  } catch (e) {
    console.error('Error handling GetParameterValuesResponse:', e);
  }

  return res.status(204).end();
}

async function handleSetParameterValuesResponse(remoteIp, res) {
  try {
    const session = await findSessionByIp(remoteIp);
    if (session?.device_id) {
      await markLastSentTaskDone(session.device_id, 'setParameterValues');
      console.log(`✓ SetParameterValues completed for ${session.device_id}`);
    }
  } catch (e) {
    console.error('Error handling SetParameterValuesResponse:', e);
  }

  return res.status(204).end();
}

async function handleRebootResponse(remoteIp, res) {
  try {
    const session = await findSessionByIp(remoteIp);
    if (session?.device_id) {
      await markLastSentTaskDone(session.device_id, 'reboot');
      console.log(`✓ Reboot initiated for ${session.device_id}`);
    }
  } catch (e) {
    console.error('Error handling RebootResponse:', e);
  }

  return res.status(204).end();
}

async function handleTransferComplete(remoteIp, method, res) {
  console.log('TransferComplete from', remoteIp);

  try {
    const session = await findSessionByIp(remoteIp);
    if (session?.device_id) {
      await markLastSentTaskDone(session.device_id, 'download');
      console.log(`✓ Download completed for ${session.device_id}`);
    }
  } catch (e) {
    console.error('Error handling TransferComplete:', e);
  }

  return res.status(204).end();
}

async function handleGetParameterNamesResponse(remoteIp, method, res) {
  console.log('GetParameterNamesResponse from', remoteIp);
  // TODO: Store parameter names in database if needed

  try {
    const session = await findSessionByIp(remoteIp);
    if (session?.device_id) {
      await markLastSentTaskDone(session.device_id, 'getParameterNames');
    }
  } catch (e) {
    console.error('Error handling GetParameterNamesResponse:', e);
  }

  return res.status(204).end();
}

/* ==================== REST API FOR DASHBOARD / LARAVEL ==================== */

// List all devices
app.get('/api/devices', async (req, res) => {
  try {
    const filters = {
      manufacturer: req.query.manufacturer,
      oui: req.query.oui,
      ip: req.query.ip,
      tags: req.query.tags,
    };

    const devices = await getDevices(filters);
    return res.json({ data: devices });
  } catch (e) {
    console.error('Error fetching devices:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Get device details
app.get('/api/devices/:deviceId', async (req, res) => {
  try {
    const device = await getDevice(req.params.deviceId);
    if (!device) {
      return res.status(404).json({ message: 'Device not found' });
    }
    return res.json({ data: device });
  } catch (e) {
    console.error('Error fetching device:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Get device parameters
app.get('/api/devices/:deviceId/parameters', async (req, res) => {
  try {
    const nameFilter = req.query.name;
    const parameters = await getParameters(req.params.deviceId, nameFilter);
    return res.json({ data: parameters });
  } catch (e) {
    console.error('Error fetching parameters:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Get device tasks
app.get('/api/devices/:deviceId/tasks', async (req, res) => {
  try {
    const status = req.query.status;
    const tasks = await getTasks(req.params.deviceId, status);
    return res.json({ data: tasks });
  } catch (e) {
    console.error('Error fetching tasks:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Create reboot task
app.post('/api/devices/:deviceId/tasks/reboot', async (req, res) => {
  try {
    const device = await getDevice(req.params.deviceId);
    if (!device) {
      return res.status(404).json({ message: 'Device not found' });
    }

    const task = await createTask(req.params.deviceId, 'reboot', {});
    return res.json({ message: 'Reboot task queued', data: task });
  } catch (e) {
    console.error('Error creating reboot task:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Create SetParameterValues task
app.post('/api/devices/:deviceId/tasks/set-parameters', async (req, res) => {
  try {
    const device = await getDevice(req.params.deviceId);
    if (!device) {
      return res.status(404).json({ message: 'Device not found' });
    }

    const { parameters, parameterValues } = req.body || {};
    const params = parameterValues || parameters;

    if (!params || typeof params !== 'object') {
      return res.status(400).json({ message: 'parameters object is required' });
    }

    const task = await createTask(req.params.deviceId, 'setParameterValues', {
      parameterValues: params,
    });

    return res.json({ message: 'SetParameterValues task queued', data: task });
  } catch (e) {
    console.error('Error creating SetParameterValues task:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Create GetParameterValues task
app.post('/api/devices/:deviceId/tasks/get-parameters', async (req, res) => {
  try {
    const device = await getDevice(req.params.deviceId);
    if (!device) {
      return res.status(404).json({ message: 'Device not found' });
    }

    const { parameterNames } = req.body || {};
    const names = parameterNames || ['InternetGatewayDevice.'];

    const task = await createTask(req.params.deviceId, 'getParameterValues', {
      parameterNames: Array.isArray(names) ? names : [names],
    });

    return res.json({ message: 'GetParameterValues task queued', data: task });
  } catch (e) {
    console.error('Error creating GetParameterValues task:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Create GetParameterNames task
app.post('/api/devices/:deviceId/tasks/get-parameter-names', async (req, res) => {
  try {
    const device = await getDevice(req.params.deviceId);
    if (!device) {
      return res.status(404).json({ message: 'Device not found' });
    }

    const { parameterPath, nextLevel } = req.body || {};

    const task = await createTask(req.params.deviceId, 'getParameterNames', {
      parameterPath: parameterPath || 'InternetGatewayDevice.',
      nextLevel: nextLevel || false,
    });

    return res.json({ message: 'GetParameterNames task queued', data: task });
  } catch (e) {
    console.error('Error creating GetParameterNames task:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Create Download task (firmware)
app.post('/api/devices/:deviceId/tasks/download', async (req, res) => {
  try {
    const device = await getDevice(req.params.deviceId);
    if (!device) {
      return res.status(404).json({ message: 'Device not found' });
    }

    const {
      url,
      fileType,
      username,
      password,
      targetFileName,
    } = req.body || {};

    if (!url) {
      return res.status(400).json({ message: 'url is required' });
    }

    const task = await createTask(req.params.deviceId, 'download', {
      url,
      fileType: fileType || '1 Firmware Upgrade Image',
      username: username || '',
      password: password || '',
      targetFileName: targetFileName || '',
    });

    return res.json({ message: 'Download task queued', data: task });
  } catch (e) {
    console.error('Error creating Download task:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Create FactoryReset task
app.post('/api/devices/:deviceId/tasks/factory-reset', async (req, res) => {
  try {
    const device = await getDevice(req.params.deviceId);
    if (!device) {
      return res.status(404).json({ message: 'Device not found' });
    }

    const task = await createTask(req.params.deviceId, 'factoryReset', {});
    return res.json({ message: 'FactoryReset task queued', data: task });
  } catch (e) {
    console.error('Error creating FactoryReset task:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Update device tags (lock/unlock)
app.post('/api/devices/:deviceId/tags', async (req, res) => {
  try {
    const device = await getDevice(req.params.deviceId);
    if (!device) {
      return res.status(404).json({ message: 'Device not found' });
    }

    const { tags } = req.body || {};
    if (typeof tags !== 'string') {
      return res.status(400).json({ message: 'tags must be a string' });
    }

    await updateDeviceTags(req.params.deviceId, tags);
    return res.json({ message: 'Tags updated', data: { tags } });
  } catch (e) {
    console.error('Error updating tags:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Lock device
app.post('/api/devices/:deviceId/lock', async (req, res) => {
  try {
    const device = await getDevice(req.params.deviceId);
    if (!device) {
      return res.status(404).json({ message: 'Device not found' });
    }

    const currentTags = (device.tags || '').split(',').map(t => t.trim()).filter(t => t);
    if (!currentTags.includes('locked')) {
      currentTags.push('locked');
    }

    const newTags = currentTags.join(',');
    await updateDeviceTags(req.params.deviceId, newTags);

    return res.json({ message: 'Device locked', data: { tags: newTags } });
  } catch (e) {
    console.error('Error locking device:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Unlock device
app.post('/api/devices/:deviceId/unlock', async (req, res) => {
  try {
    const device = await getDevice(req.params.deviceId);
    if (!device) {
      return res.status(404).json({ message: 'Device not found' });
    }

    const currentTags = (device.tags || '').split(',').map(t => t.trim()).filter(t => t);
    const newTags = currentTags.filter(t => t !== 'locked').join(',');

    await updateDeviceTags(req.params.deviceId, newTags);

    return res.json({ message: 'Device unlocked', data: { tags: newTags } });
  } catch (e) {
    console.error('Error unlocking device:', e);
    return res.status(500).json({ message: 'Database error' });
  }
});

// Setup admin (for Laravel wizard)
app.post('/api/setup/admin', (req, res) => {
  const { username, password } = req.body || {};

  if (!username || !password) {
    return res.status(400).json({ message: 'username & password required' });
  }

  console.log('Setup admin ACS:', { username, password: '***' });

  return res.json({
    success: true,
    message: 'Admin ACS saved',
  });
});

// Fallback 404
app.use((req, res) => {
  return res.status(404).json({ message: 'Not found' });
});

// Start server
app.listen(PORT, () => {
  console.log(`╔════════════════════════════════════════════════╗`);
  console.log(`║  TR-069 ACS Server (Enhanced)                 ║`);
  console.log(`║  Port: ${PORT}                                    ║`);
  console.log(`║  Endpoint: http://0.0.0.0:${PORT}/acs            ║`);
  console.log(`║  API: http://0.0.0.0:${PORT}/api                 ║`);
  console.log(`╚════════════════════════════════════════════════╝`);
});
