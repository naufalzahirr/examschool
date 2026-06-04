@extends('layouts.app', ['title' => 'Peserta ' . $exam->title])

@section('content')
<div class="between mb">
    <div>
        <h1>Peserta Ujian</h1>
        <p class="muted">{{ $exam->title }} · Kode: <b>{{ $exam->access_code }}</b></p>
    </div>
    <div class="row">
        <a class="btn soft" href="{{ route('exams.monitor', $exam) }}">Monitor</a>
        <a class="btn" href="{{ route('exams.show', $exam) }}">Kembali</a>
    </div>
</div>

{{-- Kelas terpilih + aksi sinkron --}}
<div class="card mb">
    <div class="between">
        <div>
            <h2 class="mb0">Kelas Peserta</h2>
            <p class="muted small mb0">Peserta diambil otomatis dari siswa aktif di kelas ini. Klik <b>Sinkron dari Kelas</b> setelah menambah kelas baru.</p>
        </div>
        <div class="row">
            <a class="btn soft" href="{{ route('exams.participants.import', $exam) }}">Import Peserta</a>
            <form method="POST" action="{{ route('exams.participants.syncClassrooms', $exam) }}">
                @csrf
                <button class="btn primary">↻ Sinkron dari Kelas</button>
            </form>
        </div>
    </div>
    <div class="row" style="margin-top:1rem">
        @forelse($exam->classrooms as $classroom)
            <span class="badge">{{ $classroom->nama_kelas }}</span>
        @empty
            <span class="muted">Belum ada kelas. <a href="{{ route('exams.edit', $exam) }}">Pilih kelas →</a></span>
        @endforelse
    </div>
</div>

{{-- Tabel peserta --}}
<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Daftar Peserta</h2>
            <p class="muted small mb0">{{ $participants->total() }} peserta terdaftar.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('exams.participants', $exam) }}">
            <div class="tool-field">
                <label>Status</label>
                <select class="input" name="status" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    <option value="assigned" @selected(request('status') === 'assigned')>Belum login</option>
                    <option value="download_ready" @selected(request('status') === 'download_ready')>Siap download</option>
                    <option value="downloaded" @selected(request('status') === 'downloaded')>Paket terunduh</option>
                    <option value="unlocked" @selected(request('status') === 'unlocked')>Soal terbuka</option>
                    <option value="in_progress" @selected(request('status') === 'in_progress')>Sedang mengerjakan</option>
                    <option value="locked" @selected(request('status') === 'locked')>Terkunci/Pelanggaran</option>
                    <option value="synced" @selected(request('status') === 'synced')>Tersinkron</option>
                    <option value="submitted" @selected(request('status') === 'submitted')>Sudah submit</option>
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
                <div class="live-search-wrap">
                    <input class="input" data-live-search="participantsTable" name="q" value="{{ request('q') }}" placeholder="Cari NIS, nama, kelas">
                </div>
            </div>
            <button class="btn primary">Cari</button>
            @if(request('q') || request('status') || request('classroom_id'))
                <a class="btn" href="{{ route('exams.participants', $exam) }}">Reset</a>
            @else
                <button class="btn" type="button" data-live-reset="participantsTable">Clear</button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="table" id="participantsTable">
            <thead>
                <tr>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Nilai</th>
                    <th>Submit</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($participants as $p)
                @php
                $statusLabel = match($p->status) {
                    'assigned'       => 'Belum login',
                    'download_ready' => 'Siap download',
                    'downloading'    => 'Mengunduh...',
                    'downloaded'     => 'Paket terunduh',
                    'unlocked'       => 'Soal terbuka',
                    'in_progress'    => 'Mengerjakan',
                    'synced'         => 'Tersinkron',
                    'submitted'      => 'Sudah submit',
                    default          => $p->status,
                };
                @endphp
                <tr>
                    <td><span class="badge">{{ $p->student?->nis ?: '-' }}</span></td>
                    <td><b>{{ $p->student?->name ?: 'Siswa dihapus' }}</b></td>
                    <td>{{ $p->student?->classroom?->nama_kelas ?: ($p->student?->class_name ?: '-') }}</td>
                    <td><span class="badge {{ $p->status }}">{{ $statusLabel }}</span></td>
                    <td><b>{{ $p->score ?? '-' }}</b></td>
                    <td class="small">{{ optional($p->submitted_at)->format('d M Y H:i') ?: '-' }}</td>
                    <td class="row">
                        {{-- Reset Device: hanya hapus kunci perangkat, attempt tetap ada --}}
                        <form method="POST" action="{{ route('exams.participants.resetDevice', [$exam, $p]) }}"
                              onsubmit="return confirm('Reset perangkat?\n\nIni HANYA menghapus kunci HP siswa agar bisa login dari perangkat berbeda.\nJawaban dan nilai TIDAK dihapus.\n\nLanjutkan?')">
                            @csrf
                            <button class="btn warning" title="Pakai jika siswa ganti HP atau aplikasi terkunci di perangkat lama">
                                Ganti HP
                            </button>
                        </form>
                        {{-- Reset Ujian: hapus semua (attempt, nilai, device) --}}
                        <form method="POST" action="{{ route('exams.participants.resetAttempt', [$exam, $p]) }}"
                              onsubmit="return confirm('Reset ujian siswa ini?\n\nIni MENGHAPUS:\n• Jawaban yang sudah dikerjakan\n• Nilai / skor\n• Status submit\n• Kunci perangkat\n\nSiswa harus mulai dari awal.\n\nYakin lanjutkan?')">
                            @csrf
                            <button class="btn danger" title="Hapus semua data ujian siswa ini — siswa mulai dari awal">
                                Ulangi Ujian
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="7">Belum ada peserta.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-meta between">
        <div class="small muted">
            Terlihat: <b data-live-count="participantsTable">{{ $participants->count() }}</b> baris
            &nbsp;·&nbsp;
            <span class="help">
                <b>Ganti HP</b>: hanya hapus kunci perangkat.
                <b>Ulangi Ujian</b>: hapus semua jawaban & nilai, siswa mulai dari nol.
            </span>
        </div>
        <div>{{ $participants->links() }}</div>
    </div>
</div>
@endsection
