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
            <p class="muted mb0">Unduh template, isi di Excel, lalu copy-paste isinya ke kolom di bawah. Sistem membuat/memperbarui akun berdasarkan NIS.</p>
        </div>
        <a class="btn primary" href="{{ route('students.template') }}">⬇ Unduh Template Excel (CSV)</a>
    </div>
</div>

<div class="import-layout">
    <form class="card form" method="POST" action="{{ route('students.importSimple') }}">
        @csrf
        <div class="import-toolbar">
            <div>
                <h2 class="mb0">Tempel Data Siswa</h2>
                <p class="muted small mb0">Kolom: <b>NIS · Nama · Password (min 8) · Nama Kelas · ClassroomID</b></p>
            </div>
            <button class="btn primary">Import / Update Akun</button>
        </div>
        <textarea class="input" name="students_text" rows="14" placeholder="Paste langsung dari Excel, atau ketik:&#10;4728;ACHMAD RAFA YUSAPUTRA;Siswa4728!;XII RPL 1;10&#10;4729;ADE RAHMANA;Siswa4729!;XII RPL 1;10"></textarea>
        <p class="help">Bisa paste langsung dari Excel (otomatis terdeteksi). Pemisah ; tab, atau koma diterima. Baris header "NIS" otomatis dilewati. Password kurang dari 8 karakter dilewati.</p>
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
