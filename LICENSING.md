# Panduan License Protection & Anti-Cloning

Panduan lengkap untuk protect license file dari copy, clone, dan pembajakan. Supaya 1 license = 1 server aja!

## Daftar Isi

- [Konsep License Protection](#konsep-license-protection)
- [Hardware Binding (Machine Fingerprint)](#hardware-binding-machine-fingerprint)
- [License Activation System](#license-activation-system)
- [Anti-Cloning Strategies](#anti-cloning-strategies)
- [Online License Validation](#online-license-validation)
- [Implementation Guide](#implementation-guide)
- [Best Practices](#best-practices)

---

## Konsep License Protection

### Masalah yang Harus Dipecahkan

**Tanpa protection:**
```
Customer A beli license
→ Install di Server 1 ✅
→ Copy license.key ke Server 2 ❌ (BAJAKAN!)
→ Copy license.key ke Server 3 ❌ (BAJAKAN!)
→ Share ke teman ❌ (BAJAKAN!)
```

**Dengan protection:**
```
Customer A beli license
→ Aktivasi di Server 1 ✅ (bound to hardware)
→ Copy license.key ke Server 2 ❌ (GAGAL - hardware beda!)
→ Deactivate Server 1
→ Aktivasi di Server 2 ✅ (allowed after deactivation)
```

### Strategi Protection

1. **Hardware Binding** - Bind license ke hardware ID
2. **Activation System** - Online activation + deactivation
3. **Heartbeat Check** - Periodic validation ke license server
4. **Machine Limit** - Max N machines per license
5. **Encrypted License** - Encrypt license data
6. **Trial/Commercial Split** - Different handling per license type

---

## Hardware Binding (Machine Fingerprint)

Bind license ke **unique hardware identifier** supaya gak bisa di-copy.

### Windows: Generate Hardware ID

```javascript
// hardware-id-windows.js
const { execSync } = require('child_process');
const crypto = require('crypto');

function getWindowsHardwareID() {
    try {
        // Ambil CPU ID
        const cpuId = execSync('wmic cpu get processorid', { encoding: 'utf8' })
            .split('\n')[1].trim();
        
        // Ambil Motherboard Serial
        const motherboardSerial = execSync('wmic baseboard get serialnumber', { encoding: 'utf8' })
            .split('\n')[1].trim();
        
        // Ambil MAC Address (first network adapter)
        const macAddress = execSync('getmac /fo csv /nh', { encoding: 'utf8' })
            .split(',')[0].replace(/"/g, '');
        
        // Combine dan hash
        const combined = `${cpuId}${motherboardSerial}${macAddress}`;
        const hardwareId = crypto.createHash('sha256').update(combined).digest('hex');
        
        return hardwareId;
    } catch (error) {
        throw new Error('Failed to generate hardware ID: ' + error.message);
    }
}

module.exports = { getWindowsHardwareID };
```

### Linux/Ubuntu: Generate Hardware ID

```javascript
// hardware-id-linux.js
const { execSync } = require('child_process');
const crypto = require('crypto');

function getLinuxHardwareID() {
    try {
        // Ambil Machine ID (persistent across reboots)
        const machineId = execSync('cat /etc/machine-id', { encoding: 'utf8' }).trim();
        
        // Ambil CPU info
        const cpuInfo = execSync("cat /proc/cpuinfo | grep 'Serial' | head -1", { encoding: 'utf8' }).trim();
        
        // Ambil MAC Address
        const macAddress = execSync("ip link show | grep 'link/ether' | head -1 | awk '{print $2}'", { encoding: 'utf8' }).trim();
        
        // Combine dan hash
        const combined = `${machineId}${cpuInfo}${macAddress}`;
        const hardwareId = crypto.createHash('sha256').update(combined).digest('hex');
        
        return hardwareId;
    } catch (error) {
        throw new Error('Failed to generate hardware ID: ' + error.message);
    }
}

module.exports = { getLinuxHardwareID };
```

### Cross-Platform Hardware ID

```javascript
// hardware-id.js
const os = require('os');
const { getWindowsHardwareID } = require('./hardware-id-windows');
const { getLinuxHardwareID } = require('./hardware-id-linux');

function getHardwareID() {
    const platform = os.platform();
    
    if (platform === 'win32') {
        return getWindowsHardwareID();
    } else if (platform === 'linux') {
        return getLinuxHardwareID();
    } else {
        throw new Error(`Unsupported platform: ${platform}`);
    }
}

// Test
console.log('Hardware ID:', getHardwareID());
```

---

## License Activation System

### Konsep Activation

1. Customer beli license → dapat **License Key**
2. Install software di server
3. **Activate** dengan license key → kirim hardware ID ke server
4. License server bind license ke hardware ID
5. Generate **Activation Token** (encrypted license + hardware ID)
6. Save activation token di `license.lic`
7. Setiap start, validate activation token

### License Key Format

```
Format: XXXX-XXXX-XXXX-XXXX-XXXX
Example: AC2E-9F4B-12D8-7A3C-5E6F

Components:
- Product Code (4 chars)
- Customer ID (4 chars)
- Expiry Date Encoded (4 chars)
- Feature Flags (4 chars)
- Checksum (4 chars)
```

### Generate License Key

```javascript
// license-generator.js
const crypto = require('crypto');

const SECRET_KEY = 'YOUR_SECRET_KEY_CHANGE_THIS'; // JANGAN HARDCODE!

function generateLicenseKey(customerId, expiryDate, features = {}) {
    // Product code
    const productCode = 'AC2E'; // ACS Core v2
    
    // Customer ID (4 chars from hash)
    const customerHash = crypto.createHash('md5')
        .update(customerId)
        .digest('hex')
        .substring(0, 4)
        .toUpperCase();
    
    // Encode expiry date (YYMM format to 4 hex chars)
    const expiry = new Date(expiryDate);
    const yearMonth = (expiry.getFullYear() % 100) * 100 + (expiry.getMonth() + 1);
    const expiryHex = yearMonth.toString(16).padStart(4, '0').toUpperCase();
    
    // Feature flags (bitwise)
    // Bit 0: Unlimited devices
    // Bit 1: API access
    // Bit 2: Advanced features
    let featureFlags = 0;
    if (features.unlimited) featureFlags |= 1;
    if (features.api) featureFlags |= 2;
    if (features.advanced) featureFlags |= 4;
    const featureHex = featureFlags.toString(16).padStart(4, '0').toUpperCase();
    
    // Checksum (HMAC of previous parts)
    const data = `${productCode}${customerHash}${expiryHex}${featureHex}`;
    const checksum = crypto.createHmac('sha256', SECRET_KEY)
        .update(data)
        .digest('hex')
        .substring(0, 4)
        .toUpperCase();
    
    // Combine dengan format XXXX-XXXX-XXXX-XXXX-XXXX
    const licenseKey = `${productCode}-${customerHash}-${expiryHex}-${featureHex}-${checksum}`;
    
    return licenseKey;
}

// Generate license
const license = generateLicenseKey('customer@example.com', '2025-12-31', {
    unlimited: true,
    api: true,
    advanced: false
});

console.log('License Key:', license);
// Output: AC2E-9F4B-19CF-0003-A2D1
```

### Activation Process

```javascript
// license-activation.js
const crypto = require('crypto');
const fs = require('fs');
const axios = require('axios');
const { getHardwareID } = require('./hardware-id');

const LICENSE_SERVER = 'https://license.yourcompany.com/api';
const SECRET_KEY = 'YOUR_SECRET_KEY_CHANGE_THIS';

async function activateLicense(licenseKey) {
    try {
        // Get hardware ID
        const hardwareId = getHardwareID();
        console.log('Hardware ID:', hardwareId);
        
        // Send activation request ke license server
        const response = await axios.post(`${LICENSE_SERVER}/activate`, {
            licenseKey: licenseKey,
            hardwareId: hardwareId,
            hostname: require('os').hostname(),
            platform: require('os').platform()
        });
        
        if (!response.data.success) {
            throw new Error(response.data.message || 'Activation failed');
        }
        
        // Save activation token
        const activationToken = response.data.activationToken;
        fs.writeFileSync('license.lic', activationToken, 'utf8');
        
        console.log('✅ License activated successfully!');
        console.log('Licensed to:', response.data.customerName);
        console.log('Expires:', response.data.expiryDate);
        
        return true;
    } catch (error) {
        console.error('❌ Activation failed:', error.message);
        return false;
    }
}

// Usage
const licenseKey = 'AC2E-9F4B-19CF-0003-A2D1';
activateLicense(licenseKey);
```

### Validate Activation (Offline)

```javascript
// license-validator.js
const crypto = require('crypto');
const fs = require('fs');
const { getHardwareID } = require('./hardware-id');

const SECRET_KEY = 'YOUR_SECRET_KEY_CHANGE_THIS';

function validateLicense() {
    try {
        // Read activation token
        if (!fs.existsSync('license.lic')) {
            throw new Error('License file not found. Please activate your license.');
        }
        
        const activationToken = fs.readFileSync('license.lic', 'utf8').trim();
        
        // Decrypt activation token
        const decipher = crypto.createDecipher('aes-256-cbc', SECRET_KEY);
        let decrypted = decipher.update(activationToken, 'hex', 'utf8');
        decrypted += decipher.final('utf8');
        
        const licenseData = JSON.parse(decrypted);
        
        // Verify hardware ID
        const currentHardwareId = getHardwareID();
        if (licenseData.hardwareId !== currentHardwareId) {
            throw new Error('License is bound to different hardware. Hardware ID mismatch.');
        }
        
        // Verify expiry
        const expiryDate = new Date(licenseData.expiryDate);
        if (expiryDate < new Date()) {
            throw new Error('License has expired.');
        }
        
        // Verify signature
        const dataToSign = `${licenseData.licenseKey}${licenseData.hardwareId}${licenseData.expiryDate}`;
        const expectedSignature = crypto.createHmac('sha256', SECRET_KEY)
            .update(dataToSign)
            .digest('hex');
        
        if (licenseData.signature !== expectedSignature) {
            throw new Error('License signature invalid. License file may be tampered.');
        }
        
        console.log('✅ License valid');
        console.log('Licensed to:', licenseData.customerName);
        console.log('Expires:', licenseData.expiryDate);
        console.log('Features:', licenseData.features);
        
        return licenseData;
    } catch (error) {
        console.error('❌ License validation failed:', error.message);
        process.exit(1);
    }
}

module.exports = { validateLicense };
```

---

## Anti-Cloning Strategies

### Strategi 1: Multiple Hardware Components

Jangan cuma pakai 1 hardware ID, tapi kombinasi:

```javascript
function getStrongHardwareFingerprint() {
    const components = [
        getCPUId(),           // ⭐ Most stable
        getMotherboardId(),   // ⭐ Most stable
        getMacAddress(),      // ⚠️ Bisa diganti
        getDiskSerial(),      // ⚠️ Bisa diganti
        getMachineId()        // ⭐ Linux only, very stable
    ];
    
    // Combine dengan weighting
    // CPU + Motherboard = must match (primary)
    // MAC/Disk = optional (secondary)
    
    const primaryHash = crypto.createHash('sha256')
        .update(components[0] + components[1])
        .digest('hex');
    
    const secondaryHash = crypto.createHash('sha256')
        .update(components[2] + components[3])
        .digest('hex');
    
    return {
        primary: primaryHash,
        secondary: secondaryHash,
        full: crypto.createHash('sha256').update(primaryHash + secondaryHash).digest('hex')
    };
}

function validateHardwareMatch(storedFingerprint, currentFingerprint) {
    // Primary HARUS match (CPU + Motherboard)
    if (storedFingerprint.primary !== currentFingerprint.primary) {
        return false; // Hardware berbeda
    }
    
    // Secondary boleh beda (toleransi untuk ganti NIC/Disk)
    return true;
}
```

### Strategi 2: Trial Period

```javascript
function generateTrialLicense() {
    const trialDays = 30;
    const expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + trialDays);
    
    const licenseData = {
        type: 'TRIAL',
        hardwareId: getHardwareID(),
        expiryDate: expiryDate.toISOString(),
        features: { limited: true },
        firstRunDate: new Date().toISOString()
    };
    
    // Simpan di beberapa lokasi (anti-deletion)
    fs.writeFileSync('license.lic', encrypt(JSON.stringify(licenseData)));
    fs.writeFileSync('.trial', encrypt(JSON.stringify(licenseData))); // Hidden
    
    // Juga simpan di registry (Windows) atau system config (Linux)
    saveTrialToSystem(licenseData);
}
```

### Strategi 3: Heartbeat Validation

```javascript
// Periodic check ke license server
setInterval(async () => {
    try {
        const response = await axios.post(`${LICENSE_SERVER}/heartbeat`, {
            licenseKey: currentLicense.licenseKey,
            hardwareId: getHardwareID()
        });
        
        if (!response.data.valid) {
            console.error('⚠️ License revoked or invalid');
            gracefulShutdown();
        }
    } catch (error) {
        // Jika offline, toleransi beberapa hari
        const lastValidCheck = getLastValidCheck();
        const daysSinceCheck = (Date.now() - lastValidCheck) / (1000 * 60 * 60 * 24);
        
        if (daysSinceCheck > 7) {
            console.error('❌ Cannot validate license (offline too long)');
            gracefulShutdown();
        }
    }
}, 24 * 60 * 60 * 1000); // Check every 24 hours
```

### Strategi 4: License File Protection

```javascript
// Prevent file modification
const licenseFilePath = 'license.lic';

// Watch for changes
fs.watch(licenseFilePath, (eventType, filename) => {
    if (eventType === 'change') {
        console.error('⚠️ License file modified! Re-validating...');
        
        // Re-validate immediately
        try {
            validateLicense();
        } catch (error) {
            console.error('❌ License tampering detected!');
            process.exit(1);
        }
    }
});

// Set file as read-only (Windows)
if (process.platform === 'win32') {
    execSync(`attrib +R "${licenseFilePath}"`);
}

// Set file as immutable (Linux) - requires root
if (process.platform === 'linux') {
    try {
        execSync(`sudo chattr +i "${licenseFilePath}"`);
    } catch (error) {
        console.warn('Could not set file as immutable');
    }
}
```

---

## Online License Validation

### License Server API (Backend)

```javascript
// license-server-api.js (Express.js)
const express = require('express');
const crypto = require('crypto');
const db = require('./database'); // Your DB

const app = express();
app.use(express.json());

// Activate license
app.post('/api/activate', async (req, res) => {
    const { licenseKey, hardwareId, hostname, platform } = req.body;
    
    try {
        // 1. Validate license key
        const license = await db.getLicenseByKey(licenseKey);
        if (!license) {
            return res.json({ success: false, message: 'Invalid license key' });
        }
        
        if (license.status === 'revoked') {
            return res.json({ success: false, message: 'License has been revoked' });
        }
        
        // 2. Check activation limit
        const activations = await db.getActivations(licenseKey);
        if (activations.length >= license.maxActivations) {
            // Check if this hardware already activated
            const existingActivation = activations.find(a => a.hardwareId === hardwareId);
            if (!existingActivation) {
                return res.json({ 
                    success: false, 
                    message: `Maximum activations (${license.maxActivations}) reached` 
                });
            }
        }
        
        // 3. Check if already activated on this hardware
        const existing = activations.find(a => a.hardwareId === hardwareId);
        if (existing) {
            // Already activated, return existing token
            return res.json({
                success: true,
                activationToken: existing.activationToken,
                customerName: license.customerName,
                expiryDate: license.expiryDate,
                message: 'License already activated on this machine'
            });
        }
        
        // 4. Create activation
        const activationData = {
            licenseKey,
            hardwareId,
            hostname,
            platform,
            customerName: license.customerName,
            expiryDate: license.expiryDate,
            features: license.features,
            activatedAt: new Date().toISOString()
        };
        
        // Sign activation data
        const dataToSign = `${licenseKey}${hardwareId}${license.expiryDate}`;
        const signature = crypto.createHmac('sha256', SECRET_KEY)
            .update(dataToSign)
            .digest('hex');
        
        activationData.signature = signature;
        
        // Encrypt activation token
        const cipher = crypto.createCipher('aes-256-cbc', SECRET_KEY);
        let activationToken = cipher.update(JSON.stringify(activationData), 'utf8', 'hex');
        activationToken += cipher.final('hex');
        
        // 5. Save to database
        await db.createActivation({
            licenseKey,
            hardwareId,
            hostname,
            platform,
            activationToken,
            activatedAt: new Date()
        });
        
        // 6. Return success
        res.json({
            success: true,
            activationToken,
            customerName: license.customerName,
            expiryDate: license.expiryDate,
            features: license.features
        });
        
    } catch (error) {
        console.error('Activation error:', error);
        res.status(500).json({ success: false, message: 'Server error' });
    }
});

// Deactivate license (untuk transfer ke server lain)
app.post('/api/deactivate', async (req, res) => {
    const { licenseKey, hardwareId } = req.body;
    
    try {
        await db.deleteActivation(licenseKey, hardwareId);
        
        res.json({
            success: true,
            message: 'License deactivated successfully'
        });
    } catch (error) {
        res.status(500).json({ success: false, message: 'Server error' });
    }
});

// Heartbeat check
app.post('/api/heartbeat', async (req, res) => {
    const { licenseKey, hardwareId } = req.body;
    
    try {
        const activation = await db.getActivation(licenseKey, hardwareId);
        
        if (!activation) {
            return res.json({ valid: false, message: 'No active license found' });
        }
        
        const license = await db.getLicenseByKey(licenseKey);
        
        if (license.status === 'revoked') {
            return res.json({ valid: false, message: 'License revoked' });
        }
        
        if (new Date(license.expiryDate) < new Date()) {
            return res.json({ valid: false, message: 'License expired' });
        }
        
        // Update last check timestamp
        await db.updateActivationHeartbeat(licenseKey, hardwareId);
        
        res.json({ 
            valid: true,
            expiryDate: license.expiryDate,
            features: license.features
        });
        
    } catch (error) {
        res.status(500).json({ valid: false, message: 'Server error' });
    }
});

app.listen(3000, () => console.log('License server running on port 3000'));
```

---

## Implementation Guide

### Langkah 1: Integrate ke ACS Core

**File: `src/license.js`**

```javascript
const { validateLicense } = require('./license-validator');

function checkLicense() {
    try {
        const license = validateLicense();
        
        // Apply feature restrictions based on license
        global.LICENSE_FEATURES = license.features;
        global.LICENSE_VALID = true;
        
        return true;
    } catch (error) {
        console.error('License validation failed:', error.message);
        console.error('\n🔒 Please activate your license:');
        console.error('   node activate.js YOUR-LICENSE-KEY\n');
        
        process.exit(1);
    }
}

module.exports = { checkLicense };
```

**File: `src/index.mjs`** (update)

```javascript
// Di bagian paling atas, sebelum start server
import { checkLicense } from './license.js';

console.log('🔍 Validating license...');
checkLicense();
console.log('✅ License valid\n');

// Rest of server code...
```

### Langkah 2: CLI untuk Aktivasi

**File: `activate.js`**

```javascript
#!/usr/bin/env node

const { activateLicense } = require('./license-activation');

const licenseKey = process.argv[2];

if (!licenseKey) {
    console.error('Usage: node activate.js <LICENSE-KEY>');
    process.exit(1);
}

console.log('🔑 Activating license...');
console.log('License Key:', licenseKey);

activateLicense(licenseKey).then(success => {
    if (success) {
        console.log('\n✅ Activation successful!');
        console.log('You can now start the ACS server.');
    } else {
        console.error('\n❌ Activation failed!');
        console.error('Please contact support.');
        process.exit(1);
    }
});
```

**Usage:**
```bash
node activate.js AC2E-9F4B-19CF-0003-A2D1
```

---

## Best Practices

### ✅ DO

1. **Hardware Binding** - Bind license ke hardware ID
2. **Online Activation** - Require activation via license server
3. **Heartbeat Check** - Periodic validation (toleran offline)
4. **Multi-Component Fingerprint** - Kombinasi CPU, motherboard, MAC
5. **Encrypted License File** - Encrypt activation token
6. **Grace Period** - Toleransi kalau server offline (7-30 hari)
7. **Easy Transfer** - Allow deactivation untuk transfer server
8. **Clear Error Messages** - Explain kenapa validation failed
9. **Support Channel** - Provide bantuan untuk activation issues
10. **Backup License** - Customer bisa download ulang dari portal

### ❌ DON'T

1. **Hardcode Secrets** - Jangan hardcode SECRET_KEY di source code
2. **Trust Client Only** - Selalu validate di server juga
3. **Block Immediately Offline** - Kasih grace period
4. **Complex Activation** - Keep it simple untuk customer
5. **No Support** - Customer stuck dengan license issues
6. **Aggressive Anti-Tamper** - Bisa crash di VM/container

---

## Kesimpulan

### Protection Level Comparison

| Method | Protection | User Friction | Offline Support |
|--------|-----------|---------------|-----------------|
| Simple key file | ❌ Low | ✅ Easy | ✅ Yes |
| Hardware binding | ⭐⭐ Medium | ⭐ Medium | ✅ Yes |
| Online activation | ⭐⭐⭐ High | ⭐⭐ Medium | ⚠️ Grace period |
| Heartbeat check | ⭐⭐⭐⭐ Very High | ⭐⭐⭐ Higher | ❌ Limited |
| Dongle/Hardware key | ⭐⭐⭐⭐⭐ Highest | ⭐⭐⭐⭐ Highest | ✅ Yes |

### Recommended Approach

**Untuk ACS Core:**

1. ✅ **Online activation** dengan hardware binding
2. ✅ **Offline validation** dengan encrypted license file
3. ✅ **Heartbeat check** setiap 24 jam (grace: 7 hari)
4. ✅ **Deactivation support** untuk transfer server
5. ✅ **Trial mode** 30 hari untuk testing

Balance antara **security**, **user experience**, dan **support overhead**.

---

**Happy licensing!** 🔐

**Links:**
- [BUILD_PACKAGE.md](BUILD_PACKAGE.md) - Build & packaging
- [DEPLOYMENT.md](DEPLOYMENT.md) - Production deployment
