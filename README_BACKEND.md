# Aplikasi Ujian Sekolah - Backend Laravel

Fokus versi ini adalah backend web konfigurasi.

## Perubahan utama v6

- Data kelas sudah mengikuti format `classrooms.sql` SILAP: `id`, `term_id`, `nama_kelas`, `tingkat`.
- Menu baru **Kelas** untuk melihat/import data kelas.
- Form buat/edit ujian sudah memakai pilihan kelas dari tabel `classrooms`.
- Satu ujian bisa memilih lebih dari satu kelas.
- Peserta ujian otomatis diambil dari siswa aktif yang `classroom_id`-nya termasuk kelas terpilih.
- Jika siswa baru tersinkron setelah ujian dibuat, buka **Kelola Peserta** lalu klik **Sinkron Peserta dari Kelas**.
- Tampilan backend bergaya Sneat Admin: sidebar, navbar, card, table, badge, dan form lebih rapi.
- Kode ujian otomatis dibuat saat membuat ujian baru. Format contoh: `US-ABC234`.
- Kode soal otomatis dibuat untuk tiap pertanyaan. Format contoh: `Q-260603-ABCD`.
- Jenis soal tersedia:
  1. Pilihan ganda
  2. Pilihan ganda kompleks
  3. Benar / salah
  4. Menjodohkan
  5. Uraian jawaban singkat
- Data siswa disesuaikan dengan format SILAP dari `siswa.sql`.
- Data guru disesuaikan dengan format SILAP dari `guru.sql`.
- Endpoint `POST /api/silap/sync` sekarang menerima `classrooms/kelas`, `students/siswa`, dan `teachers/guru`.
- Login siswa mobile memakai `kode ujian + NIS + password`.
- Soal otomatis dikunci setelah publish agar paket soal dan penilaian tidak berubah saat ujian berjalan.
- Halaman **Monitor Pelaksanaan** ditambahkan untuk pengawas melihat siswa belum mulai, sedang mengerjakan, tersinkron, dan sudah submit.
- Export hasil ujian ke CSV ditambahkan.

## Setup fresh

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

Buka:

```text
http://127.0.0.1:8000/setup
```

Buat akun admin pertama sendiri.

## Setup demo

Jika ingin data demo lengkap kelas + siswa + guru + ujian:

```bash
php artisan migrate:fresh --seed
```

Akun admin demo:

```text
Email    : admin@sekolah.test
Password : password123
```

Akun siswa demo:

```text
Kode ujian : DEMO01
NIS        : 4728
Password   : 4728
```

## Alur produksi web

1. Sinkron/import data **Kelas** dari SILAP.
2. Sinkron/import data **Siswa** dari SILAP.
3. Buat ujian.
4. Pilih satu atau beberapa kelas di form ujian.
5. Buat soal di builder.
6. Cek peserta otomatis di menu **Kelola Peserta**.
7. Buka halaman detail ujian dan cek **Checklist Kesiapan**.
8. Publish ujian. Setelah publish, soal dikunci.
9. Saat ujian berjalan, buka **Monitor Pelaksanaan**.
10. Setelah selesai, buka **Hasil Ujian** dan export CSV bila diperlukan.

## Integrasi SILAP

Tambahkan ke `.env`:

```env
SILAP_SYNC_TOKEN=isi_token_rahasia_panjang
SILAP_DEFAULT_STUDENT_PASSWORD=
```

Endpoint:

```http
POST /api/silap/sync
Authorization: Bearer TOKEN_DARI_ENV
```

Detail format ada di `docs/SILAP_SYNC_FORMAT.md`.

## Catatan produksi

- Untuk produksi jangan gunakan password demo.
- Isi `APP_URL`, database MySQL, mail, dan `SILAP_SYNC_TOKEN`.
- Jalankan `php artisan optimize:clear` setelah menimpa file.
- Jika struktur database berubah besar, gunakan `php artisan migrate:fresh` pada database development. Untuk data produksi nanti perlu migrasi bertahap, bukan fresh.
- Di environment produksi, `SILAP_SYNC_TOKEN` wajib diisi. Jika kosong, endpoint sinkron akan ditolak.
- Jangan edit soal saat ujian sedang berjalan. Sistem sekarang mengunci soal setelah publish untuk menghindari beda paket soal antar siswa.

