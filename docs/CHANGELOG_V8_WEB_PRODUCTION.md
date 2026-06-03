# Changelog V8 - Web Production Focus

Versi ini fokus pada kesiapan backend web untuk pelaksanaan ujian sekolah sebelum pengembangan Android dilanjutkan.

## Fitur produksi yang ditambahkan

1. Role web:
   - Admin
   - Guru
   - Pengawas

2. Akun guru dan pengawas:
   - Admin demo: `admin@sekolah.test / password123`
   - Pengawas demo: `pengawas / password123`
   - Guru otomatis dari data SILAP memakai username NIP dan password awal `Guru@123`.
   - Guru wajib mengganti password awal saat login pertama.

3. Bank soal:
   - CRUD bank soal
   - 5 jenis soal: pilihan ganda, pilihan ganda kompleks, benar/salah, menjodohkan, jawaban singkat
   - Import cepat berbasis teks
   - Ambil soal dari bank soal ke builder ujian

4. Pengaturan sekolah:
   - Identitas sekolah
   - Tahun ajaran dan semester
   - Kepala sekolah/proktor
   - Zona waktu
   - Toleransi terlambat login
   - Grace upload jawaban

5. Audit log:
   - Login/logout
   - Buat/update/delete akun
   - Generate akun guru
   - Publish/unpublish/close/archive ujian
   - Simpan soal
   - Reset device dan reset attempt

6. Alur status ujian:
   - Draft
   - Siap Publish
   - Published / Siap Dikerjakan
   - Selesai / Ditutup
   - Diarsipkan

7. Hak akses:
   - Admin bisa mengelola semua data.
   - Guru hanya mengelola ujian dan bank soal miliknya.
   - Pengawas hanya melihat monitor pelaksanaan.

## Perintah update

Jika masih development dan data boleh direset:

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Jika data lama harus dipertahankan:

```bash
php artisan optimize:clear
php artisan migrate
```

## Akun demo

```text
Admin    : admin@sekolah.test / password123
Pengawas : pengawas / password123
Guru     : username NIP masing-masing / Guru@123
Siswa    : NIS masing-masing / NIS masing-masing
```
