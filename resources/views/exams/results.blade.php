@extends('layouts.app', ['title' => 'Hasil — ' . $exam->title])

@push('head')
<style>
.result-stat{border-radius:var(--radius);padding:1.15rem 1.25rem;text-align:center;position:relative;overflow:hidden;border:1px solid var(--line);background:#fff}
.result-stat .num{font-size:38px;font-weight:950;letter-spacing:0;line-height:1}
.result-stat .lbl{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:900;margin-top:.3rem}
.dist-row{display:grid;grid-template-columns:52px 1fr 40px;gap:.6rem;align-items:center;margin-bottom:.55rem}
.dist-bar{border-radius:999px;height:10px;overflow:hidden;background:#f1f5f9}
.dist-fill{height:100%;border-radius:999px;transition:width .5s ease}
.diff-bar-wrap{background:#e2e8f0;border-radius:999px;height:8px;overflow:hidden;flex:1;min-width:40px}
.diff-fill{height:100%;border-radius:999px;transition:width .4s}
</style>
@endpush

@section('content')

<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0">Hasil Ujian</h1>
            <p class="muted mb0">{{ $exam->title }} · Kode: <b>{{ $exam->access_code }}</b></p>
        </div>
        <div class="row">
            <a class="btn soft" href="{{ route('exams.results.export', $exam) }}">↓ Export CSV</a>
            <a class="btn ghost" href="{{ route('exams.show', $exam) }}">← Detail Ujian</a>
        </div>
    </div>
</div>

{{-- ═══ STAT CARDS ═══ --}}
<div class="grid mb">
    <div class="result-stat">
        <div class="num" style="color:var(--primary)">{{ $stats['submitted'] }}</div>
        <div class="lbl">Sudah Submit</div>
        <div class="muted small" style="margin-top:.25rem">dari {{ $stats['total'] }} peserta</div>
    </div>
    <div class="result-stat">
        <div class="num" style="color:var(--heading)">{{ $stats['avg_score'] ?? '–' }}</div>
        <div class="lbl">Rata-rata Nilai</div>
        @if($stats['submitted'] > 0)<div class="muted small" style="margin-top:.25rem">dari {{ $stats['submitted'] }} siswa</div>@endif
    </div>
    <div class="result-stat">
        <div class="num" style="color:var(--success)">{{ $stats['max_score'] ?? '–' }}</div>
        <div class="lbl">Nilai Tertinggi</div>
    </div>
    <div class="result-stat">
        <div class="num" style="color:{{ $stats['min_score'] !== null && $stats['min_score'] < 60 ? 'var(--danger)' : 'var(--heading)' }}">{{ $stats['min_score'] ?? '–' }}</div>
        <div class="lbl">Nilai Terendah</div>
    </div>
</div>

@if($stats['submitted'] > 0)
<div class="two mb">

    {{-- Distribusi --}}
    <div class="card">
        <h2 style="font-size:15px;margin-bottom:1rem">Distribusi Nilai</h2>
        @php $maxDist = max(1, max($stats['distribution'])); @endphp
        @foreach($stats['distribution'] as $range => $count)
            @php
            $colors = ['85–100'=>'#22c55e','75–84'=>'#84cc16','60–74'=>'#f59e0b','40–59'=>'#f97316','0–39'=>'#ef4444'];
            $col = $colors[$range] ?? 'var(--primary)';
            @endphp
            <div class="dist-row">
                <span style="font-size:12px;font-weight:900;color:var(--heading);text-align:right">{{ $range }}</span>
                <div class="dist-bar">
                    <div class="dist-fill" style="width:{{ $count > 0 ? round($count/$maxDist*100) : 0 }}%;background:{{ $col }}"></div>
                </div>
                <span style="font-size:13px;font-weight:900;color:var(--heading);text-align:right">{{ $count }}</span>
            </div>
        @endforeach
        <div class="muted small" style="margin-top:.75rem;text-align:center">{{ $stats['submitted'] }} siswa telah submit</div>
    </div>

    {{-- Soal tersulit --}}
    <div class="card">
        <h2 style="font-size:15px;margin-bottom:1rem">Soal Paling Banyak Dijawab Salah</h2>
        @forelse($hardQuestions as $q)
            <div style="margin-bottom:.9rem">
                <div class="between" style="gap:.4rem;margin-bottom:.3rem">
                    <span style="font-size:12px;font-weight:800;color:var(--heading);flex:1;min-width:0;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">
                        No.{{ $q['no'] }} · {{ Str::limit($q['title'], 48) }}
                    </span>
                    <span class="badge {{ $q['wrong_pct'] >= 60 ? 'danger' : ($q['wrong_pct'] >= 40 ? 'warning' : '') }}" style="font-size:10px;white-space:nowrap">
                        {{ $q['wrong_pct'] }}% salah
                    </span>
                </div>
                <div style="display:flex;gap:.5rem;align-items:center">
                    <div class="diff-bar-wrap">
                        <div class="diff-fill" style="width:{{ $q['wrong_pct'] }}%;background:{{ $q['wrong_pct'] >= 60 ? 'var(--danger)' : ($q['wrong_pct'] >= 40 ? 'var(--warning)' : 'var(--primary)') }}"></div>
                    </div>
                    <span class="muted" style="font-size:11px;white-space:nowrap">{{ $q['wrong'] }}/{{ $q['total'] }}</span>
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:1.5rem;color:var(--muted)">
                <div style="font-size:28px;margin-bottom:.5rem">📊</div>
                <p class="small mb0">Data jawaban belum tersedia.</p>
            </div>
        @endforelse
    </div>

</div>
@endif

{{-- ═══ TABEL HASIL ═══ --}}
<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2 style="font-size:15px">Daftar Hasil Peserta</h2>
            <p class="muted small mb0">{{ $participants->firstItem() ?? 0 }}–{{ $participants->lastItem() ?? 0 }} dari {{ $participants->total() }} peserta</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('exams.results', $exam) }}">
            <div class="tool-field">
                <label>Status</label>
                <select class="input" name="status" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach(['assigned'=>'Belum login','download_ready'=>'Login/siap download','downloaded'=>'Paket terunduh','in_progress'=>'Mengerjakan','synced'=>'Tersinkron','submitted'=>'Sudah submit'] as $val => $lbl)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $lbl }}</option>
                    @endforeach
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
                    <input class="input" data-live-search="resultsTable" name="q" value="{{ request('q') }}" placeholder="NIS, nama, kelas...">
                </div>
            </div>
            <button class="btn primary" style="align-self:flex-end">Cari</button>
            @if(request('q') || request('status') || request('classroom_id'))
                <a class="btn ghost" href="{{ route('exams.results', $exam) }}" style="align-self:flex-end">Reset</a>
            @else
                <button class="btn ghost" type="button" data-live-reset="resultsTable" style="align-self:flex-end">Clear</button>
            @endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="table" id="resultsTable">
            <thead>
                <tr>
                    <th>Peserta</th>
                    <th>NIS</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Nilai</th>
                    <th>Submit</th>
                    <th>Sync Terakhir</th>
                </tr>
            </thead>
            <tbody>
            @forelse($participants as $p)
                @php
                $last = $p->attempts->first();
                $statusLabel = match($p->status) {
                    'assigned'=>'Belum login','download_ready'=>'Siap download','downloading'=>'Mengunduh',
                    'downloaded'=>'Terunduh','unlocked'=>'Soal terbuka','in_progress'=>'Mengerjakan',
                    'locked'=>'Terkunci','synced'=>'Tersinkron','submitted'=>'Sudah submit',default=>$p->status,
                };
                @endphp
                <tr>
                    <td><b style="font-size:13px">{{ $p->student?->name ?: 'Siswa dihapus' }}</b></td>
                    <td><span class="badge" style="font-size:11px">{{ $p->student?->nis ?: '–' }}</span></td>
                    <td class="small muted">{{ $p->student?->classroom?->nama_kelas ?: ($p->student?->class_name ?: '–') }}</td>
                    <td><span class="badge {{ $p->status }}" style="font-size:11px">{{ $statusLabel }}</span></td>
                    <td>
                        @if($p->score !== null)
                            <b style="font-size:15px;color:{{ $p->score >= 75 ? 'var(--success)' : ($p->score >= 60 ? 'var(--warning)' : 'var(--danger)') }}">{{ number_format((float) $p->score, 1) }}</b>
                        @else
                            <span class="muted">–</span>
                        @endif
                    </td>
                    <td class="small muted">{{ optional($p->submitted_at)->format('d M Y H:i') ?: '–' }}</td>
                    <td class="small muted">{{ optional($last?->last_synced_at)->format('d M Y H:i') ?: '–' }}</td>
                </tr>
            @empty
                <tr data-empty-row>
                    <td colspan="7" style="text-align:center;padding:2.5rem;color:var(--muted)">Belum ada peserta.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-meta between">
        <div class="small muted">Terlihat: <b data-live-count="resultsTable">{{ $participants->count() }}</b> baris</div>
        <div>{{ $participants->links() }}</div>
    </div>
</div>
@endsection
