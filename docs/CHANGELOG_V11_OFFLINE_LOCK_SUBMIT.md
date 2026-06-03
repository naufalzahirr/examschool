# V11 - Offline Lock & Local-first Submit

## Fokus
Versi ini menambahkan fondasi web/API untuk mode ujian offline di Android:

- Progress utama disimpan di HP.
- Paket soal tetap terenkripsi.
- Saat jadwal ujian dimulai, aplikasi meminta unlock key.
- Setelah unlock, aplikasi bisa bekerja offline penuh.
- Keluar dari mode ujian dapat dibatasi oleh aturan per ujian.
- Pengawas bisa memakai kode keluar offline jika siswa harus keluar sebelum submit/waktu habis.
- Submit final mendukung idempotency key, checksum jawaban, dan grace upload.

## Kolom baru
### exams
- `lock_mode`
- `exit_policy`
- `offline_exit_code_salt`
- `offline_exit_code_hash`
- `offline_exit_code_encrypted`
- `offline_exit_code_generated_at`

### exam_attempts
- `local_finished_at`
- `upload_received_at`
- `submission_checksum`
- `idempotency_key`

## Mode kunci
- `standard`: cocok untuk HP pribadi siswa. Mobile mencegah lewat UI dan mencatat pelanggaran keluar aplikasi.
- `strict_kiosk`: mobile mencoba mode kiosk/lock-task. Mode penuh hanya realistis untuk perangkat sekolah/device owner.

## Aturan keluar
- `proctor_code`: rekomendasi produksi. Siswa perlu kode keluar pengawas sebelum submit/waktu habis.
- `after_submit`: siswa boleh keluar setelah submit lokal/final.
- `after_time_end`: siswa boleh keluar setelah waktu ujian habis.

## Catatan Android
Android BYOD tidak bisa dijamin terkunci 100% seperti iOS Guided Access tanpa pengelolaan perangkat. Karena itu aplikasi mobile perlu menyimpan log pelanggaran lokal dan mengirimkannya bersama submit final.
