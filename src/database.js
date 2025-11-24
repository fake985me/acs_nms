// src/database.js - Database abstraction layer for ACS

import sqlite3 from 'sqlite3';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const DB_FILE = process.env.DB_FILE || join(__dirname, '..', 'acs.db');
const SCHEMA_FILE = join(__dirname, '..', 'schema.sql');

// Create database file if it doesn't exist
if (!fs.existsSync(DB_FILE)) {
  fs.closeSync(fs.openSync(DB_FILE, 'w'));
}

// Create database connection
const db = new sqlite3.Database(DB_FILE);

// Initialize schema
const schemaSql = fs.readFileSync(SCHEMA_FILE, 'utf8');
db.exec(schemaSql);

// Helper function to run queries with promises
function runQuery(sql, params = []) {
  return new Promise((resolve, reject) => {
    db.run(sql, params, function (err) {
      if (err) return reject(err);
      resolve({ lastID: this.lastID, changes: this.changes });
    });
  });
}

// Helper function to get single row
function getOne(sql, params = []) {
  return new Promise((resolve, reject) => {
    db.get(sql, params, (err, row) => {
      if (err) return reject(err);
      resolve(row || null);
    });
  });
}

// Helper function to get multiple rows
function getAll(sql, params = []) {
  return new Promise((resolve, reject) => {
    db.all(sql, params, (err, rows) => {
      if (err) return reject(err);
      resolve(rows || []);
    });
  });
}

// ISO timestamp helper
function isoNow() {
  return new Date().toISOString();
}

// Normalize IP address (remove IPv6 prefix)
function normalizeIp(ip) {
  if (!ip) return ip;
  if (ip.startsWith('::ffff:')) return ip.replace('::ffff:', '');
  return ip;
}

/* ==================== DEVICE OPERATIONS ==================== */

/**
 * Upsert device from TR-069 Inform
 */
export async function upsertDevice(deviceInfo) {
  const now = isoNow();
  const {
    deviceId,
    oui,
    productClass,
    serialNumber,
    manufacturer,
    modelName,
    softwareVersion,
    ipAddress,
  } = deviceInfo;

  const sql = `
    INSERT INTO devices (
      device_id, oui, product_class, serial_number, 
      manufacturer, model_name, software_version, 
      last_inform_at, ip_address, tags, created_at, updated_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?)
    ON CONFLICT(device_id) DO UPDATE SET
      oui = excluded.oui,
      product_class = excluded.product_class,
      serial_number = excluded.serial_number,
      manufacturer = excluded.manufacturer,
      model_name = excluded.model_name,
      software_version = excluded.software_version,
      last_inform_at = excluded.last_inform_at,
      ip_address = excluded.ip_address,
      updated_at = excluded.updated_at
  `;

  await runQuery(sql, [
    deviceId,
    oui || null,
    productClass || null,
    serialNumber || null,
    manufacturer || null,
    modelName || null,
    softwareVersion || null,
    now,
    normalizeIp(ipAddress),
    now,
    now,
  ]);

  return deviceId;
}

/**
 * Get device by ID
 */
export async function getDevice(deviceId) {
  return getOne('SELECT * FROM devices WHERE device_id = ?', [deviceId]);
}

/**
 * Get all devices with optional filters
 */
export async function getDevices(filters = {}) {
  const where = [];
  const params = [];

  if (filters.manufacturer) {
    where.push('manufacturer LIKE ?');
    params.push(`%${filters.manufacturer}%`);
  }

  if (filters.oui) {
    where.push('oui LIKE ?');
    params.push(`%${filters.oui}%`);
  }

  if (filters.ip) {
    where.push('ip_address LIKE ?');
    params.push(`%${filters.ip}%`);
  }

  if (filters.tags) {
    where.push('tags LIKE ?');
    params.push(`%${filters.tags}%`);
  }

  const sql = `
    SELECT * FROM devices
    ${where.length ? 'WHERE ' + where.join(' AND ') : ''}
    ORDER BY last_inform_at DESC
  `;

  return getAll(sql, params);
}

/**
 * Update device tags
 */
export async function updateDeviceTags(deviceId, tags) {
  const now = isoNow();
  await runQuery(
    'UPDATE devices SET tags = ?, updated_at = ? WHERE device_id = ?',
    [tags, now, deviceId]
  );
}

/* ==================== SESSION OPERATIONS ==================== */

/**
 * Upsert device session
 */
