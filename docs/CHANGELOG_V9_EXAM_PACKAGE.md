# Changelog V9 - Pre-generated Exam Package

Versi ini menyiapkan backend sebelum masuk tahap mobile dan hosting.

## Tujuan

Menghindari server down saat banyak siswa download soal bersamaan. Soal tidak lagi di-generate dari query database berat pada setiap download siswa. Paket soal dibuat sekali saat ujian dipublish, disimpan sebagai file JSON private, lalu API mobile hanya membaca file paket tersebut.

## Perubahan utama

1. Saat ujian dipublish, sistem otomatis generate file paket soal di storage private:
   `storage/app/private/exam-packages/{KODE-UJIAN}-v{VERSI}.json`
2. Paket soal tidak berisi kunci jawaban.
3. Paket soal memiliki checksum SHA-256.
4. API download paket mendukung `If-None-Match` atau query `package_checksum`.
5. Jika checksum lokal mobile sudah sama, server mengembalikan HTTP 304 agar mobile tidak download ulang.
6. Mobile boleh download paket soal sebelum jam mulai selama ujian sudah dipublish.
7. Start/sync/submit tetap mengikuti jadwal ujian melalui validasi `isOpenNow()`.
8. Halaman detail ujian menampilkan status paket, checksum, ukuran file, waktu generate, dan jumlah download.
9. Ada tombol manual `Generate Paket Soal` untuk recovery sebelum ujian dimulai.
10. Submit/sync/start bisa memvalidasi `cached_payload_hash` agar jawaban berasal dari paket soal aktif.

## Catatan hosting

Pastikan folder berikut writable oleh PHP/web server:

```bash
storage/
bootstrap/cache/
```

Untuk production:

```bash
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Paket soal disimpan di disk `local` Laravel, bukan folder public, sehingga tetap harus lewat API dengan token siswa.
