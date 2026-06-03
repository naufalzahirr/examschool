# Review Produksi v6 - Aplikasi Ujian Sekolah

## Fokus perbaikan

Versi ini merapikan backend web agar lebih aman dipakai pada pelaksanaan ujian sekolah nyata. Fokusnya bukan menambah fitur yang terlalu melebar, tetapi mengunci alur inti supaya data soal, peserta, paket offline, dan nilai tetap konsisten.

## Keputusan teknis yang diterapkan

1. **Soal dikunci setelah publish**
   - Builder soal hanya bisa disimpan saat status ujian masih `draft`.
   - Jika ujian sudah `published`, guru/admin hanya bisa melihat soal.
   - Jika sudah ada siswa login atau mulai ujian, ujian tidak bisa dikembalikan ke draft.

2. **Checklist sebelum publish**
   - Detail ujian sekarang menampilkan checklist kesiapan: jumlah soal, kelas, peserta, durasi, dan jadwal.
   - Publish akan ditolak jika checklist belum terpenuhi.

3. **Monitor pelaksanaan**
   - Halaman baru `Monitor Pelaksanaan` untuk pengawas.
   - Menampilkan total peserta, belum mulai, sedang mengerjakan/sync, dan submit.
   - Menampilkan device lock, waktu mulai, last sync, submit, nilai, dan tombol reset device.

4. **Export hasil CSV**
   - Hasil ujian bisa diexport ke CSV dari halaman hasil atau monitor.

5. **Sinkron SILAP lebih aman**
   - Endpoint sinkron tetap bisa dipakai tanpa token untuk development lokal.
   - Pada environment production, `SILAP_SYNC_TOKEN` wajib diisi agar endpoint tidak terbuka.

6. **Sinkron peserta dari kelas lebih rapi**
   - Saat ujian masih draft dan kelas diubah, peserta yang belum mulai dan sudah tidak sesuai kelas target akan dipangkas otomatis.
   - Peserta yang sudah punya aktivitas tidak dihapus otomatis.

## Alur pelaksanaan yang disarankan

1. Sinkron data kelas, siswa, dan guru dari SILAP.
2. Buat ujian baru.
3. Pilih satu atau beberapa kelas target.
4. Buat soal di builder.
5. Cek peserta otomatis di menu peserta.
6. Cek checklist kesiapan pada detail ujian.
7. Publish ujian.
8. Saat ujian berlangsung, pengawas membuka halaman monitor.
9. Setelah ujian selesai, tutup ujian.
10. Export hasil CSV.

## Catatan untuk tahap mobile Android

Backend sudah menjaga agar kunci jawaban tidak dikirim ke paket soal siswa. Untuk Android nanti, aplikasi cukup menyimpan paket soal, jawaban lokal, client_attempt_id, device_id, package_hash, dan status submit. Submit tetap diverifikasi oleh server.
