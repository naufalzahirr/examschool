@extends('layouts.app', ['title' => 'Akun Siswa'])

@section('content')
<div class="between mb">
    <div>
        <h1>Akun Siswa</h1>
        <p class="muted">Data siswa mengikuti format SILAP. Pencarian, filter kelas, pagination, dan tabel sekarang digabung dalam satu live table.</p>
    </div>
    <div class="row">
        <a class="btn soft" href="{{ route('students.import') }}">Import Manual</a>
        <a class="btn primary" href="{{ route('students.create') }}">+ Tambah Siswa</a>
    </div>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Daftar Siswa</h2>
            <p class="muted small mb0">Menampilkan {{ $students->firstItem() ?? 0 }}-{{ $students->lastItem() ?? 0 }} dari {{ $students->total() }} siswa. Ketik untuk filter halaman ini, tekan Enter/Cari untuk pencarian database.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('students.index') }}">
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
                    <input class="input" data-live-search="studentsTable" name="q" value="{{ request('q') }}" placeholder="Cari NIS, nama, atau kelas">
                </div>
            </div>
            <button class="btn primary">Cari</button>
            @if(request('q') || request('classroom_id'))
                <a class="btn" href="{{ route('students.index') }}">Reset</a>
            @else
                <button class="btn" type="button" data-live-reset="studentsTable">Clear</button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="table" id="studentsTable">
            <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>JK</th><th>Status</th><th>Sumber</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($students as $student)
                <tr>
                    <td><span class="badge">{{ $student->nis }}</span></td>
                    <td><b>{{ $student->name }}</b></td>
                    <td>{{ $student->classroom?->nama_kelas ?: ($student->class_name ?: '-') }}</td>
                    <td>{{ $student->jenis_kelamin ?: '-' }}</td>
                    <td><span class="badge {{ $student->is_active ? 'active' : 'inactive' }}">{{ $student->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="small muted">{{ $student->silap_id ? 'SILAP' : 'Manual' }}</td>
                    <td class="row">
                        <a class="btn soft" href="{{ route('students.edit', $student) }}">Edit</a>
                        <form method="POST" action="{{ route('students.destroy', $student) }}"
                              onsubmit="return confirm('Hapus akun siswa {{ $student->name }}?\n\nSemua data peserta ujian terkait juga akan terhapus.\nYakin lanjutkan?')">
                            @csrf @method('DELETE')
                            <button class="btn danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="8">Belum ada akun siswa.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-meta between">
        <div class="small muted">Terlihat di halaman ini: <b data-live-count="studentsTable">{{ $students->count() }}</b> baris</div>
        <div>{{ $students->links() }}</div>
    </div>
</div>
@endsection
