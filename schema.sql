-- Core Tables
CREATE TABLE IF NOT EXISTS devices (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  device_id TEXT UNIQUE,            -- ID CWMP: OUI-ProductClass-Serial
  oui TEXT,
  product_class TEXT,
  serial_number TEXT,
  manufacturer TEXT,
  model_name TEXT,
  software_version TEXT,
  hardware_version TEXT,
  last_inform_at TEXT,
  ip_address TEXT,
  tags TEXT DEFAULT '',             -- "locked,vip"
  created_at TEXT,
  updated_at TEXT
);

CREATE TABLE IF NOT EXISTS tasks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  device_id TEXT,                   -- ref ke devices.device_id
  type TEXT,                        -- 'reboot' | 'setParameterValues' | dll
  payload TEXT,                     -- JSON (parameterValues, dll.)
  status TEXT DEFAULT 'pending',    -- 'pending' | 'sent' | 'done' | 'error'
  error_message TEXT,
  created_at TEXT,
  updated_at TEXT
);

CREATE TABLE IF NOT EXISTS device_sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  device_id TEXT,
  remote_ip TEXT,
  session_id TEXT,
  last_seen_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_device_sessions_device_ip
  ON device_sessions(device_id, remote_ip);

CREATE TABLE IF NOT EXISTS device_parameters (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  device_id TEXT,
  name TEXT,
  value TEXT,
  type TEXT,
  updated_at TEXT
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_device_parameters_device_name
  ON device_parameters(device_id, name);

-- Advanced Features Tables

-- Presets: Configuration templates
CREATE TABLE IF NOT EXISTS presets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT UNIQUE NOT NULL,
  description TEXT,
  enabled INTEGER DEFAULT 1,        -- 0 = disabled, 1 = enabled
  weight INTEGER DEFAULT 0,         -- Priority/order
  created_at TEXT,
  updated_at TEXT
);

CREATE TABLE IF NOT EXISTS preset_parameters (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  preset_id INTEGER NOT NULL,
  parameter_name TEXT NOT NULL,
  parameter_value TEXT,
  parameter_type TEXT DEFAULT 'xsd:string',
  FOREIGN KEY (preset_id) REFERENCES presets(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_preset_parameters_preset_id
  ON preset_parameters(preset_id);

-- Provisions: Automation scripts
CREATE TABLE IF NOT EXISTS provisions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT UNIQUE NOT NULL,
  description TEXT,
  script TEXT NOT NULL,             -- JavaScript code
  enabled INTEGER DEFAULT 1,
  weight INTEGER DEFAULT 0,         -- Priority/order
  created_at TEXT,
  updated_at TEXT
);

-- Virtual Parameters: Computed parameters
CREATE TABLE IF NOT EXISTS virtual_parameters (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT UNIQUE NOT NULL,
  description TEXT,
  script TEXT NOT NULL,             -- JavaScript expression/function
  enabled INTEGER DEFAULT 1,
  created_at TEXT,
  updated_at TEXT
);

-- Device Files: Firmware and configuration files
CREATE TABLE IF NOT EXISTS device_files (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  filename TEXT NOT NULL,
  file_type TEXT,                   -- 'firmware' | 'config' | 'vendor'
  version TEXT,
  manufacturer TEXT,
  model TEXT,
  file_size INTEGER,
  file_path TEXT,                   -- Local storage path or URL
  checksum TEXT,                    -- MD5 or SHA256
  description TEXT,
  created_at TEXT,
  updated_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_device_files_type
  ON device_files(file_type);

-- Device Faults: Track errors and faults
CREATE TABLE IF NOT EXISTS device_faults (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  device_id TEXT NOT NULL,
  fault_code TEXT,
  fault_string TEXT,
  severity TEXT DEFAULT 'error',    -- 'info' | 'warning' | 'error' | 'critical'
  details TEXT,                     -- JSON with additional info
  resolved INTEGER DEFAULT 0,       -- 0 = open, 1 = resolved
  created_at TEXT,
  resolved_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_device_faults_device_id
  ON device_faults(device_id);

CREATE INDEX IF NOT EXISTS idx_device_faults_resolved
  ON device_faults(resolved);

-- Provision Execution Log
CREATE TABLE IF NOT EXISTS provision_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  device_id TEXT NOT NULL,
  provision_id INTEGER,
  status TEXT,                      -- 'success' | 'error'
  output TEXT,                      -- Script output/result
  error_message TEXT,
  execution_time_ms INTEGER,
  created_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_provision_logs_device_id
  ON provision_logs(device_id);
