# Changelog v13 - Question Bank Builder Fix

Perbaikan fokus pada menu **Bank Soal**.

## Masalah sebelumnya
- Form Bank Soal masih berupa input teks sederhana (`options_text` dan `answer_text`).
- Tampilan tidak konsisten dengan Builder Ujian.
- Guru harus memahami format manual seperti `kiri=kanan`, `opsi per baris`, dan kunci dipisah simbol.
- Halaman daftar belum menampilkan ringkasan opsi dan kunci jawaban, sehingga isi bank soal terasa tidak jelas.

## Perbaikan
- Form Bank Soal diganti menjadi builder visual satu soal.
- Jenis soal di Bank Soal sekarang konsisten dengan Builder Ujian:
  - Pilihan Ganda
  - Pilihan Ganda Kompleks
  - Benar / Salah
  - Menjodohkan
  - Uraian Jawaban Singkat
- Kunci jawaban diisi lewat UI sesuai jenis soal, bukan format teks mentah.
- Daftar Bank Soal sekarang menampilkan ringkasan opsi dan kunci.
- Halaman pilih soal dari bank juga menampilkan opsi/kunci agar guru tidak salah mengambil soal.
- Data yang disimpan tetap kompatibel dengan Builder Ujian dan paket soal terenkripsi.

## File utama yang berubah
- `app/Http/Controllers/Admin/QuestionBankController.php`
- `app/Models/QuestionBankItem.php`
- `resources/views/question_bank/form.blade.php`
- `resources/views/question_bank/index.blade.php`
- `resources/views/question_bank/select_for_exam.blade.php`
- `resources/views/layouts/app.blade.php`
