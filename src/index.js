// src/index.js (ES module)

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
  addTask,
  getNextTask,
  listTasks,
} from './taskQueue.js';

const app = express();
const PORT = process.env.ACS_PORT || 7547;

// Logger sederhana
app.use((req, res, next) => {
  console.log(`[${new Date().toISOString()}] ${req.method} ${req.url}`);
  next();
});

// Parser body:
// - SOAP TR-069 pakai text/xml
// - REST API pakai JSON
app.use(
  bodyParser.text({
    type: ['text/xml', 'application/xml', '*/xml'],
  }),
);
app.use(express.json());
app.use(cors());

// In-memory DB device
const devices = new Map(); // key: deviceId, value: { deviceId, lastInform, ... }

// Helper: ambil DeviceId dari Inform
function extractDeviceIdFromInform(method) {
  try {
    const deviceId = method.DeviceId;
    const serial = deviceId.SerialNumber;
    const oui = deviceId.OUI;
    const product = deviceId.ProductClass;
    const manufacturer = deviceId.Manufacturer;

    const uniqueId = `${oui}-${serial}`;
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

/* ---------------- Health & basic routes ---------------- */

app.get('/health', (req, res) => {
  return res.json({
    status: 'ok',
    devices: devices.size,
  });
});

// GET /acs hanya untuk test (fetch/curl). TR-069 pakai POST.
app.get(['/acs', '/'], (req, res) => {
  return res.status(200).send('ACS OK (HTTP GET)');
});

/* ---------------- TR-069 SOAP endpoint ------------------ */

// Endpoint utama ACS: URL ini yang diset di CPE TR-069
app.post(['/acs', '/'], async (req, res) => {
  const xml = req.body;

  // Empty POST → CPE polling task
  if (!xml || typeof xml !== 'string' || !xml.trim()) {
    console.log('Empty POST /acs → CPE polling for tasks');
    await handleRequestForTask(null, null, null, res);
    return;
  }

  console.log('\n=== Incoming CWMP ===');
  console.log(xml);

  try {
    const parsed = await parseCwmpXml(xml);
    const { header, methodName, method } = parsed;

    const headerId = header?.['cwmp:ID']?._ || header?.ID?._ || null;
    console.log('Method:', methodName, 'Header ID:', headerId);

    switch (methodName) {
      case 'cwmp:Inform':
      case 'Inform':
        await handleInform(method, headerId, res);
        break;

      case 'cwmp:TransferComplete':
      case 'TransferComplete':
        console.log('TransferComplete from CPE');
        return res.status(204).end();

      case 'cwmp:Fault':
      case 'Fault':
        console.log('Fault from CPE:', JSON.stringify(method, null, 2));
        return res.status(204).end();

      default:
        console.log(
          'Unknown or empty method, maybe CPE polling for tasks',
        );
        await handleRequestForTask(methodName, method, headerId, res);
        break;
    }
  } catch (e) {
    console.error('Error parsing CWMP:', e);
    return res.status(500).send('Invalid CWMP');
  }
});

// Handle Inform
async function handleInform(method, headerId, res) {
  const deviceInfo = extractDeviceIdFromInform(method);
  if (!deviceInfo) {
    console.log('Cannot extract DeviceId');
  } else {
    console.log('Inform from:', deviceInfo);

    // Update in-memory device info
    const now = new Date().toISOString();
    const existing = devices.get(deviceInfo.id) || {};
    devices.set(deviceInfo.id, {
      ...existing,
      deviceId: deviceInfo.id,
      serial: deviceInfo.serial,
      oui: deviceInfo.oui,
      product: deviceInfo.product,
      manufacturer: deviceInfo.manufacturer,
      lastInform: now,
    });
  }

  // Balas InformResponse
  const respXml = buildInformResponse(headerId);
  console.log('\n=== Sending InformResponse ===');
  console.log(respXml);

  res.set('Content-Type', 'text/xml; charset="utf-8"');
  return res.status(200).send(respXml);
}

// Setelah InformResponse, CPE akan kirim request lagi / Empty untuk cek apakah ada task
async function handleRequestForTask(methodName, method, headerId, res) {
  // Untuk demo: ambil 1 device sembarang
  const anyDevice = Array.from(devices.values())[0];

  if (!anyDevice) {
    console.log('No device registered yet, returning 204');
    return res.status(204).end();
  }

  const nextTask = getNextTask(anyDevice.deviceId);
  if (!nextTask) {
    console.log('No pending task for device', anyDevice.deviceId);
    return res.status(204).end();
  }

  console.log('Sending task', nextTask, 'to device', anyDevice.deviceId);

  // Bangun SOAP body sesuai jenis task
  let body;
  switch (nextTask.name) {
    case 'Reboot':
      body = {
        'cwmp:Reboot': {
          CommandKey: nextTask.id,
        },
      };
      break;

    case 'SetParameterValues':
      body = {
        'cwmp:SetParameterValues': {
          ParameterList: {
            ParameterValueStruct: Object.entries(
              nextTask.parameters || {},
            ).map(([name, value]) => ({
              Name: name,
              Value: {
                _: value,
                $: { 'xsi:type': 'xsd:string' },
              },
            })),
          },
          ParameterKey: nextTask.id,
        },
      };
      break;

    default:
      console.log('Unknown task type, returning 204');
      return res.status(204).end();
  }

  const header = {};
  if (headerId) {
    header['cwmp:ID'] = {
      $: { 'soap-env:mustUnderstand': '1' },
      _: headerId,
    };
  } else {
    header['cwmp:ID'] = {
      $: { 'soap-env:mustUnderstand': '1' },
      _: crypto.randomUUID(),
    };
  }

  const respXml = buildCwmpEnvelope(body, header);
  console.log('\n=== Sending Task SOAP ===');
  console.log(respXml);

  res.set('Content-Type', 'text/xml; charset="utf-8"');
  return res.status(200).send(respXml);
}

/* ----------------- REST API untuk dashboard / Laravel ------------------ */

// List perangkat
app.get('/api/devices', (req, res) => {
  return res.json(Array.from(devices.values()));
});

// Tambah task reboot untuk 1 device
app.post('/api/devices/:deviceId/tasks/reboot', (req, res) => {
  const { deviceId } = req.params;
  if (!devices.has(deviceId)) {
    return res.status(404).json({ message: 'Device not found' });
  }

  const task = {
    id: crypto.randomUUID(),
    name: 'Reboot',
    parameters: {},
  };
  addTask(deviceId, task);

  return res.json({ message: 'Reboot task queued', task });
});

// SetParameterValues contoh: ganti SSID WiFi
app.post('/api/devices/:deviceId/tasks/set-parameters', (req, res) => {
  const { deviceId } = req.params;
  const { parameters } = req.body || {};

  if (!devices.has(deviceId)) {
    return res.status(404).json({ message: 'Device not found' });
  }

  if (!parameters || typeof parameters !== 'object') {
    return res
      .status(400)
      .json({ message: 'parameters object is required' });
  }

  const task = {
    id: crypto.randomUUID(),
    name: 'SetParameterValues',
    parameters,
  };
  addTask(deviceId, task);

  return res.json({ message: 'SetParameterValues task queued', task });
});

// List antrean task utk device
app.get('/api/devices/:deviceId/tasks', (req, res) => {
  const { deviceId } = req.params;
  const tasks = listTasks(deviceId);
  return res.json(tasks);
});

// Setup admin untuk Laravel (dipanggil saat wizard pertama kali)
app.post('/api/setup/admin', (req, res) => {
  const { username, password } = req.body || {};

  if (!username || !password) {
    return res
      .status(400)
      .json({ message: 'username & password required' });
  }

  console.log('Setup admin ACS:', { username, password: '***' });

  return res.json({
    success: true,
    message: 'Admin ACS tersimpan (dummy)',
  });
});

// Fallback 404 untuk route lain
app.use((req, res) => {
  return res.status(404).json({ message: 'Not found' });
});

// Start server
app.listen(PORT, () => {
  console.log(`TR-069 ACS mini listening on http://0.0.0.0:${PORT}/acs`);
});
