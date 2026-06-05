@extends('layouts.app', ['title' => 'Monitor — ' . $exam->title])

@push('head')
<style>
.monitor-hero{background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);border-radius:var(--radius);padding:1.45rem 1.6rem;margin-bottom:1.25rem;color:#fff;position:relative;overflow:hidden}
.monitor-hero:after{content:'';position:absolute;right:-40px;bottom:-40px;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(20,184,166,.25),transparent 70%);pointer-events:none}
.monitor-hero h1{color:#fff;margin:0 0 .3rem;font-size:22px}
.pulse{width:10px;height:10px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.3);animation:pulse-anim 1.6s infinite;display:inline-block}
@keyframes pulse-anim{0%,100%{box-shadow:0 0 0 3px rgba(34,197,94,.3)}50%{box-shadow:0 0 0 8px rgba(34,197,94,.08)}}
.pulse-red{background:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.3)}
@keyframes pulse-red-anim{0%,100%{box-shadow:0 0 0 3px rgba(239,68,68,.3)}50%{box-shadow:0 0 0 8px rgba(239,68,68,.08)}}
.stat-tile{border-radius:var(--radius);padding:1rem;text-align:center;border:1px solid var(--line);background:#fff}
.stat-tile .val{font-size:30px;font-weight:950;line-height:1;color:var(--heading)}
.stat-tile .lbl{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:900;margin-top:.25rem}
.progress-track{background:#e2e8f0;border-radius:999px;height:12px;overflow:hidden;border:1px solid var(--line)}
.progress-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--primary),#10b981);transition:width .5s ease}
.countdown-badge{background:#1e293b;color:#94a3b8;font-size:12px;font-weight:900;padding:.3rem .75rem;border-radius:999px}
.status-dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0}
.integrity-alert{background:linear-gradient(135deg,#fef2f2,#fff5f5);border:1px solid #fecaca;border-radius:var(--radius);padding:.9rem 1.1rem;display:flex;align-items:center;gap:.85rem}
</style>
@endpush

@section('content')

@php
$total     = $exam->participants_count ?: 1;
$submitted = $statusCounts['submitted'] ?? 0;
$inProg    = ($statusCounts['in_progress'] ?? 0) + ($statusCounts['synced'] ?? 0);
$locked    = $integrityStats['locked'] ?? 0;
$progress  = $total > 0 ? round($submitted / $total * 100) : 0;
@endphp

{{-- ═══ MONITOR HERO ═══ --}}
<div class="monitor-hero">
    <div class="between" style="align-items:flex-start">
        <div>
            <div class="row" style="margin-bottom:.5rem;gap:.5rem">
                @if($exam->isOpenNow())
                    <span class="pulse"></span>
                    <span style="font-size:12px;font-weight:900;color:#4ade80">Ujian Sedang Berjalan</span>
                @else
                    <span style="font-size:12px;font-weight:900;color:#94a3b8">{{ $exam->operationalStatus() }}</span>
                @endif
            </div>
            <h1>{{ $exam->title }}</h1>
            <p style="margin:0;opacity:.7;font-size:13px">
                Kode: <b style="color:#fff">{{ $exam->access_code }}</b>
                &nbsp;·&nbsp; {{ optional($exam->starts_at)->format('d M Y H:i') ?: 'Jadwal fleksibel' }}
                @if($exam->ends_at) – {{ $exam->ends_at->format('H:i') }} @endif
                &nbsp;·&nbsp; Download:
                @if($queueStats['download_window_open'])
                    <span style="color:#4ade80">Dibuka</span>
                @elseif($queueStats['download_opens_at'])
                    <span>{{ $queueStats['download_opens_at'] }}</span>
                @else
                    <span>12 jam sebelum mulai</span>
                @endif
            </p>
        </div>
        <div class="row" style="gap:.5rem;flex-wrap:wrap">
            <span class="countdown-badge" id="refreshCountdown">↻ 30s</span>
            <a class="btn" href="{{ route('exams.monitor', $exam) }}" style="background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.2);font-size:13px">Refresh</a>
            @if(auth()->user()->canManageExam($exam))
                <a class="btn soft" href="{{ route('exams.show', $exam) }}" style="font-size:13px">← Detail Ujian</a>
            @endif
        </div>
    </div>
</div>

{{-- ═══ PROGRESS BAR ═══ --}}
<div class="card mb">
    <div class="between" style="margin-bottom:.75rem">
        <b style="font-size:15px">Progress Submit</b>
        <span class="muted small"><b>{{ $submitted }}</b> dari <b>{{ $exam->participants_count }}</b> peserta sudah submit — <b>{{ $progress }}%</b></span>
    </div>
    <div class="progress-track">
        <div class="progress-fill" style="width:{{ $progress }}%"></div>
    </div>
    <div class="row" style="margin-top:.85rem;flex-wrap:wrap">
        <span class="badge assigned">{{ $statusCounts['assigned'] ?? 0 }} belum login</span>
        <span class="badge download_ready">{{ $queueStats['downloaded'] }} terunduh</span>
        <span class="badge in_progress">{{ $inProg }} mengerjakan</span>
        <span class="badge submitted">{{ $submitted }} submit</span>
        @if($locked > 0)
            <span class="badge danger">{{ $locked }} terkunci</span>
        @endif
    </div>
</div>

{{-- ═══ INTEGRITY ALERT ═══ --}}
@if($locked > 0)
<div class="integrity-alert mb">
    <span style="font-size:24px">⚠️</span>
    <div>
        <b style="color:#991b1b">{{ $locked }} siswa terdeteksi pelanggaran integritas</b>
        <p class="small mb0" style="color:#b91c1c">Periksa detail di tabel peserta di bawah. Filter status "Terkunci" untuk melihat daftar lengkap.</p>
    </div>
</div>
@endif

{{-- ═══ 4-TILE STATS ═══ --}}
<div class="two mb">

    {{-- Antrean download --}}
    <div class="card">
        <div class="between mb" style="margin-bottom:.85rem">
            <div>
                <h2 class="mb0" style="font-size:15px">Antrean Download</h2>
                <p class="muted small mb0">Slot bersamaan dibatasi agar server tidak kewalahan</p>
            </div>
            <span class="badge {{ $queueStats['download_window_open'] ? 'published' : 'warning' }}" style="font-size:11px">
                {{ $queueStats['download_window_open'] ? 'Dibuka' : 'Belum Dibuka' }}
            </span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.65rem">
            <div class="stat-tile">
                <div class="val" style="color:var(--primary)">{{ $queueStats['active'] }}</div>
                <div class="lbl">Slot Aktif</div>
            </div>
            <div class="stat-tile">
                <div class="val" style="color:var(--warning)">{{ $queueStats['waiting'] }}</div>
                <div class="lbl">Antrean</div>
            </div>
            <div class="stat-tile">
                <div class="val" style="color:var(--violet)">{{ $queueStats['downloaded'] }}</div>
                <div class="lbl">Terunduh</div>
            </div>
            <div class="stat-tile">
                <div class="val" style="color:var(--success)">{{ $queueStats['unlocked'] }}</div>
                <div class="lbl">Terbuka</div>
            </div>
        </div>
        <div class="muted small" style="margin-top:.65rem;text-align:center">
            Kapasitas: {{ $queueStats['active'] }}/{{ $queueStats['limit'] }} slot
        </div>
    </div>

    {{-- Integritas --}}
    <div class="card">
        <div class="between mb" style="margin-bottom:.85rem">
            <div>
                <h2 class="mb0" style="font-size:15px">Integritas Ujian</h2>
                <p class="muted small mb0">Pelanggaran terdeteksi otomatis oleh aplikasi siswa</p>
            </div>
            <span class="badge {{ $locked > 0 ? 'danger' : 'published' }}" style="font-size:11px">
                {{ $locked > 0 ? 'Perlu Perhatian' : 'Normal' }}
            </span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.65rem">
            <div class="stat-tile">
                <div class="val" style="color:{{ $locked > 0 ? 'var(--danger)' : 'var(--success)' }}">{{ $locked }}</div>
                <div class="lbl">Terkunci</div>
            </div>
            <div class="stat-tile">
                <div class="val">{{ $integrityStats['events'] ?? 0 }}</div>
                <div class="lbl">Total Event</div>
            </div>
            <div class="stat-tile">
                <div class="val">{{ $integrityStats['app_left'] ?? 0 }}</div>
                <div class="lbl">Keluar App</div>
            </div>
            <div class="stat-tile">
                <div class="val">{{ $integrityStats['internet_active'] ?? 0 }}</div>
                <div class="lbl">Internet Aktif</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ PANDUAN CEPAT PENGAWAS ═══ --}}
@php
$notSubmitted = $exam->participants_count - $submitted;
@endphp
<div class="card mb" style="background:linear-gradient(135deg,#0f172a,#1e293b);border:none;padding:1.15rem 1.35rem">
    <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:.75rem">Ringkasan Status Saat Ini</div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.65rem">
        <div style="padding:.75rem;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.2);border-radius:10px;text-align:center">
            <div style="font-size:24px;font-weight:950;color:#4ade80">{{ $submitted }}</div>
            <div style="font-size:11px;color:#86efac">Sudah submit ✓</div>
        </div>
        <div style="padding:.75rem;background:rgba(251,191,36,.10);border:1px solid rgba(251,191,36,.2);border-radius:10px;text-align:center">
            <div style="font-size:24px;font-weight:950;color:#fbbf24">{{ $inProg }}</div>
            <div style="font-size:11px;color:#fcd34d">Sedang mengerjakan</div>
        </div>
        <div style="padding:.75rem;background:rgba(148,163,184,.08);border:1px solid rgba(148,163,184,.15);border-radius:10px;text-align:center">
            <div style="font-size:24px;font-weight:950;color:#94a3b8">{{ max(0,$notSubmitted - $inProg) }}</div>
            <div style="font-size:11px;color:#64748b">Belum mengerjakan</div>
        </div>
        <div style="padding:.75rem;background:rgba(239,68,68,.10);border:1px solid rgba(239,68,68,.2);border-radius:10px;text-align:center">
            <div style="font-size:24px;font-weight:950;color:#f87171">{{ $locked }}</div>
            <div style="font-size:11px;color:#fca5a5">Terkunci / Pelanggaran</div>
        </div>
    </div>
    @if($locked > 0)
    <div style="margin-top:.75rem;padding:.75rem 1rem;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);border-radius:10px;display:flex;align-items:center;gap:.75rem">
        <span style="font-size:18px">⚠️</span>
        <div>
            <div style="font-weight:900;color:#fca5a5;font-size:13px">{{ $locked }} siswa terkunci — kemungkinan terjadi pelanggaran integritas</div>
            <div style="font-size:12px;color:#94a3b8">Filter "Terkunci" di tabel bawah → gunakan tombol "Ganti HP" jika HP siswa bermasalah</div>
        </div>
    </div>
    @endif
</div>

{{-- ═══ TABEL PESERTA ═══ --}}
<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2 style="font-size:15px">Aktivitas Peserta</h2>
            <p class="muted small mb0">Diurutkan dari aktivitas terbaru · Waktu: jam server</p>
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
                    <option value="locked"         @selected(request('status') === 'locked')>Terkunci</option>
                    <option value="synced"         @selected(request('status') === 'synced')>Tersinkron</option>
                    <option value="submitted"      @selected(request('status') === 'submitted')>Sudah submit</option>
                </select>
            </div>
            <div class="tool-field">
                <label>Kelas</label>
                <select class="input" name="classroom_id" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($exam->classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field search">
                <label>Cari</label>
                <div class="live-search-wrap">
                    <input class="input" data-live-search="monitorTable" name="q" value="{{ request('q') }}" placeholder="NIS, nama, kelas...">
                </div>
            </div>
            <button class="btn primary" style="align-self:flex-end">Cari</button>
            @if(auth()->user()->canManageExam($exam))
                <a class="btn soft" href="{{ route('exams.results.export', $exam) }}" style="align-self:flex-end;font-size:13px">↓ Export CSV</a>
            @endif
            @if(request('q') || request('status') || request('classroom_id'))
                <a class="btn ghost" href="{{ route('exams.monitor', $exam) }}" style="align-self:flex-end">Reset</a>
            @else
                <button class="btn ghost" type="button" data-live-reset="monitorTable" style="align-self:flex-end">Clear</button>
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
                    <th>Sync</th>
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
                    <td><span class="badge" style="font-size:11px">{{ $p->student?->nis ?: '–' }}</span></td>
                    <td>
                        <b style="font-size:13px">{{ $p->student?->name ?: 'Siswa dihapus' }}</b>
                    </td>
                    <td class="small muted">{{ $p->student?->classroom?->nama_kelas ?: ($p->student?->class_name ?: '–') }}</td>
                    <td><span class="badge {{ $p->status }}" style="font-size:11px">{{ $statusLabel }}</span></td>
                    <td class="small">
                        @if(($summary['total'] ?? 0) > 0)
                            <span class="badge warning" style="font-size:11px">{{ $summary['total'] }} event</span>
                            <br><span class="muted" style="font-size:11px">{{ $summary['last_reason'] ?? '–' }}</span>
                        @elseif($p->status === 'locked')
                            <span class="badge danger" style="font-size:11px">terkunci</span>
                        @else
                            <span class="muted">–</span>
                        @endif
                    </td>
                    <td class="small muted">{{ optional($last?->started_at)->format('H:i') ?: '–' }}</td>
                    <td class="small muted">{{ optional($last?->last_synced_at)->format('H:i') ?: '–' }}</td>
                    <td class="small muted">{{ optional($p->submitted_at)->format('H:i') ?: '–' }}</td>
                    <td><b>{{ $p->score ?? '–' }}</b></td>
                    <td>
                        @if(auth()->user()->canManageExam($exam))
                            <form method="POST" action="{{ route('exams.participants.resetDevice', [$exam, $p]) }}"
                                  onsubmit="return confirm('Reset perangkat siswa ini?\nHanya menghapus kunci HP, jawaban TIDAK dihapus.')">
                                @csrf
                                <button class="btn warning" style="font-size:12px;padding:.35rem .7rem">Ganti HP</button>
                            </form>
                        @else
                            <span class="muted small">–</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr data-empty-row>
                    <td colspan="10" style="text-align:center;padding:2.5rem;color:var(--muted)">Belum ada peserta.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-meta between">
        <div class="small muted">Terlihat: <b data-live-count="monitorTable">{{ $participants->count() }}</b> baris</div>
        <div>{{ $participants->links() }}</div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function(){
    let remaining = 30;
    const badge = document.getElementById('refreshCountdown');
    if(!badge) return;
    const interval = setInterval(function(){
        remaining--;
        badge.textContent = '↻ ' + remaining + 's';
        if(remaining <= 0){ clearInterval(interval); window.location.reload(); }
    }, 1000);
})();
</script>
@endpush
