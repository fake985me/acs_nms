# Panduan Deploy Produksi ACS Core

Panduan lengkap deployment ACS Core ke production server. Santai tapi serius ya!

## Daftar Isi

- [Persiapan Deployment](#persiapan-deployment)
- [Deploy di Windows](#deploy-di-windows)
- [Deploy di WSL](#deploy-di-wsl)
- [Deploy di Ubuntu](#deploy-di-ubuntu)
- [Setup SSL/HTTPS](#setup-sslhttps)
- [Monitoring & Logging](#monitoring--logging)
- [Backup & Recovery](#backup--recovery)
- [Best Practices](#best-practices)

---

## Persiapan Deployment

### Checklist Sebelum Deploy

- [ ] Source code sudah final dan tested
- [ ] Database sudah di-backup
- [ ] Environment variables sudah disiapkan
- [ ] Domain/subdomain sudah pointing (kalau pakai domain)
- [ ] SSL certificate sudah ready (kalau pakai HTTPS)
- [ ] Firewall rules sudah disiapkan
- [ ] User admin sudah dibuat

### Perbedaan Development vs Production

| Aspek | Development | Production |
|-------|-------------|------------|
| Server | `php artisan serve` | Nginx/Apache + PHP-FPM |
| Process | Manual start (terminal) | Service/Daemon (auto-start) |
| Error Display | Full error messages | Generic error page |
| Logging | Console output | File logs |
| Debug Mode | `APP_DEBUG=true` | `APP_DEBUG=false` |
| Cache | Disabled | Enabled |
| Asset | Real-time compile | Pre-compiled |

---

## Deploy di Windows

### Opsi 1: Pakai NSSM (Recommended)

NSSM (Non-Sucking Service Manager) bikin aplikasi Node.js jadi Windows Service.

#### Install NSSM

1. Download NSSM: https://nssm.cc/download
2. Extract ke `C:\nssm\`
3. Tambah ke PATH atau pakai full path

#### Setup ACS Server Service

```powershell
# Buka PowerShell sebagai Administrator
cd C:\nssm\win64

# Install service ACS Server
.\nssm.exe install ACSServer "C:\Program Files\nodejs\node.exe" "C:\laragon\www\acs-core\src\index.mjs"

# Set working directory
.\nssm.exe set ACSServer AppDirectory "C:\laragon\www\acs-core"

# Set startup type
.\nssm.exe set ACSServer Start SERVICE_AUTO_START

# Set output log
.\nssm.exe set ACSServer AppStdout "C:\laragon\www\acs-core\logs\acs-server.log"
.\nssm.exe set ACSServer AppStderr "C:\laragon\www\acs-core\logs\acs-server-error.log"

# Start service
.\nssm.exe start ACSServer
```

#### Setup Laravel dengan IIS atau Apache

**Option A: Pakai IIS**

1. Install IIS dari Windows Features
2. Install PHP Manager for IIS
3. Configure site:
   - Physical path: `C:\laragon\www\acs-core\acs-laravel\public`
   - Binding: Port 8000 (atau sesuai kebutuhan)
4. Set permissions untuk folder `storage` dan `bootstrap/cache`

**Option B: Pakai Apache (Laragon)**

Edit `httpd-vhosts.conf`:
```apache
<VirtualHost *:8000>
    DocumentRoot "C:/laragon/www/acs-core/acs-laravel/public"
    ServerName acs-core.local
    
    <Directory "C:/laragon/www/acs-core/acs-laravel/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Restart Apache dari Laragon.

#### Manage Services

```powershell
# Cek status
sc query ACSServer

# Stop service
sc stop ACSServer

# Start service
sc start ACSServer

# Restart (stop dulu, terus start)
sc stop ACSServer
sc start ACSServer
```

### Opsi 2: Pakai PM2 (Alternative)

PM2 juga bisa di Windows, tapi NSSM lebih native.

```bash
# Install PM2 globally
npm install -g pm2

# Start ACS Server
cd C:\laragon\www\acs-core
pm2 start src/index.mjs --name acs-server

# Save config supaya auto-start
pm2 save
pm2 startup
```

---

## Deploy di WSL

WSL bisa pakai systemd tapi ada beberapa catatan khusus.

### Enable systemd di WSL

Edit `/etc/wsl.conf`:
```ini
[boot]
systemd=true
```

Restart WSL:
```powershell
wsl --shutdown
```

### Setup systemd Services

**ACS Server Service:**

Buat `/etc/systemd/system/acs-server.service`:
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
StandardOutput=append:/home/YOUR_USERNAME/acs-core/logs/acs-server.log
StandardError=append:/home/YOUR_USERNAME/acs-core/logs/acs-server-error.log

[Install]
WantedBy=multi-user.target
```

**Laravel dengan PHP-FPM:**

```bash
# Install PHP-FPM
sudo apt install php8.1-fpm

# Configure nginx (lihat section Ubuntu)
```

### Manage Services

```bash
# Enable services
sudo systemctl enable acs-server

# Start services
sudo systemctl start acs-server

# Check status
sudo systemctl status acs-server

# View logs
journalctl -u acs-server -f
```

### Port Forwarding ke Windows

Kalau mau access dari Windows host:

```powershell
# Di PowerShell Windows (Administrator)
netsh interface portproxy add v4tov4 listenport=7547 listenaddress=0.0.0.0 connectport=7547 connectaddress=$(wsl hostname -I)
netsh interface portproxy add v4tov4 listenport=8000 listenaddress=0.0.0.0 connectport=8000 connectaddress=$(wsl hostname -I)

# Cek port forwarding
netsh interface portproxy show all
```

---

## Deploy di Ubuntu

Ini deployment paling proper untuk production!

### Persiapan

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies (kalau belum)
# Lihat INSTALLATION.md untuk detail
```

### Setup systemd Services

**1. ACS Server Service**

Buat `/etc/systemd/system/acs-server.service`:
```ini
[Unit]
Description=TR-069 ACS Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/acs-core
ExecStart=/usr/bin/node src/index.mjs
Restart=always
RestartSec=10
StandardOutput=append:/var/log/acs-server/access.log
StandardError=append:/var/log/acs-server/error.log

# Environment
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
```

Buat folder log:
```bash
sudo mkdir -p /var/log/acs-server
sudo chown www-data:www-data /var/log/acs-server
```

**2. Setup Laravel dengan Nginx**

Install Nginx dan PHP-FPM:
```bash
sudo apt install nginx php8.1-fpm
```

Buat config Nginx `/etc/nginx/sites-available/acs-core`:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;  # Ganti dengan domain kamu
    root /var/www/acs-core/acs-laravel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# ACS Server reverse proxy (optional - kalau mau pakai domain untuk ACS juga)
server {
    listen 7547;
    server_name your-domain.com;

    location / {
        proxy_pass http://localhost:7547;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

Aktifkan site:
```bash
# Link ke sites-enabled
sudo ln -s /etc/nginx/sites-available/acs-core /etc/nginx/sites-enabled/

# Test config
sudo nginx -t

# Restart nginx
sudo systemctl restart nginx
```

### Setup Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/acs-core

# Set permissions
sudo chmod -R 755 /var/www/acs-core
sudo chmod -R 775 /var/www/acs-core/acs-laravel/storage
sudo chmod -R 775 /var/www/acs-core/acs-laravel/bootstrap/cache
```

### Production Environment Setup

Edit `.env` di `acs-laravel/`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_DATABASE=/var/www/acs-core/acs.db

ACS_API_URL=http://localhost:7547/api
```

Optimize Laravel:
```bash
cd /var/www/acs-core/acs-laravel

# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize composer
composer install --optimize-autoloader --no-dev
```

### Enable dan Start Services

```bash
# Enable services untuk auto-start
sudo systemctl enable acs-server
sudo systemctl enable nginx
sudo systemctl enable php8.1-fpm

# Start services
sudo systemctl start acs-server
sudo systemctl start nginx
sudo systemctl start php8.1-fpm

# Check status
sudo systemctl status acs-server
sudo systemctl status nginx
```

---

## Setup SSL/HTTPS

### Ubuntu dengan Let's Encrypt (Gratis!)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Generate certificate (otomatis configure nginx juga)
sudo certbot --nginx -d your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

Certbot otomatis update config nginx jadi HTTPS dan setup auto-renewal!

### Windows dengan Self-Signed Certificate

Atau pakai reverse proxy seperti Caddy yang otomatis handle SSL.

---

## Monitoring & Logging

### Check Logs

**Ubuntu/WSL:**
```bash
# ACS Server logs
sudo journalctl -u acs-server -f

# Atau kalau pakai file log
tail -f /var/log/acs-server/access.log
tail -f /var/log/acs-server/error.log

# Laravel logs
tail -f /var/www/acs-core/acs-laravel/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

**Windows:**
```powershell
# NSSM logs
type C:\laragon\www\acs-core\logs\acs-server.log

# Laravel logs
type C:\laragon\www\acs-core\acs-laravel\storage\logs\laravel.log
```

### Setup Log Rotation (Ubuntu)

Buat `/etc/logrotate.d/acs-core`:
```
/var/log/acs-server/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}

/var/www/acs-core/acs-laravel/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}
```

### Monitoring with systemd

Bikin script untuk cek health dan restart kalau mati.

Buat `/usr/local/bin/check-acs-health.sh`:
```bash
#!/bin/bash

# Check ACS server health
if ! curl -f http://localhost:7547/health > /dev/null 2>&1; then
    echo "ACS Server down, restarting..."
    systemctl restart acs-server
fi
```

Bikin executable dan tambah ke crontab:
```bash
sudo chmod +x /usr/local/bin/check-acs-health.sh

# Edit crontab
sudo crontab -e

# Tambah baris ini (check tiap 5 menit)
*/5 * * * * /usr/local/bin/check-acs-health.sh
```

---

## Backup & Recovery

### Backup Database

```bash
# Manual backup
cp /var/www/acs-core/acs.db /backup/acs-$(date +%Y%m%d).db

# Automated backup script
cat > /usr/local/bin/backup-acs.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/backup/acs"
DATE=$(date +%Y%m%d-%H%M%S)
mkdir -p $BACKUP_DIR
cp /var/www/acs-core/acs.db $BACKUP_DIR/acs-$DATE.db
# Keep only last 30 backups
ls -t $BACKUP_DIR/acs-*.db | tail -n +31 | xargs rm -f
EOF

chmod +x /usr/local/bin/backup-acs.sh

# Tambah ke crontab (backup tiap hari jam 2 pagi)
0 2 * * * /usr/local/bin/backup-acs.sh
```

### Restore Database

```bash
# Stop services
sudo systemctl stop acs-server

# Restore database
cp /backup/acs-20251124.db /var/www/acs-core/acs.db

# Fix permissions
sudo chown www-data:www-data /var/www/acs-core/acs.db

# Start services
sudo systemctl start acs-server
```

---

## Best Practices

### Security

1. **Firewall Configuration**
```bash
# Ubuntu - allow only necessary ports
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP
sudo ufw allow 443/tcp     # HTTPS
sudo ufw allow 7547/tcp    # ACS Server
sudo ufw enable
```

2. **Change Default Credentials**
   - Ganti password admin dashboard
   - Ganti TR-069 username/password

3. **Regular Updates**
```bash
# Update dependencies
npm audit fix
composer update

# Update system
sudo apt update && sudo apt upgrade
```

### Performance Optimization

1. **Enable OPcache (PHP)**
Edit `/etc/php/8.1/fpm/php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

2. **Laravel Optimization**
```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. **Nginx Caching**
Tambah di config nginx:
```nginx
# Cache static files
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### Monitoring Checklist

- [ ] Setup email alerts untuk service down
- [ ] Monitor disk space (database growth)
- [ ] Monitor CPU/RAM usage
- [ ] Check error logs daily
- [ ] Monitor active device count
- [ ] Setup uptime monitoring (UptimeRobot, etc)

---

## Troubleshooting Production

### Service Gak Mau Start

```bash
# Check status detail
sudo systemctl status acs-server

# Check journal log
sudo journalctl -u acs-server -n 50

# Check file permissions
ls -la /var/www/acs-core
```

### Website Error 500

```bash
# Check PHP-FPM log
tail -f /var/log/php8.1-fpm.log

# Check nginx error log
tail -f /var/log/nginx/error.log

# Check Laravel log
tail -f /var/www/acs-core/acs-laravel/storage/logs/laravel.log

# Fix permissions
sudo chown -R www-data:www-data /var/www/acs-core/acs-laravel/storage
```

### Performance Issues

```bash
# Check resource usage
top
htop

# Check database size
ls -lh /var/www/acs-core/acs.db

# Clear old logs
php artisan log:clear
```

---

## Update Production

Cara aman update aplikasi production:

```bash
# 1. Backup dulu!
/usr/local/bin/backup-acs.sh

# 2. Stop services
sudo systemctl stop acs-server
sudo systemctl stop nginx

# 3. Pull update
cd /var/www/acs-core
git pull origin main

# 4. Update dependencies
npm install
cd acs-laravel
composer install --no-dev
npm install && npm run build

# 5. Run migrations (kalau ada)
php artisan migrate --force

# 6. Clear cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Fix permissions
sudo chown -R www-data:www-data /var/www/acs-core

# 8. Start services
sudo systemctl start acs-server
sudo systemctl start nginx

# 9. Verify
curl http://localhost:7547/health
curl http://localhost:8000
```

---

## Kesimpulan

Production deployment itu perlu:
- ✅ Service yang auto-start
- ✅ Proper web server (Nginx/Apache)
- ✅ SSL/HTTPS
- ✅ Logging yang terorganisir
- ✅ Monitoring & alerts
- ✅ Regular backups
- ✅ Update strategy

Jangan lupa test semua sebelum deploy! 🚀

**Happy deploying!**
