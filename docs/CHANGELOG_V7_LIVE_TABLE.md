# Changelog v7 - Live Table & Import Page

## Fokus perubahan
- Menggabungkan search/filter dengan tabel agar halaman terasa seperti DataTables/live table.
- Memisahkan halaman import manual supaya halaman daftar data tetap bersih.
- Menambahkan filter database untuk Ujian, Peserta, Monitor, dan Hasil.

## Halaman yang dirapikan
- `/students`
- `/teachers`
- `/classrooms`
- `/exams`
- `/exams/{exam}/participants`
- `/exams/{exam}/monitor`
- `/exams/{exam}/results`

## Halaman import baru
- `/students/import`
- `/classrooms/import`
- `/exams/{exam}/participants/import`

## Catatan teknis
Live search yang dibuat bersifat ringan tanpa dependency tambahan. Ketik di input untuk memfilter data yang sedang tampil pada halaman. Tekan tombol **Cari** atau Enter untuk melakukan pencarian server/database.
