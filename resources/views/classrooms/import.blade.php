@extends('layouts.app', ['title' => 'Import Kelas'])

@section('content')
<div class="between mb">
    <div>
        <h1>Import Kelas</h1>
        <p class="muted">Import manual kelas dipisah dari tabel utama agar halaman data kelas lebih bersih.</p>
    </div>
    <a class="btn" href="{{ route('classrooms.index') }}">Kembali ke Kelas</a>
</div>

<div class="import-layout">
    <form class="card form" method="POST" action="{{ route('classrooms.importSimple') }}">
        @csrf
        <div class="import-toolbar">
            <div>
                <h2 class="mb0">Tempel Data Kelas</h2>
                <p class="muted small mb0">Format: <b>ID;TermID;Nama Kelas;Tingkat</b></p>
            </div>
            <button class="btn primary">Import / Update Kelas</button>
        </div>
        <textarea name="classrooms_text" rows="14" placeholder="31;1;X ANM;10&#10;30;1;X PSPT;10&#10;11;1;XI RPL 1;11"></textarea>
        <p class="help">Untuk sinkron otomatis dari SILAP nanti tetap gunakan endpoint <b>/api/silap/sync</b>.</p>
    </form>

    <div class="card">
        <h2>Format yang Dipakai</h2>
        <div class="step-list">
            <div class="step"><span class="step-no">1</span><div><b>ID</b><br><span class="muted small">ID kelas asli dari SILAP.</span></div></div>
            <div class="step"><span class="step-no">2</span><div><b>TermID</b><br><span class="muted small">Tahun/term aktif dari SILAP.</span></div></div>
            <div class="step"><span class="step-no">3</span><div><b>Nama Kelas</b><br><span class="muted small">Contoh: XII RPL 1.</span></div></div>
            <div class="step"><span class="step-no">4</span><div><b>Tingkat</b><br><span class="muted small">10, 11, atau 12.</span></div></div>
        </div>
        <h3 class="mt">Contoh</h3>
        <div class="import-format">10;1;XII RPL 1;12
11;1;XI RPL 1;11</div>
    </div>
</div>
@endsection
