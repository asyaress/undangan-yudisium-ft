# Scan Yudisium (Android)

Aplikasi panitia untuk scan QR kehadiran. Bisa dipakai **offline**, lalu otomatis terkirim saat HP online lagi. Banyak HP bisa login bersamaan.

## Yang diperlukan

- Android 7.0 ke atas (API 24)
- Android Studio (untuk install ke HP)
- Server resmi: https://undangan-yudisium.ft.unmul.ac.id/

## Pasang di HP

**APK siap pasang (disarankan):** `android-scanner/Yudisium-FT-1.3.1.apk` — tanpa login; area scan besar; offline setelah unduh data event.

1. Salin APK ke HP, buka file, izinkan instal dari sumber tidak dikenal jika diminta.
2. Atau: buka folder `android-scanner` di Android Studio → Run ke HP (USB debugging).

**Server:** set `MOBILE_SCANNER_KEY` di `.env` (default sama dengan kunci di aplikasi). Tanpa ini, API mobile menolak sinkron dari APK baru.

Build ulang release (setelah ubah kode):

```bash
cd android-scanner
./gradlew assembleRelease
copy app\build\outputs\apk\release\app-release.apk Yudisium-FT-1.3.1.apk
```

Aplikasi langsung memakai server produksi. Cukup login akun panitia; tidak perlu isi alamat server.

## Cara pakai hari H

1. Login akun admin/panitia (satu akun boleh di banyak HP).
2. Pilih event, tunggu unduh data mahasiswa selesai (butuh internet).
3. Scan QR kartu konfirmasi. Kalau QR rusak, ketik NIM.
4. Kalau sinyal putus, scan tetap tersimpan di HP. Chip **menunggu** di pojok kanan atas menunjukkan antrian.
5. Saat online, data terkirim sendiri. Tap chip itu untuk paksa sinkron.

Mahasiswa yang sudah check-in di HP lain akan terlihat setelah sync.

## QR yang dibaca

Format undangan yang sama dengan web: `YFT|{event}|{peserta}|{token}`
