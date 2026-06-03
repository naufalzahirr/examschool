@extends('layouts.app', ['title' => 'Kelas'])

@section('content')
<div class="between mb">
    <div>
        <h1>Data Kelas</h1>
        <p class="muted">Kelas mengikuti format <b>classrooms</b> dari SILAP dan dipakai sebagai pilihan multi-kelas saat membuat ujian.</p>
    </div>
    <a class="btn soft" href="{{ route('classrooms.import') }}">Import Manual</a>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Daftar Kelas</h2>
            <p class="muted small mb0">Menampilkan {{ $classrooms->firstItem() ?? 0 }}-{{ $classrooms->lastItem() ?? 0 }} dari {{ $classrooms->total() }} kelas.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('classrooms.index') }}">
            <div class="tool-field search">
                <label>Live Search</label>
                <div class="live-search-wrap">
                    <input class="input" data-live-search="classroomsTable" name="q" value="{{ request('q') }}" placeholder="Cari nama kelas, ID, term, tingkat">
                </div>
            </div>
            <button class="btn primary">Cari</button>
            @if(request('q'))
                <a class="btn" href="{{ route('classrooms.index') }}">Reset</a>
            @else
                <button class="btn" type="button" data-live-reset="classroomsTable">Clear</button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="table" id="classroomsTable">
            <thead><tr><th>ID SILAP</th><th>Nama Kelas</th><th>Tingkat</th><th>Term</th><th>Jumlah Siswa</th><th>Update</th></tr></thead>
            <tbody>
            @forelse($classrooms as $classroom)
                <tr>
                    <td><span class="badge">{{ $classroom->id }}</span></td>
                    <td><b>{{ $classroom->nama_kelas }}</b></td>
                    <td>{{ $classroom->tingkat ?: '-' }}</td>
                    <td>{{ $classroom->term_id ?: '-' }}</td>
                    <td>{{ $classroom->students_count }}</td>
                    <td class="small">{{ optional($classroom->updated_at)->format('d M Y H:i') ?: '-' }}</td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="6">Belum ada data kelas. Sinkron dari SILAP atau import manual terlebih dahulu.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-meta between">
        <div class="small muted">Terlihat di halaman ini: <b data-live-count="classroomsTable">{{ $classrooms->count() }}</b> baris</div>
        <div>{{ $classrooms->links() }}</div>
    </div>
</div>
@endsection
