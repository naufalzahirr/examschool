@extends('layouts.app', ['title' => 'Monitor ' . $exam->title])

@section('content')
<div class="between mb">
    <div>
        <h1>Monitor Pelaksanaan</h1>
        <p class="muted">{{ $exam->title }} · Kode: <b>{{ $exam->access_code }}</b>. Halaman ini untuk pengawas saat ujian berlangsung.</p>
    </div>
    <div class="row">
        @if(auth()->user()->canManageExam($exam))<a class="btn" href="{{ route('exams.show', $exam) }}">Kembali</a>@endif
        <a class="btn soft" href="{{ route('exams.monitor', $exam) }}">Refresh</a>
    </div>
</div>

<div class="grid mb">
    <div class="card"><div class="muted">Total Peserta</div><div class="stat">{{ $exam->participants_count }}</div></div>
    <div class="card"><div class="muted">Belum Login</div><div class="stat">{{ $statusCounts['assigned'] ?? 0 }}</div></div>
    <div class="card"><div class="muted">Antrean Aktif</div><div class="stat">{{ $queueStats['active'] }} / {{ $queueStats['limit'] }}</div></div>
    <div class="card"><div class="muted">Sudah Download</div><div class="stat">{{ $queueStats['downloaded'] }}</div></div>
    <div class="card"><div class="muted">Submit</div><div class="stat">{{ $statusCounts['submitted'] ?? 0 }}</div></div>
    <div class="card"><div class="muted">Terkunci</div><div class="stat">{{ $integrityStats['locked'] ?? 0 }}</div></div>
    <div class="card"><div class="muted">Event Pelanggaran</div><div class="stat">{{ $integrityStats['events'] ?? 0 }}</div></div>
</div>

<div class="card mb">
    <div class="between">
        <div>
            <h2 class="mb0">Status Ujian</h2>
            <p class="muted small mb0">Status server: <b>{{ $exam->status }}</b>. Jadwal: {{ optional($exam->starts_at)->format('d M Y H:i') ?: 'fleksibel' }} - {{ optional($exam->ends_at)->format('d M Y H:i') ?: 'fleksibel' }}. Download paket: {{ $queueStats['download_window_open'] ? 'sudah dibuka' : 'dibuka '.$queueStats['download_opens_at'] }}.</p>
        </div>
        <span class="badge {{ $exam->isOpenNow() ? 'active' : 'warning' }}">{{ $exam->isOpenNow() ? 'ujian sedang dibuka' : 'ujian belum/tidak dibuka' }}</span>
    </div>
</div>

<div class="card mb">
    <div class="between">
        <div>
            <h2 class="mb0">Integritas & Kunci Ujian</h2>
            <p class="muted small mb0">Pantau siswa yang ujiannya terkunci karena keluar aplikasi atau internet aktif. Saat HP benar-benar mode pesawat, event akan masuk setelah siswa upload jawaban.</p>
        </div>
        <span class="badge {{ ($integrityStats['locked'] ?? 0) > 0 ? 'warning' : 'active' }}">{{ ($integrityStats['locked'] ?? 0) > 0 ? 'perlu pengawas' : 'normal' }}</span>
    </div>
    <div class="grid mt">
        <div class="mini-card"><div class="muted small">Sedang Terkunci</div><div class="stat">{{ $integrityStats['locked'] ?? 0 }}</div></div>
        <div class="mini-card"><div class="muted small">Total Event</div><div class="stat">{{ $integrityStats['events'] ?? 0 }}</div></div>
        <div class="mini-card"><div class="muted small">Keluar/Minimize</div><div class="stat">{{ $integrityStats['app_left'] ?? 0 }}</div></div>
        <div class="mini-card"><div class="muted small">Internet Aktif</div><div class="stat">{{ $integrityStats['internet_active'] ?? 0 }}</div></div>
    </div>
</div>

