# Scan Yudisium (Android)

Aplikasi panitia untuk scan QR kehadiran. Bisa dipakai **offline**, lalu otomatis terkirim saat HP online lagi. Banyak HP bisa login bersamaan.

## Yang diperlukan

- Android 7.0 ke atas (API 24)
- Android Studio (untuk install ke HP)
- Server resmi: https://undangan-yudisium.ft.unmul.ac.id/

## Pasang di HP

1. Buka folder `android-scanner` di Android Studio.
2. Tunggu Gradle sync.
3. Sambungkan HP (USB debugging) atau pakai beberapa HP sekaligus.
4. Run ke masing-masing HP.

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
