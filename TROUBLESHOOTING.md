# Troubleshooting MikroTik TR-069 Connection Issue

## Problem yang Ditemukan

Dari log MikroTik:
```
session finished with error: "Idle timeout - connecting"
session finished with error: "No route to host"
session finished with error: "Connection refused"
```

Ini menunjukkan MikroTik **tidak bisa reach** ACS server di `172.24.128.1:7547`

## Solusi

### 1. Buka Windows Firewall (PALING PENTING!)

**Option A - Via PowerShell (Run as Administrator):**

```powershell
# Buka PowerShell as Administrator, lalu jalankan:
netsh advfirewall firewall add rule name="ACS Server TR-069" dir=in action=allow protocol=TCP localport=7547
```

**Option B - Via Windows Defender Firewall GUI:**

1. Buka `Windows Defender Firewall with Advanced Security`
2. Klik **Inbound Rules** di kiri
3. Klik **New Rule...** di kanan
4. Pilih **Port** → Next
5. Pilih **TCP** dan **Specific local ports**: `7547` → Next
6. Pilih **Allow the connection** → Next
7. Centang semua (Domain, Private, Public) → Next
8. Name: `ACS Server TR-069` → Finish

### 2. Verifikasi Port Terbuka

Dari MikroTik terminal, test koneksi:

```bash
# Test 1: Ping server
/ping 172.24.128.1

# Test 2: Fetch dari ACS health endpoint
/tool fetch url=http://172.24.128.1:7547/health

# Jika berhasil, akan ada response seperti:
# status: finished
```

### 3. Pastikan IP Server Benar

Cek IP server yang aktif:

```powershell
ipconfig
```

Cari IP yang **terhubung ke network yang sama dengan MikroTik**.

Jika IP bukan `172.24.128.1`, update ACS URL di MikroTik:

```bash
/tr069-client set acs-url=http://IP_YANG_BENAR:7547/acs
```

### 4. Force MikroTik Reconnect

Setelah firewall dibuka:

```bash
# Restart TR-069 client
/tr069-client restart

# Force immediate connection
/tr069-client force-connect

# Monitor log
/log print follow-only where topics~"tr069"
```

## Expected Result

Setelah firewall dibuka dan konfigurasi benar, log MikroTik seharusnya menunjukkan:

```
tr069,info connected to ACS
tr069,info inform sent successfully
```

Dan device akan langsung muncul di:
- Laravel UI: http://localhost:8000/devices
- ACS API: http://localhost:7547/api/devices

## Quick Test

Untuk memastikan server bisa diakses dari MikroTik:

1. Dari **MikroTik terminal**:
   ```bash
   /tool fetch url=http://172.24.128.1:7547/health
   ```

2. Seharusnya return:
   ```
   status: finished
   ```

Jika masih `connection-failed`, berarti:
- Firewall masih memblokir
- IP address salah
- Network routing issue

## Debugging Steps

1. **Cek firewall rules**:
   ```powershell
   netsh advfirewall firewall show rule name="ACS Server TR-069"
   ```

2. **Cek server listening**:
   ```powershell
   netstat -an | findstr :7547
# Troubleshooting MikroTik TR-069 Connection Issue

## Problem yang Ditemukan

Dari log MikroTik:
```
session finished with error: "Idle timeout - connecting"
session finished with error: "No route to host"
session finished with error: "Connection refused"
```

Ini menunjukkan MikroTik **tidak bisa reach** ACS server di `172.24.128.1:7547`

## Solusi

### 1. Buka Windows Firewall (PALING PENTING!)

**Option A - Via PowerShell (Run as Administrator):**

```powershell
# Buka PowerShell as Administrator, lalu jalankan:
netsh advfirewall firewall add rule name="ACS Server TR-069" dir=in action=allow protocol=TCP localport=7547
```

**Option B - Via Windows Defender Firewall GUI:**

1. Buka `Windows Defender Firewall with Advanced Security`
2. Klik **Inbound Rules** di kiri
3. Klik **New Rule...** di kanan
4. Pilih **Port** → Next
5. Pilih **TCP** dan **Specific local ports**: `7547` → Next
6. Pilih **Allow the connection** → Next
7. Centang semua (Domain, Private, Public) → Next
8. Name: `ACS Server TR-069` → Finish

### 2. Verifikasi Port Terbuka

Dari MikroTik terminal, test koneksi:

```bash
# Test 1: Ping server
/ping 172.24.128.1

