@extends('layouts.app', ['title' => 'Import Peserta ' . $exam->title])

@section('content')
<div class="between mb">
    <div>
        <h1>Import Peserta</h1>
        <p class="muted">{{ $exam->title }} · Kode: <b>{{ $exam->access_code }}</b>. Halaman import dipisah dari live table peserta.</p>
    </div>
    <a class="btn" href="{{ route('exams.participants', $exam) }}">Kembali ke Peserta</a>
</div>

<div class="card mb">
    <div class="between">
        <div>
            <h2 class="mb0">Kelas Target</h2>
            <p class="muted small mb0">Sebaiknya gunakan tombol sinkron peserta dari kelas jika data siswa sudah lengkap dari SILAP.</p>
        </div>
        <form method="POST" action="{{ route('exams.participants.syncClassrooms', $exam) }}">
            @csrf
            <button class="btn primary">Sinkron Peserta dari Kelas</button>
        </form>
    </div>
    <div class="row" style="margin-top:1rem">
        @forelse($exam->classrooms as $classroom)
            <span class="badge">{{ $classroom->nama_kelas }}</span>
        @empty
            <span class="muted">Belum ada kelas terpilih.</span>
        @endforelse
    </div>
</div>

<div class="import-layout">
    <form class="card form" method="POST" action="{{ route('exams.participants.importSimple', $exam) }}">
        @csrf
        <div class="import-toolbar">
            <div>
                <h2 class="mb0">Import Peserta + Buat Akun</h2>
                <p class="muted small mb0">Format: <b>NIS;Nama;Password minimal 8 karakter;Nama Kelas;ClassroomID</b></p>
            </div>
            <button class="btn primary">Import Peserta</button>
        </div>
        <textarea class="input" name="students_text" rows="14" placeholder="4728;ACHMAD RAFA YUSAPUTRA;Siswa4728!;XII RPL 1;10&#10;4729;ADE RAHMANA;Siswa4729!;XII RPL 1;10"></textarea>
        <p class="help">Jika NIS sudah ada, data dan password diperbarui, lalu siswa ditambahkan ke ujian ini. Baris dengan password kurang dari 8 karakter akan dilewati.</p>
    </form>

    <form class="card form" method="POST" action="{{ route('exams.participants.assignExisting', $exam) }}">
        @csrf
        <h2>Tambahkan dari Akun Siswa</h2>
        <p class="muted small">Masukkan NIS yang sudah ada, satu per baris atau pisahkan dengan koma.</p>
        <textarea class="input" name="nis_text" rows="10" placeholder="4728&#10;4729"></textarea>
        <button class="btn soft">Tambahkan ke Ujian</button>
        <div class="alert info mb0">Untuk pelaksanaan produksi, cara paling rapi adalah memilih kelas di konfigurasi ujian lalu klik <b>Sinkron Peserta dari Kelas</b>.</div>
    </form>
</div>
@endsection