## Data awal dari SILAP

Versi ini sudah membawa data master dari dump SILAP yang diberikan: 31 kelas, 251 siswa, dan 54 guru. Data tersebut berada di `database/seeders/data/` dan dimasukkan otomatis oleh `SilapInitialDataSeeder` saat menjalankan:

```bash
php artisan migrate:fresh --seed
```

Jika `SILAP_DEFAULT_STUDENT_PASSWORD` di `.env` kosong, password awal siswa memakai NIS masing-masing. Contoh login siswa demo: kode ujian `DEMO01`, NIS `4728`, password `4728`.

Lihat detail di `docs/SILAP_INITIAL_DATA.md`.

## V9 - Paket Soal Statis untuk Download Serentak

Mulai versi ini, download soal siswa tidak lagi mengambil daftar soal langsung dari query database besar. Saat ujian dipublish, backend otomatis membuat file paket soal statis di storage private:

```text
storage/app/private/exam-packages/{KODE-UJIAN}-v{VERSI}.json
```

File ini tidak berisi kunci jawaban. Kunci jawaban tetap hanya tersimpan di database server.

Alur produksi yang disarankan:

1. Guru/admin membuat ujian dan soal.
2. Guru/admin publish ujian.
3. Sistem membuat paket soal JSON + checksum.
4. Siswa login dan download paket soal sebelum jam mulai.
5. Saat jam mulai, aplikasi membuka paket dari cache HP.
6. Siswa mengerjakan offline.
7. Setelah selesai, aplikasi mengirim jawaban online kembali.

Endpoint mobile:

```text
POST /api/mobile/login
GET  /api/mobile/exam-package?access_code=KODE_UJIAN
POST /api/mobile/attempt/start
POST /api/mobile/attempt/sync
POST /api/mobile/attempt/submit
```

Untuk menghindari download ulang, mobile bisa mengirim:

```text
If-None-Match: "CHECKSUM_LOKAL"
```

atau query:

```text
/api/mobile/exam-package?access_code=KODE_UJIAN&package_checksum=CHECKSUM_LOKAL
```

Jika checksum sama, server akan membalas HTTP 304.

Catatan hosting:

```bash
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan PHP extension yang umum untuk Laravel tersedia di hosting, terutama `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, dan `dom`.

## Update V10 - Antrean Download + Unlock Key

Untuk menghindari server down saat siswa download paket soal secara serentak, backend sekarang memakai pola:

1. Paket soal dibuat saat publish.
2. Paket soal disimpan sebagai file JSON terenkripsi di disk `public`.
3. Siswa boleh download paket sebelum ujian, default 12 jam sebelum mulai.
4. Download paket memakai antrean slot, default 50 siswa bersamaan.
5. Saat waktu ujian mulai, mobile meminta unlock key kecil ke server.
6. Pengerjaan tetap offline dari cache HP.

Endpoint mobile terbaru:

```text
POST /api/mobile/login
POST /api/mobile/exam-package/queue
GET  /api/mobile/exam-package?access_code=KODE_UJIAN&queue_token=QUEUE_TOKEN
POST /api/mobile/exam-package/download-complete
POST /api/mobile/exam-package/unlock
POST /api/mobile/attempt/start
POST /api/mobile/attempt/sync
POST /api/mobile/attempt/submit
```

Pengaturan antrean ada di menu **Pengaturan Sekolah**:

```text
Buka Download Sebelum Ujian: 12 jam
Slot Download Bersamaan: 50
Masa Slot Download: 10 menit
Maks. Percobaan Download: 3 kali
```

Setelah deploy hosting, wajib jalankan:

```bash
php artisan storage:link
```
