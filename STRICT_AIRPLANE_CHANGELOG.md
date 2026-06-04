# Strict Airplane Exam Mode - Catatan Perubahan

## Mobile
- Siswa tetap login/download paket saat online.
- Setelah unlock key didapat, siswa wajib mengaktifkan mode pesawat/offline sebelum soal ditampilkan.
- Halaman soal memakai fullscreen/immersive + secure mode.
- Tombol back/keluar tidak menutup ujian; ujian langsung terkunci.
- Jika aplikasi keluar/minimize/resume atau internet aktif saat ujian, ujian terkunci.
- Untuk lanjut, perangkat harus offline lagi dan pengawas wajib memasukkan kode.
- Timer tetap berjalan saat terkunci.
- Jawaban tetap tersimpan lokal.
- Setelah submit lokal, siswa diarahkan menyalakan internet untuk upload jawaban.

## Backend / Pengawas
- Ditambah endpoint `/api/mobile/attempt/integrity-event` untuk menerima event pelanggaran saat perangkat sempat online.
- Event offline tetap ikut dikirim bersama payload submit.
- Status peserta dapat menjadi `locked` ketika ada pelanggaran yang terkirim ke server.
- Halaman monitor menampilkan ringkasan Integritas & Kunci Ujian: terkunci, total event, keluar/minimize, internet aktif.
- Default mode ujian baru diubah ke `strict_airplane` dan exit policy default tetap `proctor_code`.

## Catatan Android
Aplikasi Android biasa tidak bisa memaksa tombol Home/Recent benar-benar mati tanpa device owner/kiosk. Implementasi ini memakai pendekatan realistis: fullscreen, wajib offline, auto-lock, timer tetap berjalan, dan wajib kode pengawas.
