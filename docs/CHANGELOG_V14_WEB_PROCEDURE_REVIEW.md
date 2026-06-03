# Changelog V14 - Review Prosedur Web Produksi

## Fokus
Versi ini merapikan prosedur web, terutama perilaku Bank Soal agar sesuai alur produksi sekolah.

## Perubahan Bank Soal
- Bank Soal sekarang mendukung dua mode akses:
  - **Dibagikan untuk semua guru**: guru lain bisa memakai soal ke ujian, tetapi tidak bisa mengedit/menghapus.
  - **Pribadi / hanya pembuat**: hanya pembuat dan admin yang bisa melihat/memakai.
- Guru sekarang dapat melihat soal miliknya sendiri dan soal bersama dari guru lain.
- Halaman "Tambah dari Bank Soal" pada Builder Ujian sudah menampilkan soal bersama yang aktif.
- Saat soal dari Bank Soal ditambahkan ke ujian, sistem membuat **salinan soal** ke ujian tersebut. Perubahan bank soal di masa depan tidak mengubah ujian lama.
- Daftar Bank Soal menampilkan badge akses: Bersama / Pribadi / Nonaktif.
- Aksi Edit/Hapus disembunyikan untuk soal bersama milik guru lain.
- Import Bank Soal bisa memilih mode akses.

## Catatan Prosedur
- Bank Soal = gudang reusable.
- Builder Ujian = susunan final soal untuk satu ujian.
- Soal yang sudah masuk Builder aman untuk dipublish karena tidak tergantung lagi pada data Bank Soal.

## Perubahan Keamanan Alur Ujian
- Konfigurasi ujian sekarang dikunci total setelah ada aktivitas siswa/download/unlock/attempt.
- Ini mencegah paket soal berubah, checksum berubah, atau file paket terhapus saat siswa sudah memegang paket di HP.
- Ujian yang sudah ditutup/diarsipkan tidak bisa diedit lagi dari form konfigurasi.
