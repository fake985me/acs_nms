# Panduan Build & Packaging ACS Core

Panduan lengkap untuk build executable Windows (.exe), package Ubuntu (.deb), dan protect source code. Cocok untuk distribusi komersial atau deployment yang lebih secure!

## Daftar Isi

- [Tentang Packaging](#tentang-packaging)
- [Windows: Build Executable](#windows-build-executable)
- [Ubuntu: Build DEB Package](#ubuntu-build-deb-package)
- [Source Code Protection](#source-code-protection)
- [Licensing System (Recommended)](#licensing-system-recommended)
- [Distribution & Updates](#distribution--updates)

---

## Tentang Packaging

### Kenapa Perlu Packaging?

**Tanpa packaging:**
- ❌ Source code terbuka (bisa dicuri/dimodifikasi)
- ❌ User harus install dependencies manual
- ❌ Setup ribet
- ❌ Susah distribute ke customer

**Dengan packaging:**
- ✅ Source code ter-protect (obfuscated/compiled)
- ✅ Dependencies included
- ✅ Install gampang (double-click .exe atau dpkg -i)
- ✅ Professional distribution
- ✅ Licensing control

### Limitasi & Catatan

**Node.js (ACS Server):**
- Node.js bisa di-compile jadi executable dengan `pkg` atau `nexe`
- Source code akan di-bundle tapi **masih bisa di-extract**
- Untuk protection lebih, perlu obfuscation dulu

**PHP/Laravel (Dashboard):**
- PHP native **gak bisa** jadi true executable
- Bisa pakai: PHP Desktop, Laravel Vapor, atau bundle dengan PHP runtime
- Alternatif: Encrypt dengan ionCube/Zend Guard

**Best Approach:**
- Obfuscate source code
- Bundle dengan dependencies
- Tambah licensing system
- Distribute as installer/package

---

## Windows: Build Executable

### Opsi 1: Pakai pkg (Recommended untuk ACS Server)

`pkg` adalah tool untuk compile Node.js app jadi single executable.

#### Install pkg

```bash
npm install -g pkg
```

#### Persiapan Project

**1. Update package.json:**

Edit `C:\laragon\www\acs-core\package.json`, tambah:

```json
{
  "name": "acs-core",
  "version": "2.0.0",
  "bin": "src/index.mjs",
  "pkg": {
    "scripts": "src/**/*.js",
    "assets": [
      "node_modules/**/*",
      "src/**/*"
    ],
    "targets": [
      "node18-win-x64"
    ],
    "outputPath": "dist"
  }
}
```

**2. Build executable:**

```bash
cd C:\laragon\www\acs-core

# Build untuk Windows 64-bit
pkg . --target node18-win-x64 --output dist/acs-server.exe

# Build untuk multiple platforms
pkg . --targets node18-win-x64,node18-linux-x64
```

Output: `dist/acs-server.exe` (single file ~40-50MB)

#### Catatan pkg:

- ✅ Single executable file
- ✅ Bundled Node.js runtime
- ✅ No external dependencies
- ⚠️ Source masih bisa di-extract dengan tools khusus
- ⚠️ Perlu tambahan obfuscation untuk security

### Opsi 2: NSIS Installer (Recommended untuk Full Package)

NSIS (Nullsoft Scriptable Install System) untuk buat installer Windows yang proper.

#### Install NSIS

1. Download: https://nsis.sourceforge.io/Download
2. Install NSIS

#### Buat Script Installer

**File: `installer.nsi`**

```nsis
; ACS Core Installer Script
!define APPNAME "ACS Core"
!define COMPANYNAME "Your Company"
!define DESCRIPTION "TR-069 ACS Server & Dashboard"
!define VERSIONMAJOR 2
!define VERSIONMINOR 0
!define VERSIONBUILD 0

!include "MUI2.nsh"

Name "${APPNAME}"
OutFile "ACSCore-Setup.exe"
InstallDir "$PROGRAMFILES64\ACS Core"

; Request admin privileges
RequestExecutionLevel admin

;--------------------------------
; Pages
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_LICENSE "LICENSE.txt"
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH

!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES

!insertmacro MUI_LANGUAGE "English"

;--------------------------------
; Installer
Section "Install"
    SetOutPath "$INSTDIR"
    
    ; Copy files
    File /r "dist\*.*"
    File "acs.db"
    File "schema.sql"
    File /r "acs-laravel"
    
    ; Create Start Menu shortcuts
    CreateDirectory "$SMPROGRAMS\${APPNAME}"
    CreateShortcut "$SMPROGRAMS\${APPNAME}\ACS Core.lnk" "$INSTDIR\acs-server.exe"
    CreateShortcut "$SMPROGRAMS\${APPNAME}\Uninstall.lnk" "$INSTDIR\uninstall.exe"
    
    ; Install as service menggunakan NSSM
    ExecWait '"$INSTDIR\nssm.exe" install ACSServer "$INSTDIR\acs-server.exe"'
    ExecWait '"$INSTDIR\nssm.exe" set ACSServer Start SERVICE_AUTO_START'
    ExecWait '"$INSTDIR\nssm.exe" start ACSServer'
    
    ; Create uninstaller
    WriteUninstaller "$INSTDIR\uninstall.exe"
    
    ; Registry keys untuk Add/Remove Programs
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${APPNAME}" "DisplayName" "${APPNAME}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${APPNAME}" "UninstallString" "$\"$INSTDIR\uninstall.exe$\""
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${APPNAME}" "Publisher" "${COMPANYNAME}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${APPNAME}" "DisplayVersion" "${VERSIONMAJOR}.${VERSIONMINOR}.${VERSIONBUILD}"
SectionEnd

;--------------------------------
; Uninstaller
Section "Uninstall"
    ; Stop and remove service
    ExecWait '"$INSTDIR\nssm.exe" stop ACSServer'
    ExecWait '"$INSTDIR\nssm.exe" remove ACSServer confirm'
    
    ; Remove files
    RMDir /r "$INSTDIR"
    
    ; Remove shortcuts
    RMDir /r "$SMPROGRAMS\${APPNAME}"
    
    ; Remove registry keys
    DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\${APPNAME}"
SectionEnd
```

#### Build Installer:

```bash
# Right-click installer.nsi → Compile NSIS Script
# Atau via command line:
makensis installer.nsi
```

Output: `ACSCore-Setup.exe` (installer lengkap)

### Opsi 3: Electron (Untuk Full Desktop App)

Kalau mau bundle ACS Server + Dashboard jadi satu desktop app:

```bash
# Install electron-builder
npm install -g electron-builder

# Setup electron app structure
# (butuh restructure project - complex)
```

**Pro:** True desktop app dengan GUI  
**Con:** File size besar (200-300MB), complex setup

---

## Ubuntu: Build DEB Package

Package `.deb` untuk distribusi di Ubuntu/Debian.

### Struktur DEB Package

```
acs-core_2.0.0_amd64/
├── DEBIAN/
│   ├── control
│   ├── postinst
│   ├── prerm
│   └── postrm
├── opt/
│   └── acs-core/
│       ├── acs-server (executable)
│       ├── acs-laravel/
│       ├── acs.db
│       └── ...
├── etc/
│   └── systemd/
│       └── system/
│           └── acs-server.service
└── usr/
    └── share/
        └── doc/
            └── acs-core/
                ├── README.md
                └── copyright
```

### Langkah 1: Build Node.js Executable

```bash
# Di Ubuntu/WSL
cd ~/acs-core

# Install pkg
npm install -g pkg

# Build untuk Linux
pkg . --target node18-linux-x64 --output acs-server
```

### Langkah 2: Buat Struktur DEB

```bash
# Buat folder struktur
mkdir -p acs-core_2.0.0_amd64/DEBIAN
mkdir -p acs-core_2.0.0_amd64/opt/acs-core
mkdir -p acs-core_2.0.0_amd64/etc/systemd/system
mkdir -p acs-core_2.0.0_amd64/usr/share/doc/acs-core

# Copy files
cp acs-server acs-core_2.0.0_amd64/opt/acs-core/
cp acs.db acs-core_2.0.0_amd64/opt/acs-core/
cp -r acs-laravel acs-core_2.0.0_amd64/opt/acs-core/
cp *.md acs-core_2.0.0_amd64/usr/share/doc/acs-core/
```

### Langkah 3: Buat Control File

**File: `acs-core_2.0.0_amd64/DEBIAN/control`**

```
Package: acs-core
Version: 2.0.0
Architecture: amd64
Maintainer: Your Name <your@email.com>
Depends: nginx, php8.1-fpm, php8.1-cli, php8.1-sqlite3, sqlite3
Section: net
Priority: optional
Homepage: https://yourwebsite.com
Description: TR-069 ACS Server with Laravel Dashboard
 ACS Core is a complete TR-069 Auto Configuration Server (ACS)
 with modern Laravel dashboard for managing CPE devices like
 MikroTik routers, ONTs, and other TR-069 compatible devices.
```

### Langkah 4: Buat Post-Install Script

**File: `acs-core_2.0.0_amd64/DEBIAN/postinst`**

```bash
#!/bin/bash
set -e

# Set permissions
chown -R www-data:www-data /opt/acs-core
chmod 755 /opt/acs-core/acs-server

# Create log directory
mkdir -p /var/log/acs-server
chown www-data:www-data /var/log/acs-server

# Reload systemd dan enable service
systemctl daemon-reload
systemctl enable acs-server
systemctl start acs-server

# Setup Laravel
cd /opt/acs-core/acs-laravel
sudo -u www-data php artisan key:generate --force
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache

echo "ACS Core installed successfully!"
echo "Dashboard: http://localhost:8000"
echo "ACS Server: http://localhost:7547"

exit 0
```

**Buat executable:**
```bash
chmod 755 acs-core_2.0.0_amd64/DEBIAN/postinst
```

### Langkah 5: Buat systemd Service File

**File: `acs-core_2.0.0_amd64/etc/systemd/system/acs-server.service`**

```ini
[Unit]
Description=TR-069 ACS Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/opt/acs-core
ExecStart=/opt/acs-core/acs-server
Restart=always
RestartSec=10

StandardOutput=append:/var/log/acs-server/access.log
StandardError=append:/var/log/acs-server/error.log

[Install]
WantedBy=multi-user.target
```

### Langkah 6: Build DEB Package

```bash
# Build package
dpkg-deb --build acs-core_2.0.0_amd64

# Output: acs-core_2.0.0_amd64.deb
```

### Langkah 7: Test Installation

```bash
# Install
sudo dpkg -i acs-core_2.0.0_amd64.deb

# Kalau ada dependency issues
sudo apt-get install -f

# Cek service
sudo systemctl status acs-server

# Uninstall
sudo dpkg -r acs-core
```

---

## Source Code Protection

### 1. JavaScript/Node.js Obfuscation

**Pakai javascript-obfuscator:**

```bash
# Install
npm install -g javascript-obfuscator

# Obfuscate single file
javascript-obfuscator src/index.mjs --output dist/index.mjs

# Obfuscate seluruh folder
javascript-obfuscator src/ --output dist/ --compact true --control-flow-flattening true
```

**Config untuk maximum protection:**

```bash
javascript-obfuscator src/ --output dist/ \
  --compact true \
  --control-flow-flattening true \
  --control-flow-flattening-threshold 1 \
  --dead-code-injection true \
  --dead-code-injection-threshold 1 \
  --identifier-names-generator hexadecimal \
  --rename-globals true \
  --rotate-string-array true \
  --self-defending true \
  --string-array true \
  --string-array-encoding rc4 \
  --string-array-threshold 1 \
  --transform-object-keys true
```

**Warning:** Code jadi jauh lebih lambat! Balance between protection vs performance.

### 2. PHP Encryption

**Opsi A: ionCube (Komersial)**

```bash
# Install ionCube encoder
# Download dari: https://www.ioncube.com/

# Encode Laravel project
ioncube_encoder.sh acs-laravel/ encoded-laravel/ \
  --encrypt '*.php' \
  --copy '*.blade.php' '*.json' '*.env.example'
```

**Opsi B: Zend Guard (Komersial)**

Similar dengan ionCube, lebih mahal tapi lebih features.

**Opsi C: SourceGuardian (Lebih Murah)**

Alternative yang lebih affordable.

**Opsi D: Custom Base64 Encoding (Basic)**

```php
// Encode file
<?php
eval(base64_decode('ENCODED_STRING_HERE'));
```

**Warning:** Ini **bukan** enkripsi real, hanya obscure. Mudah di-decode!

### 3. Database Encryption

```bash
# Encrypt SQLite database
sqlcipher acs.db
sqlite> PRAGMA key = 'your-encryption-key';
sqlite> ATTACH DATABASE 'acs-encrypted.db' AS encrypted KEY 'your-encryption-key';
sqlite> SELECT sqlcipher_export('encrypted');
sqlite> DETACH DATABASE encrypted;
```

### 4. Environment Variables Protection

Jangan hardcode credentials! Pakai encryption key:

```env
APP_KEY=base64:GENERATED_KEY
DB_PASSWORD=encrypted:ENCRYPTED_PASSWORD
```

---

## Licensing System (Recommended)

Lebih baik pakai licensing system daripada full encryption.

### Simple License Key System

**1. Generate License Key:**

```javascript
// license-generator.js
const crypto = require('crypto');

function generateLicense(customerId, expiryDate) {
    const data = `${customerId}:${expiryDate}`;
    const signature = crypto.createHmac('sha256', 'SECRET_KEY')
        .update(data)
        .digest('hex');
    
    return Buffer.from(`${data}:${signature}`).toString('base64');
}

// Generate license
const license = generateLicense('CUSTOMER001', '2025-12-31');
console.log('License:', license);
```

**2. Validate License:**

```javascript
// Di src/index.mjs - tambah di awal
const crypto = require('crypto');
const fs = require('fs');

function validateLicense() {
    try {
        const license = fs.readFileSync('license.key', 'utf8').trim();
        const decoded = Buffer.from(license, 'base64').toString();
        const [customerId, expiryDate, signature] = decoded.split(':');
        
        // Verify signature
        const data = `${customerId}:${expiryDate}`;
        const expectedSig = crypto.createHmac('sha256', 'SECRET_KEY')
            .update(data)
            .digest('hex');
        
        if (signature !== expectedSig) {
            throw new Error('Invalid license signature');
        }
        
        // Check expiry
        if (new Date(expiryDate) < new Date()) {
            throw new Error('License expired');
        }
        
        console.log(`✅ License valid for ${customerId} until ${expiryDate}`);
        return true;
    } catch (error) {
        console.error('❌ License validation failed:', error.message);
        process.exit(1);
    }
}

// Validate license sebelum start server
validateLicense();
```

**3. Deploy dengan License:**

Customer install, terus aktivasi dengan license key:

```bash
# Windows
echo LICENSE_KEY > C:\Program Files\ACS Core\license.key

# Ubuntu
echo LICENSE_KEY > /opt/acs-core/license.key
```

### Online License Validation

Untuk protection lebih, validate license ke server online:

```javascript
async function validateLicenseOnline(licenseKey) {
    const response = await fetch('https://yourserver.com/api/validate-license', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license: licenseKey })
    });
    
    const result = await response.json();
    return result.valid;
}
```

---

## Distribution & Updates

### Windows Distribution

**1. Distribute via Website:**
```
https://yourwebsite.com/downloads/ACSCore-Setup-2.0.0.exe
```

**2. Auto-Update System:**

Pakai electron-updater atau custom update checker:

```javascript
// Simple update checker
async function checkForUpdates() {
    const currentVersion = '2.0.0';
    const response = await fetch('https://yourserver.com/api/latest-version');
    const { version, downloadUrl } = await response.json();
    
    if (version > currentVersion) {
        console.log(`Update available: ${version}`);
        console.log(`Download: ${downloadUrl}`);
    }
}
```

### Ubuntu Distribution

**1. APT Repository (Advanced):**

Setup own repo:
```bash
# Upload .deb files
# Create Packages file
# Setup repository
```

**2. Via Direct Download:**
```bash
wget https://yourwebsite.com/downloads/acs-core_2.0.0_amd64.deb
sudo dpkg -i acs-core_2.0.0_amd64.deb
```

---

## Kesimpulan

### Recommended Approach

**Untuk Komersial:**
1. ✅ Obfuscate JavaScript dengan javascript-obfuscator
2. ✅ Encrypt PHP dengan ionCube/SourceGuardian
3. ✅ Build executable dengan pkg
4. ✅ Package dengan NSIS installer (Windows) / DEB (Ubuntu)
5. ✅ **Implement licensing system** (paling penting!)
6. ✅ Setup auto-update mechanism

**Untuk Internal/Open Source:**
1. ⭐ Skip obfuscation
2. ⭐ Pakai NSIS installer atau DEB package
3. ⭐ Document installation process
4. ⭐ Provide proper support

### Security Checklist

- [ ] Source code obfuscated
- [ ] Executable built dan tested
- [ ] License system implemented
- [ ] Installer tested di clean system
- [ ] Update mechanism ready
- [ ] Documentation lengkap untuk customer
- [ ] Support channel ready

---

**Happy packaging!** 📦

**Links:**
- [DEPLOYMENT.md](DEPLOYMENT.md) - Production deployment
- [AUTO_START.md](AUTO_START.md) - Auto-start setup
- [INSTALLATION.md](INSTALLATION.md) - Manual installation
