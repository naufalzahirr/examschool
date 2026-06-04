@extends('layouts.app', ['title' => 'Monitor ' . $exam->title])

@push('head')
<style>
.progress-bar-wrap{background:#ebeef0;border-radius:999px;height:10px;overflow:hidden;margin-top:.35rem}
.progress-bar-fill{height:100%;border-radius:999px;background:var(--success);transition:width .4s}
.refresh-badge{font-size:12px;font-weight:800;padding:.3rem .7rem;border-radius:999px;background:var(--info-soft);color:#0787a1}
</style>
@endpush

@section('content')
<div class="between mb">
    <div>
        <h1>Monitor Pelaksanaan</h1>
        <p class="muted">{{ $exam->title }} · Kode: <b>{{ $exam->access_code }}</b></p>
    </div>
    <div class="row">
        @if(auth()->user()->canManageExam($exam))
            <a class="btn" href="{{ route('exams.show', $exam) }}">Kembali</a>
        @endif
        <span class="refresh-badge" id="refreshCountdown">Auto-refresh: 30s</span>
        <a class="btn primary" href="{{ route('exams.monitor', $exam) }}" style="font-weight:900">↻ Refresh Sekarang</a>
    </div>
</div>

{{-- Status ujian ringkas --}}
<div class="card mb">
    <div class="between">
        <div>
            <b>Status server:</b> {{ $exam->operationalStatus() }} &nbsp;·&nbsp;
            <b>Jadwal:</b> {{ optional($exam->starts_at)->format('d M Y H:i') ?: 'fleksibel' }}
            – {{ optional($exam->ends_at)->format('H:i') ?: 'fleksibel' }} &nbsp;·&nbsp;
            <b>Download:</b>
            @if($queueStats['download_window_open'])
                sedang dibuka
            @elseif($queueStats['download_opens_at'])
                dibuka {{ $queueStats['download_opens_at'] }}
            @else
                dibuka 12 jam sebelum mulai
            @endif
        </div>
        <span class="badge {{ $exam->isOpenNow() ? 'published' : 'warning' }}">
            {{ $exam->isOpenNow() ? 'Ujian Sedang Dibuka' : 'Belum / Tidak Dibuka' }}
        </span>
    </div>
</div>

{{-- Progress submit --}}
@php
$total     = $exam->participants_count ?: 1;
$submitted = $statusCounts['submitted'] ?? 0;
$progress  = $total > 0 ? round($submitted / $total * 100) : 0;
$inProg    = ($statusCounts['in_progress'] ?? 0) + ($statusCounts['synced'] ?? 0);
@endphp
<div class="card mb">
    <div class="between" style="margin-bottom:.5rem">
        <b>Progress Submit</b>
        <span class="muted small">{{ $submitted }} / {{ $exam->participants_count }} sudah submit ({{ $progress }}%)</span>
    </div>
    <div class="progress-bar-wrap">
        <div class="progress-bar-fill" style="width:{{ $progress }}%"></div>
    </div>
    <div class="row" style="margin-top:.75rem;flex-wrap:wrap">
        <span class="badge">{{ $statusCounts['assigned'] ?? 0 }} belum login</span>
        <span class="badge info">{{ $queueStats['downloaded'] }} terunduh</span>
        <span class="badge warning">{{ $inProg }} mengerjakan</span>
        <span class="badge published">{{ $submitted }} submit</span>
        @if(($integrityStats['locked'] ?? 0) > 0)
            <span class="badge danger">{{ $integrityStats['locked'] }} terkunci</span>
        @endif
    </div>
</div>

<div class="two mb">
    {{-- Antrean download --}}
    <div class="card">
        <div class="between mb">
            <div>
                <h2 class="mb0">Antrean Download</h2>
                <p class="muted small mb0">Slot bersamaan dibatasi agar server tidak kewalahan.</p>
            </div>
            <span class="badge {{ $queueStats['download_window_open'] ? 'published' : 'warning' }}">
                {{ $queueStats['download_window_open'] ? 'Dibuka' : 'Belum Dibuka' }}
            </span>
        </div>
        <div class="grid">
            <div class="mini-card"><div class="muted small">Slot Aktif</div><div class="stat">{{ $queueStats['active'] }} / {{ $queueStats['limit'] }}</div></div>
            <div class="mini-card"><div class="muted small">Menunggu</div><div class="stat">{{ $queueStats['waiting'] }}</div></div>
            <div class="mini-card"><div class="muted small">Terunduh</div><div class="stat">{{ $queueStats['downloaded'] }}</div></div>
            <div class="mini-card"><div class="muted small">Soal Terbuka</div><div class="stat">{{ $queueStats['unlocked'] }}</div></div>
        </div>
    </div>

    {{-- Integritas --}}
    <div class="card">
        <div class="between mb">
            <div>
                <h2 class="mb0">Integritas Ujian</h2>
                <p class="muted small mb0">Pelanggaran terdeteksi oleh aplikasi siswa secara otomatis.</p>
            </div>
            <span class="badge {{ ($integrityStats['locked'] ?? 0) > 0 ? 'warning' : 'published' }}">
                {{ ($integrityStats['locked'] ?? 0) > 0 ? 'Perlu Perhatian' : 'Normal' }}
            </span>
        </div>
        <div class="grid">
            <div class="mini-card"><div class="muted small">Sedang Terkunci</div><div class="stat">{{ $integrityStats['locked'] ?? 0 }}</div></div>
            <div class="mini-card"><div class="muted small">Total Event</div><div class="stat">{{ $integrityStats['events'] ?? 0 }}</div></div>
            <div class="mini-card"><div class="muted small">Keluar Aplikasi</div><div class="stat">{{ $integrityStats['app_left'] ?? 0 }}</div></div>
            <div class="mini-card"><div class="muted small">Internet Aktif</div><div class="stat">{{ $integrityStats['internet_active'] ?? 0 }}</div></div>
        </div>
    </div>
</div>

{{-- Tabel peserta --}}
<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Aktivitas Peserta</h2>
            <p class="muted small mb0">Diurutkan dari aktivitas terbaru. Waktu menggunakan jam lokal server.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('exams.monitor', $exam) }}">
            <div class="tool-field">
                <label>Status</label>
                <select class="input" name="status" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    <option value="assigned"       @selected(request('status') === 'assigned')>Belum login</option>
                    <option value="download_ready" @selected(request('status') === 'download_ready')>Siap download</option>
                    <option value="downloading"    @selected(request('status') === 'downloading')>Sedang download</option>
                    <option value="downloaded"     @selected(request('status') === 'downloaded')>Paket terunduh</option>
                    <option value="unlocked"       @selected(request('status') === 'unlocked')>Soal terbuka</option>
                    <option value="in_progress"    @selected(request('status') === 'in_progress')>Mengerjakan</option>
                    <option value="locked"         @selected(request('status') === 'locked')>Terkunci/Pelanggaran</option>
                    <option value="synced"         @selected(request('status') === 'synced')>Tersinkron</option>
                    <option value="submitted"      @selected(request('status') === 'submitted')>Sudah submit</option>
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
                    <input class="input" data-live-search="monitorTable" name="q" value="{{ request('q') }}" placeholder="Cari NIS, nama, kelas">
                </div>
            </div>
            <button class="btn primary">Cari</button>
            @if(auth()->user()->canManageExam($exam))
                <a class="btn soft" href="{{ route('exams.results.export', $exam) }}">Export CSV</a>
            @endif
            @if(request('q') || request('status') || request('classroom_id'))
                <a class="btn" href="{{ route('exams.monitor', $exam) }}">Reset</a>
            @else
                <button class="btn" type="button" data-live-reset="monitorTable">Clear</button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="table" id="monitorTable">
            <thead>
                <tr>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Integritas</th>
                    <th>Mulai</th>
                    <th>Last Sync</th>
                    <th>Submit</th>
                    <th>Nilai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($participants as $p)
                @php
                $last = $p->attempts->first();
                $statusLabel = match($p->status) {
                    'assigned'       => 'Belum login',
                    'download_ready' => 'Siap download',
                    'downloading'    => 'Mengunduh',
                    'downloaded'     => 'Terunduh',
                    'unlocked'       => 'Soal terbuka',
                    'in_progress'    => 'Mengerjakan',
                    'locked'         => 'Terkunci',
                    'synced'         => 'Tersinkron',
                    'submitted'      => 'Submit',
                    default          => $p->status,
                };
                $summary = $p->meta['integrity_summary'] ?? [];
                @endphp
                <tr>
                    <td><span class="badge">{{ $p->student?->nis ?: '-' }}</span></td>
                    <td><b>{{ $p->student?->name ?: 'Siswa dihapus' }}</b></td>
                    <td class="small">{{ $p->student?->classroom?->nama_kelas ?: ($p->student?->class_name ?: '-') }}</td>
                    <td><span class="badge {{ $p->status }}">{{ $statusLabel }}</span></td>
                    <td class="small">
                        @if(($summary['total'] ?? 0) > 0)
                            <span class="badge warning">{{ $summary['total'] }} event</span>
                            <br><span class="muted tiny">{{ $summary['last_reason'] ?? '-' }}</span>
                        @elseif($p->status === 'locked')
                            <span class="badge warning">terkunci</span>
                        @else
                            <span class="muted">–</span>
                        @endif
                    </td>
                    <td class="small">{{ optional($last?->started_at)->format('H:i') ?: '-' }}</td>
                    <td class="small">{{ optional($last?->last_synced_at)->format('H:i') ?: '-' }}</td>
                    <td class="small">{{ optional($p->submitted_at)->format('H:i') ?: '-' }}</td>
                    <td><b>{{ $p->score ?? '-' }}</b></td>
                    <td class="row">
                        @if(auth()->user()->canManageExam($exam))
                            <form method="POST" action="{{ route('exams.participants.resetDevice', [$exam, $p]) }}"
                                  onsubmit="return confirm('Reset perangkat siswa ini?\nHanya menghapus kunci HP, jawaban TIDAK dihapus.')">
                                @csrf
                                <button class="btn warning" title="Pakai jika siswa ganti HP">Ganti HP</button>
                            </form>
                        @else
                            <span class="muted small">–</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="10">Belum ada peserta.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-meta between">
        <div class="small muted">
            Terlihat: <b data-live-count="monitorTable">{{ $participants->count() }}</b> baris
        </div>
        <div>{{ $participants->links() }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh setiap 30 detik, dengan countdown
(function(){
    let remaining = 30;
    const badge = document.getElementById('refreshCountdown');
    if(!badge) return;
    const interval = setInterval(function(){
        remaining--;
        badge.textContent = 'Auto-refresh: ' + remaining + 's';
        if(remaining <= 0){
            clearInterval(interval);
            window.location.reload();
        }
    }, 1000);
})();
</script>
@endpush
