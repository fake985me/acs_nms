# ACS Core - Panduan Instalasi

Panduan lengkap instalasi TR-069 ACS Server dengan Dashboard Laravel untuk Windows, WSL, dan Ubuntu. Gampang kok!

## Daftar Isi

- [Gambaran Umum](#gambaran-umum)
- [Yang Harus Disiapkan](#yang-harus-disiapkan)
- [Instalasi: Windows (Laragon)](#instalasi-windows-laragon)
- [Instalasi: WSL (Ubuntu di Windows)](#instalasi-wsl-ubuntu-di-windows)
- [Instalasi: Ubuntu (Asli)](#instalasi-ubuntu-asli)
- [Setup Setelah Instalasi](#setup-setelah-instalasi)
- [Cek Apakah Berhasil](#cek-apakah-berhasil)
- [Masalah yang Sering Muncul](#masalah-yang-sering-muncul)

---

## Gambaran Umum

**ACS Core** adalah server TR-069 Auto Configuration Server (ACS) dengan dashboard Laravel keren buat manage perangkat CPE kayak router MikroTik, ONT, dan lain-lain.

### Arsitektur Sistem

```
┌─────────────────┐      TR-069/CWMP      ┌──────────────────┐
│  Perangkat CPE  │◄────────────────────►│  ACS Server      │
│  (MikroTik,     │      Port 7547        │  (Node.js)       │
│   ONT, dll)     │                       │                  │
└─────────────────┘                       └────────┬─────────┘
                                                   │
                                                   │ HTTP API
                                                   │
                                          ┌────────▼─────────┐
                                          │  Laravel App     │
                                          │  Port 8000       │
                                          │  (Dashboard)     │
                                          └──────────────────┘
                                                   │
                                                   │
                                          ┌────────▼─────────┐
                                          │  SQLite Database │
                                          │  (acs.db)        │
                                          └──────────────────┘
```

---

## Yang Harus Disiapkan

### Spesifikasi Minimum

- **Node.js**: Versi 16.x ke atas
- **PHP**: Versi 8.1 ke atas
- **Composer**: Versi terbaru
- **SQLite**: Versi 3.x ke atas
- **RAM**: 2GB minimal (4GB lebih nyaman)
- **Storage**: 500MB kosong

### Tool yang Dibutuhkan per Platform

**Windows:**
- Laragon (recommended) ATAU XAMPP/WAMP
- Git for Windows

**WSL/Ubuntu:**
- Ubuntu 20.04 LTS atau lebih baru
- systemd (buat manage service)

---

## Instalasi: Windows (Laragon)

### Langkah 1: Install Laragon

1. Download Laragon dari: https://laragon.org/download/
2. Install Laragon Full (udah include PHP, Apache, MySQL)
3. Jalankan Laragon

### Langkah 2: Install Node.js

1. Download Node.js LTS dari: https://nodejs.org/
2. Install dengan setting default aja
3. Cek udah terinstall belum:
```bash
node --version
npm --version
```

### Langkah 3: Clone Project

```bash
# Masuk ke folder Laragon www
cd C:\laragon\www

# Clone repository
git clone <repository-url> acs-core

# Atau kalau udah punya file projectnya, copy aja ke:
# C:\laragon\www\acs-core\
```

### Langkah 4: Install Dependencies

```bash
cd C:\laragon\www\acs-core

# Install package Node.js
npm install

# Install package Laravel
cd acs-laravel
composer install
npm install
```

### Langkah 5: Setting Environment

```bash
cd C:\laragon\www\acs-core\acs-laravel

# Copy file environment
copy .env.example .env

# Generate application key
php artisan key:generate
```

Edit file `.env`:
```env
APP_NAME="ACS Core"
APP_URL=http://localhost:8000
DB_DATABASE=../../acs.db
ACS_API_URL=http://localhost:7547/api
```

### Langkah 6: Setup Database

```bash
# Dari folder acs-laravel
php artisan migrate --seed
```

### Langkah 7: Build Assets Frontend

```bash
npm run build
```

### Langkah 8: Jalankan Server

**Terminal 1 - ACS Server:**
```bash
cd C:\laragon\www\acs-core
npm start
```

**Terminal 2 - Laravel App:**
```bash
cd C:\laragon\www\acs-core\acs-laravel
php artisan serve
```

✅ **Selesai!** Buka dashboard di: http://localhost:8000

---

## Instalasi: WSL (Ubuntu di Windows)

### Langkah 1: Aktifkan WSL

```powershell
# Jalankan di PowerShell sebagai Administrator
wsl --install

# Restart komputer
# Buka Ubuntu dari Start Menu
```

### Langkah 2: Update System

```bash
sudo apt update && sudo apt upgrade -y
```

### Langkah 3: Install Dependencies

```bash
# Install Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install PHP 8.1
sudo apt install -y php8.1 php8.1-cli php8.1-mbstring php8.1-xml \
  php8.1-curl php8.1-sqlite3 php8.1-zip php8.1-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Git dan SQLite
sudo apt install -y git sqlite3

# Cek sudah terinstall
node --version
php --version
composer --version
```

### Langkah 4: Clone dan Setup Project

```bash
# Masuk ke home directory atau folder yang diinginkan
cd ~

# Clone project
git clone <repository-url> acs-core
cd acs-core

# Install dependencies
npm install

cd acs-laravel
composer install
npm install
```

### Langkah 5: Setting Environment

```bash
cd ~/acs-core/acs-laravel

# Copy dan edit .env
cp .env.example .env
nano .env
```

Ubah di `.env`:
```env
APP_URL=http://localhost:8000
DB_DATABASE=../../acs.db
ACS_API_URL=http://localhost:7547/api
```

Generate key:
```bash
php artisan key:generate
```

### Langkah 6: Database & Assets

```bash
php artisan migrate --seed
npm run build
```

### Langkah 7: Jalankan Server

**Terminal 1:**
```bash
cd ~/acs-core
npm start
```

**Terminal 2:**
```bash
cd ~/acs-core/acs-laravel
php artisan serve
```

### Langkah 8: Akses dari Windows

Dari browser Windows, buka:
- Dashboard: http://localhost:8000
- ACS Server: http://localhost:7547

> **Note:** Kalau portnya gak bisa diakses, cek port forwarding WSL:
> ```powershell
> netsh interface portproxy add v4tov4 listenport=8000 listenaddress=0.0.0.0 connectport=8000 connectaddress=$(wsl hostname -I)
> ```

---

## Instalasi: Ubuntu (Asli)

### Langkah 1: Update System

```bash
sudo apt update && sudo apt upgrade -y
```

### Langkah 2: Install Dependencies

```bash
# Install Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install PHP 8.1 dan ekstensinya
sudo apt install -y php8.1 php8.1-cli php8.1-fpm php8.1-mbstring \
  php8.1-xml php8.1-curl php8.1-sqlite3 php8.1-zip php8.1-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Git dan SQLite
sudo apt install -y git sqlite3

# Cek
node --version
php --version
composer --version
```

### Langkah 3: Clone Project

```bash
# Clone ke /var/www atau home directory
sudo mkdir -p /var/www
cd /var/www
sudo git clone <repository-url> acs-core
sudo chown -R $USER:$USER acs-core
cd acs-core
```

### Langkah 4: Install Dependencies

```bash
# Install package Node.js
npm install

# Install package Laravel
cd acs-laravel
composer install
npm install
```

### Langkah 5: Setting Environment

```bash
cp .env.example .env
nano .env
```

Ubah di `.env`:
```env
APP_URL=http://localhost:8000
DB_DATABASE=../../acs.db
ACS_API_URL=http://localhost:7547/api
```

```bash
php artisan key:generate
```

### Langkah 6: Database & Assets

```bash
php artisan migrate --seed
npm run build
```

### Langkah 7: Jalankan Server

**Untuk Development (pakai Terminal):**

Terminal 1:
```bash
cd /var/www/acs-core
npm start
```

Terminal 2:
```bash
cd /var/www/acs-core/acs-laravel
php artisan serve
```

**Untuk Production (pakai systemd service):**

Buat file `/etc/systemd/system/acs-server.service`:
```ini
[Unit]
Description=TR-069 ACS Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/acs-core
ExecStart=/usr/bin/node src/index.mjs
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Buat file `/etc/systemd/system/acs-laravel.service`:
```ini
[Unit]
Description=ACS Laravel Application
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/acs-core/acs-laravel
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8000
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Aktifkan dan jalankan service:
```bash
sudo systemctl daemon-reload
sudo systemctl enable acs-server acs-laravel
sudo systemctl start acs-server acs-laravel
sudo systemctl status acs-server acs-laravel
```

---

## Setup Setelah Instalasi

### 1. Buat User Admin

```bash
cd acs-laravel
php artisan tinker
```

Di tinker, ketik:
```php
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@example.com';
$user->password = Hash::make('password');
$user->role = 'super_admin';
$user->save();
exit
```

### 2. Login ke Dashboard

1. Buka: http://localhost:8000
2. Login pakai:
   - Email: admin@example.com
   - Password: password

### 3. Setting MikroTik

Di setiap router MikroTik:

```bash
# Aktifkan TR-069 client
/tr069-client set enabled=yes

# Set ACS URL (ganti SERVER_IP dengan IP server kamu)
/tr069-client set acs-url=http://SERVER_IP:7547/acs

# Aktifkan periodic inform
/tr069-client set periodic-inform-enabled=yes
/tr069-client set periodic-inform-interval=5m

# Set username password
/tr069-client set username=admin password=admin

# Cek settingnya
/tr069-client print
```

### 4. Cek Koneksi Device

Liat di log ACS server, harusnya ada:
```
[TIMESTAMP] POST /acs
Method: Inform Header ID: null From: DEVICE_IP
✓ Device DEVICE_ID updated
```

---

## Cek Apakah Berhasil

### Health Check

**1. ACS Server:**
```bash
curl http://localhost:7547/health
# Harusnya muncul: {"status":"ok","timestamp":"..."}
```

**2. ACS API:**
```bash
curl http://localhost:7547/api/devices
# Harusnya muncul: JSON array berisi device
```

**3. Laravel App:**
```bash
curl http://localhost:8000
# Harusnya muncul: HTML halaman login
```

**4. Database:**
```bash
cd acs-core
sqlite3 acs.db "SELECT COUNT(*) FROM devices;"
# Harusnya muncul: Jumlah device yang terdaftar
```

### Checklist Instalasi

- [ ] ACS server jalan di port 7547
- [ ] Laravel app jalan di port 8000
- [ ] Bisa buka dashboard di http://localhost:8000
- [ ] Bisa login dengan user admin
- [ ] File database `acs.db` sudah ada
- [ ] MikroTik device muncul di dashboard setelah connect
- [ ] Status device "Online" dengan badge hijau
- [ ] Gak ada error di log server

---

## Masalah yang Sering Muncul

### Port Sudah Dipakai

**Gejala:** `Error: listen EADDRINUSE: address already in use :::7547`

**Solusi:**
```bash
# Windows
netstat -ano | findstr :7547
taskkill /PID <PID> /F

# Linux/WSL
sudo lsof -i :7547
kill -9 <PID>
```

### Database Terkunci

**Gejala:** `SQLITE_BUSY: database is locked`

**Solusi:**
```bash
# Stop semua proses yang pakai database
# Restart ACS server
cd acs-core
npm start
```

### Permission Denied (Ubuntu)

**Gejala:** `Error: EACCES: permission denied`

**Solusi:**
```bash
sudo chown -R $USER:$USER /var/www/acs-core
chmod -R 755 /var/www/acs-core
```

### MikroTik Offline Terus

**Solusi:** Lihat [MIKROTIK_OFFLINE_FIX.md](MIKROTIK_OFFLINE_FIX.md)

### Gak Bisa Buka Dashboard

**Cek:**
1. Laravel server jalan: `php artisan serve`
2. Cek file `.env`: `APP_URL=http://localhost:8000`
3. Clear cache: `php artisan config:clear`

---

## Langkah Selanjutnya

- 📖 [Panduan Setup MikroTik](MIKROTIK_SETUP.md) - Konfigurasi TR-069 lengkap
- 🔧 [Troubleshooting](TROUBLESHOOTING.md) - Solusi masalah umum
- 📡 [Fix MikroTik Offline](MIKROTIK_OFFLINE_FIX.md) - Kalau device offline
- 🗂️ [Referensi DASAN OID](DASAN_OID_REFERENCE.md) - SNMP OID buat DASAN

---

## Bantuan

Kalau ada masalah atau pertanyaan:
1. Cek [Troubleshooting](TROUBLESHOOTING.md)
2. Lihat log error di server
3. Cek konfigurasi di MikroTik

**Lokasi Log:**
- ACS Server: Output console dari `npm start`
- Laravel App: `acs-laravel/storage/logs/laravel.log`

---

**Versi:** 2.0.0  
**Update Terakhir:** 2025-11-24
