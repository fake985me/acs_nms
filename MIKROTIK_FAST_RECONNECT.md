# Setting MikroTik Auto Reconnect Cepat (10-15 detik)

Default MikroTik periodic inform adalah 5 menit (300 detik). Untuk testing dan development, kita bisa set lebih cepat ke 10-15 detik.

## Via Terminal MikroTik

```bash
# Set periodic inform interval ke 15 detik
/tr069-client set periodic-inform-interval=15s

# Atau 10 detik
/tr069-client set periodic-inform-interval=10s

# Verifikasi setting
/tr069-client print
```

## Via Winbox/WebFig

1. Buka **System → TR-069 Client**
2. Di field **Periodic Inform Interval**, ganti dari `300` ke `15` (atau `10`)
3. Klik **Apply** dan **OK**

## Hasil

Setelah setting ini:
- MikroTik akan kontak ACS server setiap **10-15 detik**
- Task yang pending akan langsung terkirim
- Reboot akan execute dalam **10-15 detik** setelah task dibuat
- Perfect untuk testing!

## Monitoring

Monitor log di terminal ACS untuk melihat MikroTik reconnect:

```
[2025-11-24T...] POST /acs
Method: Inform Header ID: null From: 192.168.1.2
✓ Device E48D8C-hAP lite-HG709VJ17JJ updated with XX parameters
```

Setiap 10-15 detik akan ada log seperti ini.

## ⚠️ Catatan Untuk Production

Untuk production environment, **kembalikan ke interval normal** (300 detik / 5 menit):

```bash
/tr069-client set periodic-inform-interval=5m
```

Interval terlalu pendek akan:
- Membebani ACS server
- Menggunakan bandwidth tidak perlu
- Bisa overload database jika banyak device

## Testing Reboot Sekarang

Dengan interval 15 detik:
1. Klik tombol "Reboot Device" di Laravel UI
2. Tunggu **maksimal 15 detik**
3. MikroTik akan reconnect
4. Task reboot terkirim
5. MikroTik akan **REBOOT!** 🎉