<div class="card mb">
    <div class="between">
        <div>
            <h2 class="mb0">Antrean Download Paket</h2>
            <p class="muted small mb0">Model 50/399: hanya sejumlah slot aktif yang boleh mengambil file paket terenkripsi. Sisanya menunggu giliran.</p>
        </div>
        <span class="badge {{ $queueStats['download_window_open'] ? 'active' : 'warning' }}">{{ $queueStats['download_window_open'] ? 'download dibuka' : 'download belum dibuka' }}</span>
    </div>
    <div class="grid mt">
        <div class="mini-card"><div class="muted small">Slot Aktif</div><div class="stat">{{ $queueStats['active'] }} / {{ $queueStats['limit'] }}</div></div>
        <div class="mini-card"><div class="muted small">Menunggu</div><div class="stat">{{ $queueStats['waiting'] }}</div></div>
        <div class="mini-card"><div class="muted small">Sudah Download</div><div class="stat">{{ $queueStats['downloaded'] }}</div></div>
        <div class="mini-card"><div class="muted small">Sudah Unlock</div><div class="stat">{{ $queueStats['unlocked'] }}</div></div>
    </div>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Aktivitas Peserta</h2>
            <p class="muted small mb0">Urut dari aktivitas terbaru. Gunakan reset device hanya jika siswa memang ganti HP/perangkat.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('exams.monitor', $exam) }}">
            <div class="tool-field">
                <label>Status</label>
                <select class="input" name="status" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach(['assigned' => 'Belum login', 'download_ready' => 'Login/siap download', 'downloading' => 'Sedang download', 'downloaded' => 'Paket terunduh', 'unlocked' => 'Soal terbuka', 'in_progress' => 'Mengerjakan', 'locked' => 'Terkunci/Pelanggaran', 'synced' => 'Tersinkron', 'submitted' => 'Submit'] as $value => $label)
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
                <div class="live-search-wrap"><input class="input" data-live-search="monitorTable" name="q" value="{{ request('q') }}" placeholder="Cari NIS, nama, kelas, device"></div>
            </div>
            <button class="btn primary">Cari</button>
            @if(auth()->user()->canManageExam($exam))<a class="btn soft" href="{{ route('exams.results.export', $exam) }}">Export CSV</a>@endif
            @if(request('q') || request('status') || request('classroom_id'))<a class="btn" href="{{ route('exams.monitor', $exam) }}">Reset</a>@else<button class="btn" type="button" data-live-reset="monitorTable">Clear</button>@endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="table" id="monitorTable">
            <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th><th>Integritas</th><th>Download</th><th>Unlock</th><th>Device</th><th>Mulai</th><th>Last Sync</th><th>Submit</th><th>Nilai</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($participants as $p)
                @php($last = $p->attempts->first())
                <tr>
                    <td><span class="badge">{{ $p->student?->nis ?: '-' }}</span></td>
                    <td><b>{{ $p->student?->name ?: 'Siswa dihapus' }}</b></td>
                    <td>{{ $p->student?->classroom?->nama_kelas ?: ($p->student?->class_name ?: '-') }}</td>
                    <td><span class="badge {{ $p->status }}">{{ $p->status }}</span></td>
                    @php($summary = $p->meta['integrity_summary'] ?? [])
                    @php($lastIntegrity = $p->meta['last_integrity_event'] ?? null)
                    <td class="small">
                        @if(($summary['total'] ?? 0) > 0)
                            <span class="badge warning">{{ $summary['total'] }} event</span><br>
                            <span class="muted">{{ $summary['last_reason'] ?? ($lastIntegrity['reason'] ?? '-') }}</span>
                            @if(!empty($summary['last_at']))<br><span class="muted">{{ \Illuminate\Support\Carbon::parse($summary['last_at'])->format('H:i:s') }}</span>@endif
                        @elseif($p->status === 'locked')
                            <span class="badge warning">terkunci</span>
                        @else
                            <span class="muted">normal</span>
                        @endif
                    </td>
                    <td class="small">{{ optional($p->package_download_finished_at)->format('H:i:s') ?: (optional($p->package_queue_started_at)->format('H:i:s') ?: '-') }}</td>
                    <td class="small">{{ optional($p->package_unlock_key_issued_at)->format('H:i:s') ?: '-' }}</td>
                    <td class="small">{{ $p->device_id ?: '-' }}</td>
                    <td>{{ optional($last?->started_at)->format('H:i:s') ?: '-' }}</td>
                    <td>{{ optional($last?->last_synced_at)->format('H:i:s') ?: '-' }}</td>
                    <td>{{ optional($p->submitted_at)->format('H:i:s') ?: '-' }}</td>
                    <td><b>{{ $p->score ?? '-' }}</b></td>
                    <td class="row">
                        @if(auth()->user()->canManageExam($exam))
                        <form method="POST" action="{{ route('exams.participants.resetDevice', [$exam, $p]) }}" onsubmit="return confirm('Reset kunci perangkat siswa ini?')">
                            @csrf
                            <button class="btn warning">Reset Device</button>
                        </form>
                        @else
                            <span class="muted small">Monitor saja</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="13">Belum ada peserta.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-meta between">
        <div class="small muted">Terlihat di halaman ini: <b data-live-count="monitorTable">{{ $participants->count() }}</b> baris</div>
        <div>{{ $participants->links() }}</div>
    </div>
</div>
@endsection