export async function upsertSession(deviceId, remoteIp, sessionId = null) {
  const now = isoNow();
  const normalizedIp = normalizeIp(remoteIp);

  try {
    await runQuery(
      `INSERT INTO device_sessions (device_id, remote_ip, session_id, last_seen_at)
       VALUES (?, ?, ?, ?)`,
      [deviceId, normalizedIp, sessionId, now]
    );
  } catch (err) {
    // If insert fails (duplicate), update instead
    await runQuery(
      `UPDATE device_sessions 
       SET last_seen_at = ?, session_id = ? 
       WHERE device_id = ? AND remote_ip = ?`,
      [now, sessionId, deviceId, normalizedIp]
    );
  }
}

/**
 * Find session by IP address
 */
export async function findSessionByIp(remoteIp) {
  const normalizedIp = normalizeIp(remoteIp);
  return getOne(
    `SELECT * FROM device_sessions 
     WHERE remote_ip = ? 
     ORDER BY last_seen_at DESC LIMIT 1`,
    [normalizedIp]
  );
}

/* ==================== PARAMETER OPERATIONS ==================== */

/**
 * Upsert device parameters
 */
export async function upsertParameters(deviceId, parameters) {
  if (!parameters || parameters.length === 0) return;

  const now = isoNow();
  const sql = `
    INSERT INTO device_parameters (device_id, name, value, type, updated_at)
    VALUES (?, ?, ?, ?, ?)
    ON CONFLICT(device_id, name) DO UPDATE SET
      value = excluded.value,
      type = excluded.type,
      updated_at = excluded.updated_at
  `;

  return new Promise((resolve, reject) => {
    db.serialize(() => {
      const stmt = db.prepare(sql);
      for (const param of parameters) {
        const name = param.name || param.Name;
        if (!name) continue;

        let value = param.value || param.Value;
        let type = 'xsd:string';

        // Handle structured value with type attribute
        if (typeof value === 'object' && value !== null) {
          type = (value.$ && value.$['xsi:type']) || 'xsd:string';
          value = value._;
        }

        stmt.run(deviceId, name, value ?? null, type, now);
      }
      stmt.finalize((err) => {
        if (err) return reject(err);
        resolve();
      });
    });
  });
}

/**
 * Get parameters for a device
 */
export async function getParameters(deviceId, nameFilter = null) {
  if (nameFilter) {
    return getAll(
      `SELECT name, value, type, updated_at 
       FROM device_parameters 
       WHERE device_id = ? AND name LIKE ?
       ORDER BY name`,
      [deviceId, `%${nameFilter}%`]
    );
  }
  return getAll(
    `SELECT name, value, type, updated_at 
     FROM device_parameters 
     WHERE device_id = ? 
     ORDER BY name`,
    [deviceId]
  );
}

/* ==================== TASK OPERATIONS ==================== */

/**
 * Create a new task
 */
export async function createTask(deviceId, type, payload = {}) {
  const now = isoNow();
  const payloadStr = JSON.stringify(payload);

  const result = await runQuery(
    `INSERT INTO tasks (device_id, type, parameters, status, created_at, updated_at)
     VALUES (?, ?, ?, 'pending', ?, ?)`,
    [deviceId, type, payloadStr, now, now]
  );

  return { id: result.lastID, deviceId, type, status: 'pending' };
}

/**
 * Get next pending task for a device
 */
export async function getNextPendingTask(deviceId) {
  return getOne(
    `SELECT * FROM tasks 
     WHERE device_id = ? AND status = 'pending' 
     ORDER BY id ASC LIMIT 1`,
    [deviceId]
  );
}

/**
 * Get all tasks for a device
 */
export async function getTasks(deviceId, status = null) {
  if (status) {
    return getAll(
      `SELECT * FROM tasks 
       WHERE device_id = ? AND status = ? 
       ORDER BY id DESC`,
      [deviceId, status]
    );
  }
  return getAll(
    `SELECT * FROM tasks 
     WHERE device_id = ? 
     ORDER BY id DESC`,
    [deviceId]
  );
}

/**
 * Update task status
 */
export async function updateTaskStatus(taskId, status) {
  const now = isoNow();
  await runQuery(
    'UPDATE tasks SET status = ?, updated_at = ? WHERE id = ?',
    [status, now, taskId]
  );
}

/**
 * Mark last sent task as done
 */
export async function markLastSentTaskDone(deviceId, type) {
  const now = isoNow();
  await runQuery(
    `UPDATE tasks
     SET status = 'done', updated_at = ?
     WHERE id = (
       SELECT id FROM tasks
       WHERE device_id = ? AND type = ? AND status = 'sent'
       ORDER BY id DESC LIMIT 1
     )`,
    [now, deviceId, type]
  );
}

/* ==================== DATABASE MANAGEMENT ==================== */

/**
 * Close database connection
 */
export function closeDatabase() {
  return new Promise((resolve, reject) => {
    db.close((err) => {
      if (err) return reject(err);
      resolve();
    });
  });
}

/**
 * Export raw database for advanced queries
 */
export { db };
