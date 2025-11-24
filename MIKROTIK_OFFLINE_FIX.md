# Troubleshooting MikroTik TR-069 Offline Status

## Problem
MikroTik menunjukkan status "Offline" di dashboard ACS setelah server restart.

## Root Cause
Device status dianggap "Offline" jika `last_inform_at` lebih dari 5 menit yang lalu. Setelah ACS server restart, MikroTik perlu reconnect.

## Quick Fix

### Step 1: Verify ACS Server Running
```bash
# Check if ACS server listening on port 7547
netstat -an | findstr :7547
```

Should show: `0.0.0.0:7547` or `[::]:7547` in LISTENING state

### Step 2: Force MikroTik Reconnect

**Via MikroTik Terminal:**
```bash
# Method 1: Disable and Enable TR-069 client
/tr069-client disable
/tr069-client enable

# Method 2: Force connect (if supported)
/tr069-client force-connect
```

**Via Winbox/WebFig:**
1. Go to **System → TR-069 Client**
2. Uncheck "Enabled"
3. Click Apply
4. Check "Enabled" again
5. Click Apply

### Step 3: Verify MikroTik Configuration

```bash
/tr069-client print
```

**Expected output:**
```
          enabled: yes
          acs-url: http://192.168.1.5:7547/acs
periodic-inform-enabled: yes
periodic-inform-interval: 15s  (or your custom value)
```

### Step 4: Check Connectivity

**From MikroTik:**
```bash
# Test if MikroTik can reach ACS server
/ping 192.168.1.5 count=5

# Test HTTP connectivity
/tool fetch url="http://192.168.1.5:7547/health" mode=http
```

### Step 5: Monitor ACS Server Logs

Watch for incoming Inform messages:
```bash
# In ACS terminal (npm start)
# Should see messages like:
[2025-11-24T...] POST /acs
Method: Inform Header ID: null From: 192.168.1.2
✓ Device E48D8C-hAP lite-HG709VJ17JJ updated
```

## Verification

After MikroTik reconnects, verify in Laravel dashboard:

1. **Dashboard**: http://localhost:8000/dashboard
2. **Device List**: http://localhost:8000/devices
3. **Device should show**: Status badge = "Online" (green)

Status calculation:
- **Online**: `last_inform_at` < 5 minutes ago
- **Offline**: `last_inform_at` >= 5 minutes ago

## Common Issues

### Issue 1: ACS Server Not Running
**Symptoms**: Port 7547 not listening

**Solution**:
```bash
cd c:/laragon/www/acs-core
npm start
```

### Issue 2: Wrong ACS URL in MikroTik
**Symptoms**: MikroTik logs show connection refused

**Solution**:
```bash
/tr069-client set acs-url=http://192.168.1.5:7547/acs
```

Use your actual server IP (check with `ipconfig`)

### Issue 3: Firewall Blocking
**Symptoms**: Connection timeout

**Solution**:
1. Disable Windows Firewall temporarily to test
2. If works, add firewall rule for port 7547
3. Or permanently disable firewall for development

### Issue 4: Periodic Inform Disabled
**Symptoms**: MikroTik only connects once, then offline

**Solution**:
```bash
/tr069-client set periodic-inform-enabled=yes
/tr069-client set periodic-inform-interval=15s
```

### Issue 5: Database Empty After migrate:fresh
**Symptoms**: No devices in dashboard

**Note**: `migrate:fresh` drops all data including devices. Device will reappear after next Inform.

## Manual Database Check

```bash
# Check device in ACS database
cd c:/laragon/www/acs-core
node -e "const sqlite3 = require('sqlite3'); const db = new sqlite3.Database('./acs.db'); db.all('SELECT device_id, last_inform_at, ip_address FROM devices', [], (err, rows) => { console.log(JSON.stringify(rows, null, 2)); db.close(); });"
```

## Expected Timeline

1. **Immediate**: After enable TR-069 client
2. **Within 15 seconds**: If periodic-inform-interval=15s
3. **Within 5 minutes**: If using default interval (300s)

## Still Not Working?

Check:
1. ✅ ACS server running (`npm start` in c:/laragon/www/acs-core)
2. ✅ Laravel app running (`php artisan serve` in acs-laravel folder)
3. ✅ MikroTik can ping server IP
4. ✅ TR-069 client enabled on MikroTik
5. ✅ Correct ACS URL configured
6. ✅ Periodic inform enabled
7. ✅ Check ACS server terminal for incoming requests

## Force Immediate Update

If you need immediate update for testing:

**MikroTik Terminal:**
```bash
# Set very short interval temporarily
/tr069-client set periodic-inform-interval=5s

# After testing, set back to normal
/tr069-client set periodic-inform-interval=5m
```

**Or trigger manual inform** (if your RouterOS version supports it):
```bash
/tr069-client force-update
```

---

**Status after following these steps**: MikroTik should show "Online" in dashboard within 15 seconds (or your configured interval).
