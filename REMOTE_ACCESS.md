# Panduan Remote Access ACS Core

Panduan lengkap untuk akses ACS Core dari PC lain dalam jaringan lokal (LAN) atau melalui VPN. Gampang kok!

## Daftar Isi

- [Gambaran Umum](#gambaran-umum)
- [Remote Access di LAN](#remote-access-di-lan)
- [Remote Access via VPN](#remote-access-via-vpn)
- [Setup untuk MikroTik Remote](#setup-untuk-mikrotik-remote)
- [Troubleshooting](#troubleshooting)

---

## Gambaran Umum

**Default Setup (Development):**
- Laravel: Hanya bisa diakses dari `localhost` (127.0.0.1)
- ACS Server: Sudah bind ke `0.0.0.0` (bisa diakses dari mana saja)
- Firewall: Mungkin masih block port

**Yang Perlu Diubah:**
1. ✅ Bind Laravel ke semua network interface
2. ✅ Buka Windows Firewall
3. ✅ Update `.env` kalau perlu
4. ✅ Setting MikroTik untuk connect ke IP server yang benar

---

## Remote Access di LAN

Akses dari PC lain dalam satu jaringan lokal (WiFi/LAN yang sama).

### Langkah 1: Cek IP Server

**Windows:**
```powershell
ipconfig
```

Cari IP Address yang **satu network** dengan PC client:
- WiFi: Biasanya `192.168.x.x` atau `10.0.x.x`
- LAN Cable: Tergantung network kamu
- **Contoh:** `192.168.1.5`

**WSL/Ubuntu:**
```bash
ip addr show
# atau
hostname -I
```

### Langkah 2: Restart Laravel dengan Host 0.0.0.0

**Stop server yang sekarang:**
```bash
# Di terminal Laravel, tekan Ctrl+C
```

**Start ulang dengan bind ke semua interface:**
```bash
cd C:\laragon\www\acs-core\acs-laravel
php artisan serve --host=0.0.0.0 --port=8000
```

Sekarang Laravel bisa diakses dari: `http://IP_SERVER:8000`

> **ACS Server** sudah otomatis bind ke `0.0.0.0:7547`, gak perlu diubah!

### Langkah 3: Buka Windows Firewall

**Option A: Via PowerShell (Cepat!)**

```powershell
# Jalankan sebagai Administrator
# Buka port Laravel (8000)
netsh advfirewall firewall add rule name="Laravel Dev Server" dir=in action=allow protocol=TCP localport=8000

# Buka port ACS Server (7547)
netsh advfirewall firewall add rule name="ACS TR-069 Server" dir=in action=allow protocol=TCP localport=7547

# Cek rule sudah dibuat
netsh advfirewall firewall show rule name="Laravel Dev Server"
netsh advfirewall firewall show rule name="ACS TR-069 Server"
```

**Option B: Via GUI (Kalau suka klik-klik)**

1. Buka `Windows Defender Firewall with Advanced Security`
2. Klik **Inbound Rules** → **New Rule**
3. Rule Type: **Port** → Next
4. Protocol: **TCP**, Port: **8000** → Next
5. Action: **Allow the connection** → Next
6. Profile: Centang **semua** → Next
7. Name: `Laravel Dev Server` → Finish
8. **Ulangi** untuk port **7547** dengan name `ACS TR-069 Server`

### Langkah 4: Update .env (Optional tapi Recommended)

Edit `acs-laravel/.env`:
```env
# Ganti localhost dengan IP server kamu
APP_URL=http://192.168.1.5:8000

# Kalau pakai domain local
# APP_URL=http://acs-server.local:8000
```

Restart Laravel setelah edit `.env`:
```bash
# Ctrl+C, terus
php artisan serve --host=0.0.0.0 --port=8000
```

### Langkah 5: Test dari PC Client

**Dari browser PC client (dalam LAN yang sama):**
```
http://192.168.1.5:8000        # Dashboard
http://192.168.1.5:7547/health # ACS Server health check
```

**Dari terminal PC client:**
```bash
# Windows
curl http://192.168.1.5:8000
curl http://192.168.1.5:7547/health

# Linux/Mac
curl http://192.168.1.5:8000
curl http://192.168.1.5:7547/health
```

Kalau muncul response, berarti **SUKSES!** ✅

---

## Remote Access via VPN

Akses dari luar jaringan lokal melalui VPN (Tailscale, WireGuard, OpenVPN, dll).

### Skenario Umum

```
[PC Client] --VPN--> [Server ACS]
                        ├─ 192.168.1.5 (LAN IP)
                        └─ 10.100.0.5 (VPN IP)
```

### Langkah 1: Setup VPN di Server

**Pilihan VPN (salah satu):**
- **Tailscale** (Paling mudah! Recommended)
- WireGuard
- OpenVPN
- ZeroTier

**Contoh: Tailscale (Gratis untuk personal use)**

1. Install Tailscale di server: https://tailscale.com/download
2. Login dengan akun Google/GitHub
3. Server otomatis dapat VPN IP (misal `100.x.x.x`)

### Langkah 2: Setup VPN di PC Client

1. Install Tailscale di PC client
2. Login dengan akun yang sama
3. PC client otomatis connect ke VPN network

### Langkah 3: Cek IP VPN Server

**Windows:**
```powershell
# Cek adapter Tailscale
ipconfig | findstr "Tailscale"
# IP akan seperti: 100.x.x.x
```

**Linux/WSL:**
```bash
tailscale ip -4
```

### Langkah 4: Firewall Allow dari VPN Interface

```powershell
# Buka PowerShell sebagai Administrator
# Allow dari VPN subnet (adjust sesuai VPN kamu)
netsh advfirewall firewall add rule name="Laravel from VPN" dir=in action=allow protocol=TCP localport=8000 remoteip=100.0.0.0/8

netsh advfirewall firewall add rule name="ACS from VPN" dir=in action=allow protocol=TCP localport=7547 remoteip=100.0.0.0/8
```

> **Note:** `100.0.0.0/8` adalah subnet Tailscale. Untuk VPN lain, sesuaikan subnet-nya.

### Langkah 5: Test dari PC Client (via VPN)

**Dari browser PC client:**
```
http://100.x.x.x:8000        # Dashboard (pakai IP VPN server)
http://100.x.x.x:7547/health # ACS health check
```

### Setup dengan Domain (Optional)

Kalau punya domain atau subdomain:

**Update DNS:**
```
acs.yourdomain.com → 100.x.x.x (VPN IP)
```

**Update .env:**
```env
APP_URL=http://acs.yourdomain.com:8000
```

**Untuk production, pakai SSL:**
Lihat [DEPLOYMENT.md](DEPLOYMENT.md) section SSL/HTTPS.

---

## Setup untuk MikroTik Remote

MikroTik device yang jauh dari server perlu connect via internet atau VPN.

### Opsi 1: MikroTik Connect via Public IP (Perlu Port Forwarding)

**Di Router Internet:**
1. Forward port **7547** ke server lokal
2. Setup Dynamic DNS (kalau IP public dynamic)

**Di MikroTik:**
```bash
/tr069-client set acs-url=http://YOUR_PUBLIC_IP:7547/acs
# atau pakai domain
/tr069-client set acs-url=http://acs.yourdomain.com:7547/acs
```

### Opsi 2: MikroTik Connect via VPN (Recommended)

**Setup VPN di MikroTik:**

**Jika pakai WireGuard:**
```bash
# Di MikroTik, setup WireGuard client
/interface wireguard
add listen-port=13231 mtu=1420 name=wireguard1 private-key="PRIVATE_KEY_HERE"

/interface wireguard peers
add allowed-address=0.0.0.0/0 endpoint-address=VPN_SERVER_IP endpoint-port=51820 interface=wireguard1 public-key="PUBLIC_KEY_HERE"

# Setelah VPN connect, set ACS URL ke IP VPN server
/tr069-client set acs-url=http://100.x.x.x:7547/acs
```

**Jika pakai Tailscale di MikroTik:**

MikroTik belum support Tailscale native, tapi bisa pakai container atau router lain sebagai gateway.

### Opsi 3: MikroTik Connect via L2TP/IPsec VPN

```bash
# Setup L2TP client di MikroTik
/interface l2tp-client
add connect-to=VPN_SERVER_IP disabled=no name=l2tp-out1 password=PASSWORD user=USERNAME

# Tunggu connect, terus set ACS URL
/tr069-client set acs-url=http://VPN_SERVER_IP:7547/acs
```

### Verifikasi Koneksi MikroTik

```bash
# Test ping ke ACS server
/ping 192.168.1.5 count=5

# Test HTTP fetch
/tool fetch url="http://192.168.1.5:7547/health" mode=http

# Monitor TR-069 log
/log print follow-only where topics~"tr069"
```

---

## Troubleshooting

### PC Client Tidak Bisa Connect

**1. Cek bisa ping server:**
```bash
ping 192.168.1.5
```

Kalau **Request timeout**:
- Pastikan server dan client dalam satu network
- Cek firewall allow ICMP (ping)

**2. Cek port terbuka:**
```bash
# Windows (dari server)
netstat -an | findstr :8000
netstat -an | findstr :7547

# Harus muncul 0.0.0.0:8000 LISTENING
```

**3. Cek firewall rules:**
```powershell
# List semua inbound rules untuk port 8000
netsh advfirewall firewall show rule name=all | findstr "8000"
```

### Laravel Tetap Localhost Only

**Symptoms:** Akses dari IP lain gagal, tapi localhost:8000 jalan.

**Solution:**
```bash
# HARUS pakai --host=0.0.0.0
php artisan serve --host=0.0.0.0 --port=8000

# JANGAN cuma:
php artisan serve  # ❌ Ini cuma bind ke 127.0.0.1
```

### Connection Refused dari VPN

**Cek routing:**
```bash
# Di server, cek route ke VPN subnet
route print

# Di client VPN, cek bisa ping server VPN IP
ping 100.x.x.x
```

**Cek firewall allow dari VPN IP:**
```powershell
# Test dengan temporarily disable firewall
netsh advfirewall set allprofiles state off

# Kalau jalan, berarti firewall block
# Enable ulang
netsh advfirewall set allprofiles state on

# Tambah rule untuk VPN subnet
```

### MikroTik Connection Timeout

**Cek dari MikroTik:**
```bash
# Test connectivity
/tool fetch url="http://SERVER_IP:7547/health" mode=http

# Jika gagal, cek:
# 1. Routing - bisa reach server IP?
# 2. Firewall - ada yang block port 7547?
# 3. ACS URL benar?
```

**Cek di server:**
```bash
# Monitor incoming connections
# Di terminal ACS server, lihat log POST /acs
```

---

## Production Setup untuk Remote Access

Untuk production yang proper, ikuti **DEPLOYMENT.md**:

**Windows:**
- Pakai NSSM untuk service (auto-start)
- Pakai IIS/Apache (lebih robust)
- Setup SSL certificate

**Ubuntu:**
- Pakai systemd service
- Pakai nginx sebagai reverse proxy
- Setup Let's Encrypt SSL
- Setup firewall dengan UFW

**Keamanan:**
- Jangan expose port development ke internet
- Pakai VPN untuk remote access
- Pakai SSL/HTTPS
- Batas IP yang bisa access (firewall rules)
- Ganti default credentials

---

## Checklist Remote Access

### Development (LAN)
- [ ] Server bind ke 0.0.0.0 (`--host=0.0.0.0`)
- [ ] Firewall allow port 8000 dan 7547
- [ ] .env APP_URL updated
- [ ] Test dari PC client dalam LAN
- [ ] Test login dashboard
- [ ] MikroTik di LAN bisa connect

### VPN Access
- [ ] VPN server setup dan running
- [ ] VPN client connected
- [ ] Firewall allow dari VPN subnet
- [ ] Test akses via VPN IP
- [ ] MikroTik via VPN bisa connect
- [ ] Monitor bandwidth usage

### Production
- [ ] Follow DEPLOYMENT.md
- [ ] SSL certificate installed
- [ ] Domain DNS configured
- [ ] Monitoring setup
- [ ] Backup procedure ready
- [ ] Security hardening complete

---

## Quick Reference

**Start Laravel untuk Remote Access:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Buka Firewall:**
```powershell
netsh advfirewall firewall add rule name="Laravel Dev" dir=in action=allow protocol=TCP localport=8000
netsh advfirewall firewall add rule name="ACS Server" dir=in action=allow protocol=TCP localport=7547
```

**Test Connection:**
```bash
curl http://SERVER_IP:8000
curl http://SERVER_IP:7547/health
```

**MikroTik ACS URL:**
```bash
/tr069-client set acs-url=http://SERVER_IP:7547/acs
```

---

**Selamat remote access!** 🌐

Untuk production deployment yang proper, lihat [DEPLOYMENT.md](DEPLOYMENT.md)
