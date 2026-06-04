@extends('layouts.app', ['title' => 'Daftar Ujian'])

@section('content')
<div class="between mb">
    <div><h1>Daftar Ujian</h1><p class="muted">Kelola ujian dari draft sampai hasil. Soal ujian dipilih dari Bank Soal setelah konfigurasi ujian dibuat.</p></div>
    <a class="btn primary" href="{{ route('exams.create') }}">+ Buat Ujian dari Bank Soal</a>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Live Table Ujian</h2>
            <p class="muted small mb0">Menampilkan {{ $exams->firstItem() ?? 0 }}-{{ $exams->lastItem() ?? 0 }} dari {{ $exams->total() }} ujian.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('exams.index') }}">
            <div class="tool-field">
                <label>Status</label>
                <select class="input" name="status" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Kelas</label>
                <select class="input" name="classroom_id" onchange="this.form.submit()">
                    <option value="">Semua kelas</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field search">
                <label>Live Search</label>
                <div class="live-search-wrap">
                    <input class="input" data-live-search="examsTable" name="q" value="{{ request('q') }}" placeholder="Cari judul, kode, mapel, kelas">
                </div>
            </div>
            <button class="btn primary">Cari</button>
            @if(request('q') || request('status') || request('classroom_id'))
                <a class="btn" href="{{ route('exams.index') }}">Reset</a>
            @else
                <button class="btn" type="button" data-live-reset="examsTable">Clear</button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="table" id="examsTable">
            <thead><tr><th>Ujian</th><th>Kode</th><th>Kelas</th><th>Status</th><th>Paket</th><th>Soal</th><th>Peserta</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($exams as $exam)
                <tr>
                    <td><b>{{ $exam->title }}</b><br><span class="muted small">{{ $exam->subject ?: '-' }} · {{ $exam->grade_level ?: '-' }}</span></td>
                    <td><span class="badge">{{ $exam->access_code }}</span></td>
                    <td>
                        @forelse($exam->classrooms->take(4) as $classroom)
                            <span class="badge">{{ $classroom->nama_kelas }}</span>
                        @empty
                            <span class="muted small">Belum dipilih</span>
                        @endforelse
                        @if($exam->classrooms_count > 4)<span class="muted small">+{{ $exam->classrooms_count - 4 }} kelas</span>@endif
                    </td>
                    <td><span class="badge {{ $exam->status }}">{{ $exam->operationalStatus() }}</span><br><span class="muted tiny">{{ $exam->status }}</span></td>
                    <td><span class="badge {{ $exam->hasGeneratedPackage() ? 'active' : 'warning' }}">{{ $exam->hasGeneratedPackage() ? 'siap' : 'belum' }}</span><br><span class="muted tiny">v{{ $exam->package_version }} · {{ $exam->package_downloads_count ?? 0 }} dl</span></td>
                    <td>{{ $exam->questions_count }}</td>
                    <td>{{ $exam->participants_count }}</td>
                    <td class="row"><a class="btn soft" href="{{ route('exams.show', $exam) }}">Kelola</a><a class="btn" href="{{ route('exams.builder', $exam) }}">Builder</a><a class="btn" href="{{ route('exams.monitor', $exam) }}">Monitor</a></td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="8">Belum ada ujian.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-meta between">
        <div class="small muted">Terlihat di halaman ini: <b data-live-count="examsTable">{{ $exams->count() }}</b> baris</div>
        <div>{{ $exams->links() }}</div>
    </div>
</div>
@endsection
