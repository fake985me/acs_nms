# DASAN OLT OID Reference

## Overview
Complete OID mappings for DASAN GPON OLT management via SNMP.

**Enterprise OID**: `1.3.6.1.4.1.6296` (DASAN Networks)
**GPON MIB**: `1.3.6.1.4.1.6296.9.1.1.1`

---

## System Information

| Parameter | OID | Description |
|-----------|-----|-------------|
| System Name | 1.3.6.1.2.1.1.5.0 | Device hostname |
| System Description | 1.3.6.1.2.1.1.1.0 | Hardware/software info |
| System Uptime | 1.3.6.1.2.1.1.3.0 | Time since last reboot |
| System Contact | 1.3.6.1.2.1.1.4.0 | Admin contact |
| System Location | 1.3.6.1.2.1.1.6.0 | Physical location |

---

## PON Interface Status

| Parameter | OID | Values |
|-----------|-----|--------|
| Admin Status | 1.3.6.1.4.1.6296.9.1.1.1.21.1.3.{slot}.{port} | 1=up, 2=down |
| Operational Status | 1.3.6.1.4.1.6296.9.1.1.1.21.1.4.{slot}.{port} | 1=up, 2=down |
| ONT Count | 1.3.6.1.4.1.6296.9.1.1.1.21.1.7.{slot}.{port} | Number of ONTs |
| OLT TX Power | 1.3.6.1.4.1.6296.9.1.1.1.21.1.8.{slot}.{port} | dBm * 100 |
| OLT RX Power | 1.3.6.1.4.1.6296.9.1.1.1.21.1.9.{slot}.{port} | dBm * 100 |
| Temperature | 1.3.6.1.4.1.6296.9.1.1.1.21.1.10.{slot}.{port} | °C * 100 |
| Voltage | 1.3.6.1.4.1.6296.9.1.1.1.21.1.11.{slot}.{port} | V * 100 |
| Bias Current | 1.3.6.1.4.1.6296.9.1.1.1.21.1.12.{slot}.{port} | mA * 100 |

---

## ONT Information

**Index Format**: `{slot}.{port}.{ontId}`

| Parameter | OID | Description |
|-----------|-----|-------------|
| ONT Index | 1.3.6.1.4.1.6296.9.1.1.1.22.1.1 | Unique identifier |
| Serial Number | 1.3.6.1.4.1.6296.9.1.1.1.22.1.3 | ONT serial (ASCII) |
| Status | 1.3.6.1.4.1.6296.9.1.1.1.22.1.4 | 1=online, 2=offline |
| Distance | 1.3.6.1.4.1.6296.9.1.1.1.22.1.6 | Meters |
| RX Power (ONT) | 1.3.6.1.4.1.6296.9.1.1.1.22.1.10 | dBm * 100 |
| TX Power (ONT) | 1.3.6.1.4.1.6296.9.1.1.1.22.1.11 | dBm * 100 |
| RX Power (OLT) | 1.3.6.1.4.1.6296.9.1.1.1.22.1.12 | dBm * 100 |
| Temperature | 1.3.6.1.4.1.6296.9.1.1.1.22.1.13 | °C * 100 |
| Voltage | 1.3.6.1.4.1.6296.9.1.1.1.22.1.14 | V * 100 |

---

## Performance Monitoring

| Parameter | OID | Description |
|-----------|-----|-------------|
| FEC Corrected | 1.3.6.1.4.1.6296.9.1.1.1.22.1.20 | Corrected errors |
| FEC Uncorrected | 1.3.6.1.4.1.6296.9.1.1.1.22.1.21 | Uncorrectable errors |
| BER | 1.3.6.1.4.1.6296.9.1.1.1.22.1.22 | Bit error rate |

---

## Alarms

| Parameter | OID | Values |
|-----------|-----|--------|
| LOS Alarm | 1.3.6.1.4.1.6296.9.1.1.1.22.1.30 | 0=clear, 1=active |
| LOF Alarm | 1.3.6.1.4.1.6296.9.1.1.1.22.1.31 | 0=clear, 1=active |
| Dying Gasp | 1.3.6.1.4.1.6296.9.1.1.1.22.1.32 | 0=clear, 1=active |

---

## Usage Examples

### Get ONT Status
```bash
snmpget -v2c -c public 192.168.1.1 1.3.6.1.4.1.6296.9.1.1.1.22.1.4.0.1.1
```

### Get ONT RX Power
```bash
snmpget -v2c -c public 192.168.1.1 1.3.6.1.4.1.6296.9.1.1.1.22.1.10.0.1.1
```
*Output example: -2150 (= -21.50 dBm)*

### Get All ONTs on PON 0/1
```bash
snmpwalk -v2c -c public 192.168.1.1 1.3.6.1.4.1.6296.9.1.1.1.22.1.3.0.1
```

---

## SNMPv3 Configuration Example

```php
$config = [
    'version' => 'v3',
    'sec_level' => 'authPriv',
    'sec_name' => 'admin',
    'auth_protocol' => 'SHA',
    'auth_passphrase' => 'YourAuthPass123',
    'priv_protocol' => 'AES',
    'priv_passphrase' => 'YourPrivPass123',
];
```

---

## Power Conversion

All optical power values are in **dBm * 100**:
- Raw value: `-2150`
- Actual power: `-21.50 dBm`

Formula: `actual_dBm = raw_value / 100`

---

## Status Codes

### ONT Status
- `1` = Online
- `2` = Offline
- `3` = In Progress (ONU discovery)

### Alarm Status
- `0` = Clear
- `1` = Active/Raised

---

**Reference**: DASAN V-SOL GPON OLT MIB v2.8  
**Last Updated**: 2025-11-24
