@extends('layouts.app', ['title' => 'Hasil ' . $exam->title])

@push('head')
<style>
.dist-bar-wrap{background:#ebeef0;border-radius:999px;height:10px;overflow:hidden;width:100%}
.dist-bar-fill{height:100%;border-radius:999px;background:var(--primary);transition:width .4s}
.stat-big{font-size:32px;font-weight:900;color:var(--heading);letter-spacing:-.04em;line-height:1}
</style>
@endpush

@section('content')
<div class="between mb">
    <div><h1>Hasil Ujian</h1><p class="muted">{{ $exam->title }} · Kode: <b>{{ $exam->access_code }}</b></p></div>
    <div class="row"><a class="btn soft" href="{{ route('exams.results.export', $exam) }}">Export CSV</a><a class="btn" href="{{ route('exams.show', $exam) }}">Kembali</a></div>
</div>

{{-- ===== STATISTIK RINGKAS ===== --}}
<div class="grid mb">
    <div class="card">
        <div class="muted small">Sudah Submit</div>
        <div class="stat-big">{{ $stats['submitted'] }}</div>
        <div class="muted small">dari {{ $stats['total'] }} peserta</div>
    </div>
    <div class="card">
        <div class="muted small">Rata-rata Nilai</div>
        <div class="stat-big">{{ $stats['avg_score'] ?? '–' }}</div>
        <div class="muted small">&nbsp;</div>
    </div>
    <div class="card">
        <div class="muted small">Nilai Tertinggi</div>
        <div class="stat-big" style="color:var(--success)">{{ $stats['max_score'] ?? '–' }}</div>
        <div class="muted small">&nbsp;</div>
    </div>
    <div class="card">
        <div class="muted small">Nilai Terendah</div>
        <div class="stat-big" style="color:{{ $stats['min_score'] !== null && $stats['min_score'] < 60 ? '#ff3e1d' : 'inherit' }}">{{ $stats['min_score'] ?? '–' }}</div>
        <div class="muted small">&nbsp;</div>
    </div>
</div>

@if($stats['submitted'] > 0)
<div class="two mb">

    {{-- Distribusi Nilai --}}
    <div class="card">
        <h2 class="mb0">Distribusi Nilai</h2>
        <p class="muted small" style="margin-bottom:1rem">{{ $stats['submitted'] }} siswa sudah submit</p>
        @php $maxDist = max(1, max($stats['distribution'])); @endphp
        @foreach($stats['distribution'] as $range => $count)
        <div style="display:grid;grid-template-columns:60px 1fr 40px;gap:.5rem;align-items:center;margin-bottom:.6rem">
            <span class="small" style="font-weight:800;color:var(--heading)">{{ $range }}</span>
            <div class="dist-bar-wrap">
                <div class="dist-bar-fill" style="width:{{ $count > 0 ? round($count / $maxDist * 100) : 0 }}%;background:{{ str_starts_with($range,'85') ? '#71dd37' : (str_starts_with($range,'75') ? '#96c93d' : (str_starts_with($range,'60') ? '#ffab00' : '#ff3e1d')) }}"></div>
            </div>
            <span class="small muted">{{ $count }}</span>
        </div>
        @endforeach
    </div>

    {{-- Soal Paling Banyak Salah --}}
    <div class="card">
        <h2 class="mb0">Soal Paling Banyak Dijawab Salah</h2>
        <p class="muted small" style="margin-bottom:1rem">Berdasarkan jawaban peserta yang sudah submit</p>
        @forelse($hardQuestions as $q)
        <div style="margin-bottom:.85rem">
            <div class="between" style="gap:.5rem;margin-bottom:.3rem">
                <span class="small" style="font-weight:800">
                    No.{{ $q['no'] }} · {{ Str::limit($q['title'], 55) }}
                </span>
                <span class="badge {{ $q['wrong_pct'] >= 60 ? 'danger' : ($q['wrong_pct'] >= 40 ? 'warning' : '') }}" style="white-space:nowrap">
                    {{ $q['wrong_pct'] }}% salah
                </span>
            </div>
            <div class="dist-bar-wrap">
                <div class="dist-bar-fill" style="width:{{ $q['wrong_pct'] }}%;background:{{ $q['wrong_pct'] >= 60 ? 'var(--danger)' : ($q['wrong_pct'] >= 40 ? 'var(--warning)' : 'var(--primary)') }}"></div>
            </div>
            <span class="muted tiny">{{ $q['wrong'] }} dari {{ $q['total'] }} siswa menjawab salah</span>
        </div>
        @empty
        <p class="muted small">Data jawaban belum tersedia atau belum ada yang submit.</p>
        @endforelse
    </div>

</div>
@endif

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Daftar Hasil</h2>
            <p class="muted small mb0">Menampilkan {{ $participants->firstItem() ?? 0 }}-{{ $participants->lastItem() ?? 0 }} dari {{ $participants->total() }} peserta.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('exams.results', $exam) }}">
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
                <div class="live-search-wrap"><input class="input" data-live-search="resultsTable" name="q" value="{{ request('q') }}" placeholder="Cari NIS, nama, kelas"></div>
            </div>
            <button class="btn primary">Cari</button>
            @if(request('q') || request('status') || request('classroom_id'))<a class="btn" href="{{ route('exams.results', $exam) }}">Reset</a>@else<button class="btn" type="button" data-live-reset="resultsTable">Clear</button>@endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="table" id="resultsTable">
            <thead><tr><th>Peserta</th><th>NIS</th><th>Kelas</th><th>Status</th><th>Nilai</th><th>Submit</th><th>Last Sync</th></tr></thead>
            <tbody>
            @forelse($participants as $p)
                @php
                $last = $p->attempts->first();
                $statusLabel = match($p->status) {
                    'assigned'       => 'Belum login',
                    'download_ready' => 'Siap download',
                    'downloading'    => 'Mengunduh',
                    'downloaded'     => 'Paket terunduh',
                    'unlocked'       => 'Soal terbuka',
                    'in_progress'    => 'Mengerjakan',
                    'locked'         => 'Terkunci',
                    'synced'         => 'Tersinkron',
                    'submitted'      => 'Sudah submit',
                    default          => $p->status,
                };
                @endphp
                <tr>
                    <td><b>{{ $p->student?->name ?: 'Siswa dihapus' }}</b></td>
                    <td>{{ $p->student?->nis ?: '-' }}</td>
                    <td>{{ $p->student?->classroom?->nama_kelas ?: ($p->student?->class_name ?: '-') }}</td>
                    <td><span class="badge {{ $p->status }}">{{ $statusLabel }}</span></td>
                    <td><b>{{ $p->score ?? '-' }}</b></td>
                    <td>{{ optional($p->submitted_at)->format('d M Y H:i') ?: '-' }}</td>
                    <td>{{ optional($last?->last_synced_at)->format('d M Y H:i') ?: '-' }}</td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="7">Belum ada peserta.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-meta between">
        <div class="small muted">Terlihat di halaman ini: <b data-live-count="resultsTable">{{ $participants->count() }}</b> baris</div>
        <div>{{ $participants->links() }}</div>
    </div>
</div>
@endsection
