# Data Awal SILAP di Backend Ujian Sekolah

Versi ini sudah membawa data awal dari dump SILAP yang diberikan:

- `classrooms.sql` -> `database/seeders/data/silap_classrooms.json`
- `siswa.sql` -> `database/seeders/data/silap_students.json`
- `guru.sql` -> `database/seeders/data/silap_teachers.json`

Jumlah data yang dibawa:

- 31 kelas
- 251 siswa
- 54 guru

Seeder utama Laravel sudah diatur seperti ini:

```php
$this->call(SilapInitialDataSeeder::class);
$this->call(ExamDemoSeeder::class);
```

Artinya, setelah menjalankan:

```bash
php artisan migrate:fresh --seed
```

Data siswa, kelas, dan guru langsung penuh dari SILAP tanpa perlu sinkron API terlebih dahulu.

## Password siswa

Untuk tahap awal, jika `.env` tidak mengisi:

```env
SILAP_DEFAULT_STUDENT_PASSWORD=
```

maka password awal siswa otomatis memakai NIS masing-masing.

Contoh login siswa demo:

```text
Kode Ujian : DEMO01
NIS        : 4728
Password   : 4728
```

Jika sekolah ingin satu password awal seragam saat seeding, isi `.env`:

```env
SILAP_DEFAULT_STUDENT_PASSWORD=Siswa@123
```

Lalu jalankan ulang:

```bash
php artisan migrate:fresh --seed
```

## Catatan sinkron SILAP berikutnya

Data awal ini hanya untuk memasukkan data master dari dump SQL yang sudah ada. Setelah API SILAP siap, endpoint sinkron tetap bisa dipakai:

```text
POST /api/silap/sync
```

Endpoint tersebut akan memperbarui data berdasarkan `id`, `nis`, `nip`, dan `classroom_id` dari SILAP.
