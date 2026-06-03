# Format Sinkron SILAP

Endpoint backend ujian:

```http
POST /api/silap/sync
Authorization: Bearer {{SILAP_SYNC_TOKEN}}
Content-Type: application/json
```

Token bisa dikosongkan saat pengembangan. Untuk produksi isi `.env`:

```env
SILAP_SYNC_TOKEN=isi_token_rahasia_panjang
SILAP_DEFAULT_STUDENT_PASSWORD=
```

## Payload yang didukung

Backend ujian menerima key Indonesia maupun Inggris:

- `classrooms` atau `kelas`
- `students` atau `siswa`
- `teachers` atau `guru`

Disarankan urutan payload tetap mengirim `classrooms` dulu, lalu `students`, lalu `teachers`, karena `students.classroom_id` akan dicocokkan ke data kelas.

```json
{
  "classrooms": [
    {
      "id": 10,
      "term_id": 1,
      "nama_kelas": "XII RPL 1",
      "tingkat": 12
    },
    {
      "id": 11,
      "term_id": 1,
      "nama_kelas": "XI RPL 1",
      "tingkat": 11
    }
  ],
  "students": [
    {
      "id": 1,
      "term_id": 1,
      "classroom_id": 10,
      "user_id": 55,
      "nis": "4728",
      "nama_lengkap": "ACHMAD RAFA YUSAPUTRA",
      "jenis_kelamin": "L",
      "alamat": null,
      "kontak": null,
      "photo": null
    }
  ],
  "teachers": [
    {
      "id": 47,
      "user_id": 48,
      "nip": "NIPPPK 200005102024211004",
      "nama_lengkap": "NAUFAL ZAHIR RIZQULLAH, S.Kom.",
      "jenis_kelamin": "L",
      "kontak": null,
      "alamat": null,
      "photo": null
    }
  ],
  "default_student_password": "siswa123"
}
```

## Catatan penting

1. `classrooms.id` mengikuti ID asli dari tabel `classrooms` SILAP.
2. `students.classroom_id` harus sama dengan `classrooms.id`.
3. Saat membuat ujian, admin/guru memilih satu atau beberapa kelas dari tabel `classrooms`.
4. Siswa aktif yang `classroom_id`-nya termasuk kelas terpilih akan otomatis menjadi peserta ujian.
5. Jika siswa baru disinkron setelah ujian dibuat, buka menu **Peserta Ujian** lalu klik **Sinkron Peserta dari Kelas**.
6. Jika `default_student_password` kosong, akun siswa baru akan memakai NIS sebagai password awal. Untuk produksi, lebih aman kirim password awal dari SILAP atau buat fitur reset password khusus.

## Catatan keamanan v6

Pada `APP_ENV=production`, variabel `SILAP_SYNC_TOKEN` wajib diisi. Jika token kosong, endpoint `/api/silap/sync` akan menolak request agar API sinkron tidak terbuka bebas.
