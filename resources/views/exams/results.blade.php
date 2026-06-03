@extends('layouts.app', ['title' => 'Hasil ' . $exam->title])

@section('content')
<div class="between mb">
    <div><h1>Hasil Ujian</h1><p class="muted">{{ $exam->title }} · Kode: <b>{{ $exam->access_code }}</b></p></div>
    <div class="row"><a class="btn soft" href="{{ route('exams.results.export', $exam) }}">Export CSV</a><a class="btn" href="{{ route('exams.show', $exam) }}">Kembali</a></div>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Live Table Hasil</h2>
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
                <label>Live Search</label>
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
                @php($last = $p->attempts->first())
                <tr>
                    <td><b>{{ $p->student?->name ?: 'Siswa dihapus' }}</b></td>
                    <td>{{ $p->student?->nis ?: '-' }}</td>
                    <td>{{ $p->student?->classroom?->nama_kelas ?: ($p->student?->class_name ?: '-') }}</td>
                    <td><span class="badge {{ $p->status }}">{{ $p->status }}</span></td>
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
