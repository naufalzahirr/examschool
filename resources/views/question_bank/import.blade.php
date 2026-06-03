@extends('layouts.app', ['title' => 'Import Bank Soal'])

@section('content')
<div class="between mb"><div><h1>Import Bank Soal</h1><p class="muted">Import cepat untuk banyak soal. Untuk file Excel nanti bisa ditambahkan setelah format final sekolah disepakati.</p></div><a class="btn" href="{{ route('question-bank.index') }}">Kembali</a></div>
<div class="import-layout">
    <div class="card">
        <form class="form" method="POST" action="{{ route('question-bank.importSimple') }}">
            @csrf
            <div class="three"><div class="field"><label>Mapel Default</label><input class="input" name="subject" placeholder="Matematika"></div><div class="field"><label>Tingkat Default</label><input class="input" name="grade_level" placeholder="XII"></div><div class="field"><label>Akses Pemakaian</label><select class="input" name="visibility"><option value="school">Dibagikan untuk semua guru</option><option value="private">Pribadi / hanya pembuat</option></select></div></div>
            <div class="field"><label>Data Soal</label><textarea class="input" name="questions_text" rows="14" placeholder="multiple_choice;Pertanyaan;10;Jawaban benar;Opsi A|Opsi B|Opsi C"></textarea></div>
            <button class="btn primary">Import Bank Soal</button>
        </form>
    </div>
    <div class="import-hero"><h2>Format</h2><pre class="import-format">jenis;pertanyaan;poin;kunci;opsi
multiple_choice;Ibu kota Indonesia?;10;Jakarta;Jakarta|Bandung|Surabaya
multiple_choice_complex;Fitur semi-online?;10;Download soal|Autosave lokal;Download soal|Autosave lokal|Online terus
true_false;Kunci dikirim ke HP siswa;10;Salah;
matching;Jodohkan istilah;10;;Frontend=Tampilan|Backend=Server
short_answer;Singkatan Safe Exam Browser;10;SEB; </pre></div>
</div>
@endsection
