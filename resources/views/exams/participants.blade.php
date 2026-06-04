@extends('layouts.app', ['title' => 'Peserta ' . $exam->title])

@section('content')
<div class="between mb">
    <div>
        <h1>Peserta Ujian</h1>
        <p class="muted">{{ $exam->title }} · Kode: <b>{{ $exam->access_code }}</b>. Siswa login di HP memakai kode ujian, NIS, dan password.</p>
    </div>
    <div class="row"><a class="btn soft" href="{{ route('exams.monitor', $exam) }}">Monitor</a><a class="btn" href="{{ route('exams.show', $exam) }}">Kembali</a></div>
</div>

<div class="card mb">
    <div class="between">
        <div>
            <h2 class="mb0">Kelas Terpilih</h2>
            <p class="muted small mb0">Peserta bisa otomatis diambil dari siswa aktif pada kelas-kelas ini.</p>
        </div>
        <div class="row">
            <a class="btn soft" href="{{ route('exams.participants.import', $exam) }}">Import Peserta</a>
            <form method="POST" action="{{ route('exams.participants.syncClassrooms', $exam) }}">
                @csrf
                <button class="btn primary">Sinkron Peserta dari Kelas</button>
            </form>
        </div>
    </div>
    <div class="row" style="margin-top:1rem">
        @forelse($exam->classrooms as $classroom)
            <span class="badge">{{ $classroom->nama_kelas }}</span>
        @empty
            <span class="muted">Belum ada kelas terpilih. Edit konfigurasi ujian untuk memilih kelas.</span>
        @endforelse
    </div>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Daftar Peserta</h2>
            <p class="muted small mb0">Reset perangkat dipakai jika siswa ganti HP atau aplikasi terkunci di device lama.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('exams.participants', $exam) }}">
            <div class="tool-field">
                <label>Status</label>
                <select class="input" name="status" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach(['assigned' => 'Belum login', 'download_ready' => 'Login/siap download', 'downloaded' => 'Paket terunduh', 'in_progress' => 'Mengerjakan', 'synced' => 'Tersinkron', 'submitted' => 'Submit'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Kelas</label>
                <select class="input" name="classroom_id" onchange="this.form.submit()">
                    <option value="">Semua kelas</option>
                    @foreach($exam->classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field search">
                <label>Cari Cepat</label>
                <div class="live-search-wrap"><input class="input" data-live-search="participantsTable" name="q" value="{{ request('q') }}" placeholder="Cari NIS, nama, kelas, device"></div>
            </div>
            <button class="btn primary">Cari</button>
            @if(request('q') || request('status') || request('classroom_id'))<a class="btn" href="{{ route('exams.participants', $exam) }}">Reset</a>@else<button class="btn" type="button" data-live-reset="participantsTable">Clear</button>@endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="table" id="participantsTable">
            <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Classroom</th><th>Status</th><th>Device</th><th>Nilai</th><th>Submit</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($participants as $p)
                <tr>
                    <td><span class="badge">{{ $p->student?->nis ?: '-' }}</span></td>
                    <td><b>{{ $p->student?->name ?: 'Siswa dihapus' }}</b></td>
                    <td>{{ $p->student?->classroom?->nama_kelas ?: ($p->student?->class_name ?: '-') }}</td>
                    <td>{{ $p->student?->classroom_id ?: '-' }}</td>
                    <td><span class="badge {{ $p->status }}">{{ $p->status }}</span></td>
                    <td class="small">{{ $p->device_id ?: '-' }}</td>
                    <td>{{ $p->score ?? '-' }}</td>
                    <td>{{ optional($p->submitted_at)->format('d M Y H:i') ?: '-' }}</td>
                    <td class="row">
                        <form method="POST" action="{{ route('exams.participants.resetDevice', [$exam, $p]) }}" onsubmit="return confirm('Reset kunci perangkat siswa ini?')">
                            @csrf
                            <button class="btn warning">Reset Device</button>
                        </form>
                        <form method="POST" action="{{ route('exams.participants.resetAttempt', [$exam, $p]) }}" onsubmit="return confirm('Reset attempt dan nilai siswa ini?')">
                            @csrf
                            <button class="btn danger">Reset Ujian</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="9">Belum ada peserta.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-meta between">
        <div class="small muted">Terlihat di halaman ini: <b data-live-count="participantsTable">{{ $participants->count() }}</b> baris</div>
        <div>{{ $participants->links() }}</div>
    </div>
</div>
@endsection
