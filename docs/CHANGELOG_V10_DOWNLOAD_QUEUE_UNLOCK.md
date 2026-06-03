# Changelog V10 - Download Queue + Encrypted Exam Package

Versi ini menyiapkan backend web untuk skenario hosting dan banyak siswa download paket soal secara bersamaan.

## Konsep Produksi

1. Guru/admin publish ujian.
2. Server membuat paket soal JSON terenkripsi di storage public.
3. Siswa boleh download paket sebelum ujian sesuai pengaturan default 12 jam sebelum mulai.
4. Download paket memakai antrean slot seperti war tiket, misalnya 50/399.
5. Saat waktu ujian mulai, aplikasi mobile meminta unlock key kecil ke server.
6. Siswa mengerjakan offline dari cache HP.
7. Jawaban dikirim online dengan retry.

## Endpoint Mobile Baru

```http
POST /api/mobile/login
POST /api/mobile/exam-package/queue
GET  /api/mobile/exam-package?access_code=US-XXXXXX&queue_token=TOKEN
POST /api/mobile/exam-package/download-complete
POST /api/mobile/exam-package/unlock
POST /api/mobile/attempt/start
POST /api/mobile/attempt/sync
POST /api/mobile/attempt/submit
```

## Pengaturan Baru

Di menu Pengaturan Sekolah:

- Buka Download Sebelum Ujian (jam), default 12.
- Slot Download Bersamaan, default 50.
- Masa Slot Download (menit), default 10.
- Maks. Percobaan Download, default 3.

## Catatan Hosting

Jalankan storage link setelah deploy:

```bash
php artisan storage:link
```

File paket disimpan di disk `public` agar file terenkripsi bisa dilayani oleh web server. Isi soal tidak terbaca tanpa unlock key.