# Test 2: Fetch dari ACS health endpoint
/tool fetch url=http://172.24.128.1:7547/health

# Jika berhasil, akan ada response seperti:
# status: finished
```

### 3. Pastikan IP Server Benar

Cek IP server yang aktif:

```powershell
ipconfig
```

Cari IP yang **terhubung ke network yang sama dengan MikroTik**.

Jika IP bukan `172.24.128.1`, update ACS URL di MikroTik:

```bash
/tr069-client set acs-url=http://IP_YANG_BENAR:7547/acs
```

### 4. Force MikroTik Reconnect

Setelah firewall dibuka:

```bash
# Restart TR-069 client
/tr069-client restart

# Force immediate connection
/tr069-client force-connect

# Monitor log
/log print follow-only where topics~"tr069"
```

## Expected Result

Setelah firewall dibuka dan konfigurasi benar, log MikroTik seharusnya menunjukkan:

```
tr069,info connected to ACS
tr069,info inform sent successfully
```

Dan device akan langsung muncul di:
- Laravel UI: http://localhost:8000/devices
- ACS API: http://localhost:7547/api/devices

## Quick Test

Untuk memastikan server bisa diakses dari MikroTik:

1. Dari **MikroTik terminal**:
   ```bash
   /tool fetch url=http://172.24.128.1:7547/health
   ```

2. Seharusnya return:
   ```
   status: finished
   ```

Jika masih `connection-failed`, berarti:
- Firewall masih memblokir
- IP address salah
- Network routing issue

## Debugging Steps

1. **Cek firewall rules**:
   ```powershell
   netsh advfirewall firewall show rule name="ACS Server TR-069"
   ```

2. **Cek server listening**:
   ```powershell
   netstat -an | findstr :7547
   ```
   Seharusnya ada: `0.0.0.0:7547 ... LISTENING`

3. **Restart ACS server** (jika perlu):
   ```powershell
   # Stop dengan Ctrl+C, lalu:
    npm start
    ```

---

## Server Restart Procedures

### When to Restart Servers

Restart ACS server and Laravel app when:
- After code changes or bug fixes
- After database schema changes
- After configuration changes in `.env`
- Server becomes unresponsive
- After system reboot

### How to Restart

**ACS Server (Node.js):**
1. In the terminal running `npm start`, press **Ctrl+C**
2. Wait for "Server stopped" or return to prompt
3. Run: `npm start`
4. Wait for "TR-069 ACS Server (Enhanced)" message

**Laravel App:**
1. In the terminal running `php artisan serve`, press **Ctrl+C**
2. Run: `php artisan serve`
3. Optionally clear cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

**Both Servers (Quick Method):**
```bash
# Stop both with Ctrl+C in each terminal, then:

# Terminal 1
cd c:/laragon/www/acs-core
npm start

# Terminal 2
cd c:/laragon/www/acs-core/acs-laravel
php artisan serve
```

### After Restart

- Devices will reconnect automatically (within 5 minutes)
- Or force reconnect from MikroTik: `/tr069-client restart`
- Check logs for successful connection
- Refresh browser dashboard

---

## Additional Resources

- [Installation Guide](INSTALLATION.md) - Complete setup instructions
- [MikroTik Setup](MIKROTIK_SETUP.md) - TR-069 configuration
- [MikroTik Offline Fix](MIKROTIK_OFFLINE_FIX.md) - Fix offline status
- [MikroTik Fast Reconnect](MIKROTIK_FAST_RECONNECT.md) - Reduce connection interval
