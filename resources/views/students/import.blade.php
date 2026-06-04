@extends('layouts.app', ['title' => 'Import Siswa'])

@section('content')
<div class="between mb">
    <div>
        <h1>Import Siswa</h1>
        <p class="muted">Halaman import dipisah agar daftar siswa tetap bersih. Cocok untuk input cepat sebelum sinkron API SILAP dipakai penuh.</p>
    </div>
    <a class="btn" href="{{ route('students.index') }}">Kembali ke Siswa</a>
</div>

<div class="import-hero mb">
    <div class="between">
        <div>
            <h2 class="mb0">Template Import Cepat</h2>
            <p class="muted mb0">Gunakan pemisah titik koma. Sistem akan membuat atau memperbarui akun siswa berdasarkan NIS.</p>
        </div>
        <span class="badge info">Format SILAP-ready</span>
    </div>
</div>

<div class="import-layout">
    <form class="card form" method="POST" action="{{ route('students.importSimple') }}">
        @csrf
        <div class="import-toolbar">
            <div>
                <h2 class="mb0">Tempel Data Siswa</h2>
                <p class="muted small mb0">Format: <b>NIS;Nama;Password minimal 8 karakter;Nama Kelas;ClassroomID</b></p>
            </div>
            <button class="btn primary">Import / Update Akun</button>
        </div>
        <textarea name="students_text" rows="14" placeholder="4728;ACHMAD RAFA YUSAPUTRA;Siswa4728!;XII RPL 1;10&#10;4729;ADE RAHMANA;Siswa4729!;XII RPL 1;10"></textarea>
        <p class="help">Kolom nama kelas boleh kosong jika ClassroomID valid. Baris dengan password kurang dari 8 karakter akan dilewati.</p>
    </form>

    <div class="card">
        <h2>Panduan Singkat</h2>
        <div class="step-list">
            <div class="step"><span class="step-no">1</span><div><b>Ambil dari Excel/SILAP</b><br><span class="muted small">Susun urutan kolom sesuai template.</span></div></div>
            <div class="step"><span class="step-no">2</span><div><b>Paste ke kolom import</b><br><span class="muted small">Satu siswa per baris, gunakan tanda <span class="kbd">;</span>.</span></div></div>
            <div class="step"><span class="step-no">3</span><div><b>Import</b><br><span class="muted small">NIS lama akan diperbarui, NIS baru akan dibuat.</span></div></div>
        </div>
        <h3 class="mt">Contoh</h3>
        <div class="import-format">4728;ACHMAD RAFA YUSAPUTRA;Siswa4728!;XII RPL 1;10
4729;ADE RAHMANA;Siswa4729!;XII RPL 1;10</div>
    </div>
</div>
@endsection
