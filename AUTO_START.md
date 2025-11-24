# Auto-Start Setup (Windows, WSL, Ubuntu)

Panduan setup auto-start ACS Core supaya otomatis jalan lagi setelah listrik mati, restart, atau crash. Gak perlu panik lagi kalau listrik tiba-tiba mati!

## Daftar Isi

- [Kenapa Perlu Auto-Start](#kenapa-perlu-auto-start)
- [Windows: Setup dengan NSSM](#windows-setup-dengan-nssm-recommended)
- [Windows: Task Scheduler (Alternative)](#windows-setup-dengan-task-scheduler-alternative)
- [Windows: PM2 (Alternative)](#windows-setup-dengan-pm2-alternative)
- [Ubuntu: Setup dengan systemd](#ubuntu-setup-dengan-systemd)
- [WSL: Setup dengan systemd](#wsl-setup-dengan-systemd)
- [Testing Auto-Start](#testing-auto-start)
- [Monitoring & Logging](#monitoring--logging)

---

## Kenapa Perlu Auto-Start

**Masalah tanpa auto-start:**
- ❌ Listrik mati → Server mati
- ❌ Windows restart → Harus manual start server lagi
- ❌ Aplikasi crash → Harus manual restart
- ❌ Lupa start server → MikroTik gak bisa connect
- ❌ Ribet kalau server jauh/remote

**Dengan auto-start:**
- ✅ Listrik nyala lagi → Server otomatis jalan
- ✅ Windows restart → Aplikasi auto-start
- ✅ Aplikasi crash → Auto-restart dalam 10 detik
- ✅ Gak perlu login Windows → Service jalan di background
- ✅ Peace of mind! 😊

---

## Windows: Setup dengan NSSM (Recommended)

NSSM (Non-Sucking Service Manager) adalah tool terbaik untuk bikin aplikasi Node.js jadi Windows Service.

### Langkah 1: Download dan Install NSSM

1. **Download NSSM:**
   - Link: https://nssm.cc/download
   - Pilih versi terbaru (misal: nssm 2.24)

2. **Extract:**
   ```
   Extract zip ke: C:\nssm\
   ```

3. **Struktur folder:**
   ```
   C:\nssm\
   ├── win32\
   │   └── nssm.exe
   └── win64\
       └── nssm.exe
   ```

### Langkah 2: Setup ACS Server Service

**Buka PowerShell sebagai Administrator:**

```powershell
# Masuk ke folder NSSM
cd C:\nssm\win64

# Install service untuk ACS Server
.\nssm.exe install ACSServer

# Atau langsung dengan parameter:
.\nssm.exe install ACSServer "C:\Program Files\nodejs\node.exe" "C:\laragon\www\acs-core\src\index.mjs"
```

**Set konfigurasi service:**

```powershell
# Working directory
.\nssm.exe set ACSServer AppDirectory "C:\laragon\www\acs-core"

# Startup type (AUTO = jalan otomatis saat Windows start)
.\nssm.exe set ACSServer Start SERVICE_AUTO_START

# Set log output (penting untuk debugging!)
mkdir C:\laragon\www\acs-core\logs -Force
.\nssm.exe set ACSServer AppStdout "C:\laragon\www\acs-core\logs\acs-server.log"
.\nssm.exe set ACSServer AppStderr "C:\laragon\www\acs-core\logs\acs-server-error.log"

# Rotate log setiap 1MB (opsional tapi recommended)
.\nssm.exe set ACSServer AppStdoutCreationDisposition 4
.\nssm.exe set ACSServer AppStderrCreationDisposition 4

# Auto-restart kalau crash
.\nssm.exe set ACSServer AppExit Default Restart
.\nssm.exe set ACSServer AppRestartDelay 10000  # Restart setelah 10 detik
```

**Start service:**

```powershell
.\nssm.exe start ACSServer

# Atau pakai sc command:
sc start ACSServer
```

**Cek status:**

```powershell
.\nssm.exe status ACSServer
# Atau
sc query ACSServer
```

### Langkah 3: Setup Laravel Service (Optional)

Kalau mau Laravel juga jadi service:

```powershell
# Install service Laravel
.\nssm.exe install ACSLaravel "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"

# Set arguments
.\nssm.exe set ACSLaravel AppParameters "artisan" "serve" "--host=0.0.0.0" "--port=8000"

# Working directory
.\nssm.exe set ACSLaravel AppDirectory "C:\laragon\www\acs-core\acs-laravel"

# Startup type
.\nssm.exe set ACSLaravel Start SERVICE_AUTO_START

# Logs
.\nssm.exe set ACSLaravel AppStdout "C:\laragon\www\acs-core\logs\laravel.log"
.\nssm.exe set ACSLaravel AppStderr "C:\laragon\www\acs-core\logs\laravel-error.log"

# Start
.\nssm.exe start ACSLaravel
```

> **Note:** Untuk production, lebih baik pakai IIS atau Apache seperti di DEPLOYMENT.md. Ini cuma untuk development/testing.

### Langkah 4: Cek Services di Windows

1. Buka **Services** (`services.msc`)
2. Cari **ACSServer** dan **ACSLaravel**
3. Pastikan:
   - Status: **Running**
   - Startup Type: **Automatic**

---

## Windows: Setup dengan Task Scheduler (Alternative)

Kalau gak mau pakai NSSM, bisa pakai Windows Task Scheduler.

### Langkah 1: Buat Batch Script

**Buat file `C:\laragon\www\acs-core\start-acs.bat`:**

```batch
@echo off
cd /d C:\laragon\www\acs-core
start "ACS Server" cmd /k npm start

cd /d C:\laragon\www\acs-core\acs-laravel
start "Laravel App" cmd /k php artisan serve --host=0.0.0.0 --port=8000
```

### Langkah 2: Buat Task di Task Scheduler

1. Buka **Task Scheduler**
2. Klik **Create Basic Task**
3. Name: `ACS Core Auto Start`
4. Trigger: **When the computer starts**
5. Action: **Start a program**
6. Program: `C:\laragon\www\acs-core\start-acs.bat`
7. Finish

**Settings penting:**
- ✅ Run whether user is logged on or not
- ✅ Run with highest privileges
- ✅ Configure for: Windows 10/11

**Kekurangan Task Scheduler:**
- Gak auto-restart kalau crash
- Muncul command window (bisa hide pakai VBScript)
- Kurang robust dibanding NSSM

---

## Windows: Setup dengan PM2 (Alternative)

PM2 juga bisa di Windows, cocok kalau sudah familiar dengan PM2.

### Langkah 1: Install PM2

```bash
npm install -g pm2
```

### Langkah 2: Setup PM2 untuk ACS Server

```bash
cd C:\laragon\www\acs-core

# Start dengan PM2
pm2 start src/index.mjs --name acs-server

# Auto-restart kalau crash
pm2 startup

# Save config
pm2 save
```

PM2 akan otomatis buat Windows service sendiri!

### Langkah 3: Manage dengan PM2

```bash
# Cek status
pm2 status

# Stop
pm2 stop acs-server

# Restart
pm2 restart acs-server

# View logs
pm2 logs acs-server

# Monitor
pm2 monit
```

---

## Ubuntu: Setup dengan systemd

Di Ubuntu, systemd adalah cara paling proper untuk auto-start service. Lebih reliable dan native!

### Langkah 1: Buat systemd Service File

**Service untuk ACS Server:**

Buat file `/etc/systemd/system/acs-server.service`:

```bash
sudo nano /etc/systemd/system/acs-server.service
```

Isi dengan:
```ini
[Unit]
Description=TR-069 ACS Server
After=network.target
Documentation=https://github.com/your-repo/acs-core

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/acs-core
ExecStart=/usr/bin/node src/index.mjs

# Auto-restart configuration
Restart=always
RestartSec=10

# Environment
Environment=NODE_ENV=production

# Logging
StandardOutput=append:/var/log/acs-server/access.log
StandardError=append:/var/log/acs-server/error.log

# Security (optional but recommended)
NoNewPrivileges=true
PrivateTmp=true

[Install]
WantedBy=multi-user.target
```

**Service untuk Laravel (optional - untuk development):**

Buat file `/etc/systemd/system/acs-laravel.service`:

```bash
sudo nano /etc/systemd/system/acs-laravel.service
```

Isi dengan:
```ini
[Unit]
Description=ACS Laravel Application
After=network.target acs-server.service
Requires=acs-server.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/acs-core/acs-laravel
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8000

Restart=always
RestartSec=10

StandardOutput=append:/var/log/acs-laravel/access.log
StandardError=append:/var/log/acs-laravel/error.log

[Install]
WantedBy=multi-user.target
```

> **Note:** Untuk production, pakai nginx + PHP-FPM seperti di DEPLOYMENT.md, bukan `artisan serve`.

### Langkah 2: Buat Folder Log

```bash
# Buat folder log
sudo mkdir -p /var/log/acs-server
sudo mkdir -p /var/log/acs-laravel

# Set permissions
sudo chown www-data:www-data /var/log/acs-server
sudo chown www-data:www-data /var/log/acs-laravel
```

### Langkah 3: Set Permissions Project

```bash
# Set ownership ke www-data
sudo chown -R www-data:www-data /var/www/acs-core

# Set permissions
sudo chmod -R 755 /var/www/acs-core
```

### Langkah 4: Enable dan Start Services

```bash
# Reload systemd untuk baca service file baru
sudo systemctl daemon-reload

# Enable auto-start saat boot
sudo systemctl enable acs-server
sudo systemctl enable acs-laravel  # kalau pakai

# Start services sekarang
sudo systemctl start acs-server
sudo systemctl start acs-laravel  # kalau pakai

# Cek status
sudo systemctl status acs-server
sudo systemctl status acs-laravel
```

### Langkah 5: Manage Services

```bash
# Start service
sudo systemctl start acs-server

# Stop service
sudo systemctl stop acs-server

# Restart service
sudo systemctl restart acs-server

# Cek status
sudo systemctl status acs-server

# Disable auto-start (kalau perlu)
sudo systemctl disable acs-server

# Enable kembali
sudo systemctl enable acs-server

# View logs real-time
sudo journalctl -u acs-server -f

# View logs (last 100 lines)
sudo journalctl -u acs-server -n 100
```

---

## WSL: Setup dengan systemd

WSL 2 sekarang sudah support systemd! Setup hampir sama dengan Ubuntu.

### Langkah 1: Enable systemd di WSL

Edit `/etc/wsl.conf`:

```bash
sudo nano /etc/wsl.conf
```

Tambahkan:
```ini
[boot]
systemd=true
```

**Restart WSL:**
```powershell
# Di PowerShell Windows
wsl --shutdown
```

Buka WSL lagi, cek systemd sudah jalan:
```bash
systemctl --version
```

### Langkah 2: Buat systemd Service File

**ACS Server Service:**

```bash
sudo nano /etc/systemd/system/acs-server.service
```

Isi dengan (sesuaikan path):
```ini
[Unit]
Description=TR-069 ACS Server
After=network.target

[Service]
Type=simple
User=YOUR_USERNAME
WorkingDirectory=/home/YOUR_USERNAME/acs-core
ExecStart=/usr/bin/node src/index.mjs

Restart=always
RestartSec=10

Environment=NODE_ENV=production

StandardOutput=append:/home/YOUR_USERNAME/acs-core/logs/acs-server.log
StandardError=append:/home/YOUR_USERNAME/acs-core/logs/acs-server-error.log

[Install]
WantedBy=multi-user.target
```

> **Ganti `YOUR_USERNAME`** dengan username WSL kamu!

### Langkah 3: Buat Folder Log

```bash
mkdir -p ~/acs-core/logs
```

### Langkah 4: Enable dan Start

```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable auto-start
sudo systemctl enable acs-server

# Start service
sudo systemctl start acs-server

# Cek status
sudo systemctl status acs-server
```

### Langkah 5: Auto-Start WSL di Windows (Optional)

Kalau mau WSL otomatis start saat Windows boot:

**Buat file `start-wsl.vbs`:**

```vbscript
Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "wsl -d Ubuntu -- /bin/bash -c 'exit'", 0, False
```

**Tambah ke Windows Startup:**
1. Copy file ke `C:\scripts\start-wsl.vbs`
2. Tekan `Win + R`, ketik `shell:startup`
3. Buat shortcut ke `C:\scripts\start-wsl.vbs`

Sekarang WSL + services otomatis jalan saat Windows boot!

### WSL Management Commands

```bash
# Sama seperti Ubuntu
sudo systemctl start acs-server
sudo systemctl stop acs-server
sudo systemctl restart acs-server
sudo systemctl status acs-server

# View logs
sudo journalctl -u acs-server -f
```

---

## Testing Auto-Start

### Windows: Test Restart

```powershell
# Restart komputer
shutdown /r /t 0

# Setelah restart, cek service langsung jalan:
sc query ACSServer
# Status harus: RUNNING

# Test curl
curl http://localhost:7547/health
curl http://localhost:8000
```

### Windows: Test Simulasi Crash

```powershell
# Stop service
sc stop ACSServer

# Tunggu 10 detik (sesuai RestartDelay)
# Service harus auto-restart

# Cek status
sc query ACSServer
# Status harus: RUNNING lagi
```

### Ubuntu/WSL: Test Reboot

```bash
# Reboot system
sudo reboot

# Setelah boot, login dan cek service:
sudo systemctl status acs-server
# Status harus: active (running)

# Test curl
curl http://localhost:7547/health
```

### Ubuntu/WSL: Test Simulasi Crash

```bash
# Stop service paksa
sudo systemctl kill -s SIGKILL acs-server

# Tunggu 10 detik
sleep 10

# Cek status - harus auto-restart
sudo systemctl status acs-server
# Status: active (running)

# Cek log restart
sudo journalctl -u acs-server -n 20
```

### Test Simulasi Listrik Mati (Semua Platform)

1. **Cabut kabel power** (kalau berani 😅)
   - Atau: Force shutdown
2. **Nyalakan lagi**
3. **Tunggu system boot**
4. **Service harus sudah jalan otomatis**

**Verify:**
```powershell
# Cek service status
sc query ACSServer

# Cek log
type C:\laragon\www\acs-core\logs\acs-server.log
```

**Dari PC lain di LAN:**
```bash
curl http://SERVER_IP:7547/health
```

Kalau response OK, berarti **AUTO-START SUKSES!** ✅

---

## Monitoring & Logging

### Cek Service Status

```powershell
# Via sc command
sc query ACSServer
sc query ACSLaravel

# Via NSSM
C:\nssm\win64\nssm.exe status ACSServer

# Via PowerShell
Get-Service ACSServer
Get-Service ACSLaravel
```

### Lihat Logs

```powershell
# ACS Server log (live)
Get-Content C:\laragon\www\acs-core\logs\acs-server.log -Wait -Tail 50

# Laravel log
Get-Content C:\laragon\www\acs-core\logs\laravel.log -Wait -Tail 50

# Error log
Get-Content C:\laragon\www\acs-core\logs\acs-server-error.log -Wait -Tail 50
```

### Setup Email Alert (Optional)

Buat script PowerShell untuk kirim email kalau service down:

**File `C:\scripts\check-acs-service.ps1`:**

```powershell
$service = Get-Service -Name "ACSServer" -ErrorAction SilentlyContinue

if ($service.Status -ne "Running") {
    # Send email alert
    $smtp = "smtp.gmail.com"
    $from = "alert@yourdomain.com"
    $to = "admin@yourdomain.com"
    $subject = "ALERT: ACS Server is DOWN!"
    $body = "ACS Server service is not running. Current status: $($service.Status)"
    
    Send-MailMessage -SmtpServer $smtp -From $from -To $to -Subject $subject -Body $body -UseSsl -Port 587 -Credential (Get-Credential)
    
    # Auto-restart
    Start-Service -Name "ACSServer"
}
```

**Schedule di Task Scheduler (tiap 5 menit):**
```
Trigger: On a schedule, every 5 minutes
Action: powershell.exe -File C:\scripts\check-acs-service.ps1
```

---

## Tips & Best Practices

### 1. Delay Start untuk Dependency

Kalau Laravel service butuh database yang belum ready:

```powershell
# Set delay 30 detik setelah boot
.\nssm.exe set ACSLaravel AppStartup Delayed-Auto
```

### 2. Resource Limits

Batasi CPU/RAM usage:

```powershell
# Limit priority (opsional)
.\nssm.exe set ACSServer AppPriority NORMAL
```

### 3. Maintenance Mode

Kalau mau matikan auto-start sementara:

```powershell
# Disable auto-start
.\nssm.exe set ACSServer Start SERVICE_DEMAND_START

# Enable kembali
.\nssm.exe set ACSServer Start SERVICE_AUTO_START
```

### 4. Backup Config

Export service config (kalau perlu setup ulang):

```powershell
# Registry backup
reg export "HKLM\SYSTEM\CurrentControlSet\Services\ACSServer" C:\backup\acs-service.reg
```

---

## Troubleshooting

### Service Gak Mau Start

```powershell
# Cek error detail
.\nssm.exe status ACSServer

# Lihat Event Viewer
eventvwr.msc
# Navigate: Windows Logs → Application
# Cari error dari ACSServer

# Cek log file
type C:\laragon\www\acs-core\logs\acs-server-error.log
```

### Service Start tapi Langsung Stop

**Kemungkinan:**
1. Path executable salah
2. Working directory salah
3. Node.js belum terinstall
4. Port sudah dipakai

**Debug:**
```powershell
# Test manual dulu
cd C:\laragon\www\acs-core
node src/index.mjs

# Kalau manual jalan, berarti config NSSM yang salah
```

### Service Running tapi Gak Bisa Diakses

```powershell
# Cek port listening
netstat -an | findstr :7547

# Cek firewall
netsh advfirewall firewall show rule name="ACS TR-069 Server"
```

---

## Uninstall Service (Kalau Perlu)

```powershell
# Stop service
sc stop ACSServer

# Uninstall
C:\nssm\win64\nssm.exe remove ACSServer confirm

# Atau pakai sc
sc delete ACSServer
```

---

## Kesimpulan

**Auto-Start Setup Checklist:**
- ✅ NSSM installed
- ✅ ACS Server service created
- ✅ Startup type: Automatic
- ✅ Auto-restart on crash: 10 seconds
- ✅ Logs configured
- ✅ Tested dengan restart Windows
- ✅ Firewall rules set
- ✅ Monitoring ready

**Sekarang server kamu:**
- 🔋 Auto-start setelah listrik mati
- 🔄 Auto-restart kalau crash
- 📝 Ada logging untuk debugging
- 🚀 Jalan terus tanpa manual intervention

**Happy auto-starting!** 🎉

---

**Links:**
- [DEPLOYMENT.md](DEPLOYMENT.md) - Production deployment lengkap
- [REMOTE_ACCESS.md](REMOTE_ACCESS.md) - Setup remote access
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Troubleshooting umum
