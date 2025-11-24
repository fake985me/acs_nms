# Panduan Konfigurasi MikroTik TR-069 ke ACS

## Informasi Server ACS Anda
- **ACS URL**: `http://172.24.128.1:7547/acs`
- **Port**: 7547
- **IP Server**: 172.24.128.1

## Langkah Konfigurasi MikroTik

### Method 1: Via Winbox/WebFig

1. **Buka Menu TR-069 Client**
   ```
   System → TR-069 Client
   ```

2. **Aktifkan TR-069 Client**
   - Centang "Enabled"
   
3. **Konfigurasi ACS URL**
   ```
   ACS URL: http://172.24.128.1:7547/acs
   ```

4. **Username & Password** (opsional, kosongkan jika ACS tidak pakai auth)
   ```
   Username: (kosongkan)
   Password: (kosongkan)
   ```

5. **Periodic Inform**
   - Centang "Periodic Inform Enabled"
   - Periodic Inform Interval: 300 (5 menit)

6. **Klik Apply & OK**

### Method 2: Via Terminal/SSH

Jalankan command berikut di MikroTik terminal:

```bash
/tr069-client
set enabled=yes \
    acs-url=http://172.24.128.1:7547/acs \
    periodic-inform-enabled=yes \
    periodic-inform-interval=5m \
    username="" \
    password=""

# Restart TR-069 client
/tr069-client restart
```

## Verifikasi Koneksi

### 1. Cek Status di MikroTik
```bash
/tr069-client print
```

Output seharusnya menunjukkan:
- `enabled: yes`
- `acs-url: http://172.24.128.1:7547/acs`
- `periodic-inform-enabled: yes`

### 2. Lihat Log MikroTik
```bash
/log print where topics~"tr069"
```

Cari pesan seperti:
- "TR069: connected to ACS"
- "TR069: inform sent"

### 3. Force Connection Test
```bash
# Force MikroTik untuk segera kontak ACS
/tr069-client force-connect
```

## Troubleshooting

### Problem: "Connection timeout" atau "Connection refused"

**Solusi:**
1. **Pastikan firewall tidak memblokir**
   ```bash
   # Lihat firewall rules
   /ip firewall filter print
   
   # Tambahkan rule jika perlu untuk allow port 7547
   /ip firewall filter add chain=output protocol=tcp dst-port=7547 action=accept place-before=0
   ```

2. **Test koneksi dari MikroTik**
   ```bash
   /tool fetch url=http://172.24.128.1:7547/health
   ```
   
   Seharusnya mengembalikan response dari ACS server.

### Problem: IP Server berbeda

Jika IP server Anda bukan `172.24.128.1`, ganti dengan IP yang benar:

```bash
# Cek IP server yang benar
ipconfig
```

Lalu update ACS URL:
```bash
/tr069-client set acs-url=http://IP_ANDA:7547/acs
```

### Problem: MikroTik di balik NAT/Router

Jika MikroTik dan Server ACS terpisah oleh router:
1. Pastikan ada route antara MikroTik dan Server
2. Gunakan IP Public atau setup port forwarding
3. Test ping dari MikroTik ke server:
   ```bash
   /ping 172.24.128.1
   ```

## Cek di ACS Server

Setelah konfigurasi, cek apakah device muncul:

1. **Via Browser**: http://localhost:8000/devices
2. **Via API**:
   ```powershell
   curl http://localhost:7547/api/devices
   ```

Device seharusnya muncul dengan informasi:
- Device ID (format: OUI-ProductClass-SerialNumber)
- Manufacturer: MikroTik
- Model Name
- IP Address
- Last Inform time

## Debug Mode (Advanced)

Jika masih tidak muncul, aktifkan debug logging di MikroTik:

```bash
/system logging add topics=tr069,debug action=memory
/log print where topics~"tr069"
```

Ini akan menampilkan detail komunikasi antara MikroTik dan ACS.
