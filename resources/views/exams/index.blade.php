@extends('layouts.app', ['title' => 'Daftar Ujian'])

@push('head')
<style>
.exam-card{border:1px solid var(--line);border-radius:var(--radius);background:#fff;padding:1.15rem 1.25rem;transition:transform .18s,box-shadow .18s,border-color .18s;display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:start}
.exam-card:hover{transform:translateY(-2px);box-shadow:0 18px 36px rgba(15,23,42,.09);border-color:#99f6e4}
.exam-card-title{font-size:16px;font-weight:900;color:var(--heading);margin:0 0 .25rem;line-height:1.2}
.exam-card-meta{font-size:12px;color:var(--muted);margin-bottom:.6rem}
.exam-card-footer{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-top:.65rem;padding-top:.65rem;border-top:1px solid var(--line)}
.progress-mini{height:5px;border-radius:999px;background:#e2e8f0;overflow:hidden;flex:1;min-width:60px}
.progress-mini-fill{height:100%;border-radius:999px;background:var(--primary);transition:width .4s}
.empty-state{text-align:center;padding:4rem 2rem;color:var(--muted)}
.empty-state-icon{font-size:52px;margin-bottom:1rem;display:block}
</style>
@endpush

@section('content')
<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0">Daftar Ujian</h1>
            <p class="muted mb0">Kelola ujian dari pembuatan hingga hasil. Soal dipilih dari Bank Soal.</p>
        </div>
        <a class="btn primary" href="{{ route('exams.create') }}">+ Buat Ujian</a>
    </div>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2 style="font-size:15px;margin:0">{{ $exams->total() }} Ujian</h2>
        </div>
        <form class="table-tools" method="GET" action="{{ route('exams.index') }}">
            <div class="tool-field">
                <label>Status</label>
                <select class="input" name="status" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Kelas</label>
                <select class="input" name="classroom_id" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field search">
                <label>Cari</label>
                <div class="live-search-wrap">
                    <input class="input" data-live-search="examsGrid" name="q" value="{{ request('q') }}" placeholder="Judul, kode, mapel...">
                </div>
            </div>
            <button class="btn primary" style="align-self:flex-end">Cari</button>
            @if(request('q') || request('status') || request('classroom_id'))
                <a class="btn ghost" href="{{ route('exams.index') }}" style="align-self:flex-end">Reset</a>
            @else
                <button class="btn ghost" type="button" data-live-reset="examsGrid" style="align-self:flex-end">Clear</button>
            @endif
        </form>
    </div>

    <div style="padding:1.25rem">
        <div id="examsGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:1rem">
        @forelse($exams as $exam)
            @php
                $pct = $exam->participants_count > 0 ? round($exam->submitted_count / $exam->participants_count * 100) : 0;
            @endphp
            <div class="exam-card">
                <div>
                    <div class="row" style="margin-bottom:.5rem;gap:.4rem">
                        <span class="badge {{ $exam->status }}" style="font-size:11px">{{ $exam->operationalStatus() }}</span>
                        @if(!$exam->hasGeneratedPackage() && $exam->status === 'draft')
                            <span class="badge warning" style="font-size:10px">belum ada paket</span>
                        @endif
                    </div>
                    <div class="exam-card-title">{{ $exam->title }}</div>
                    <div class="exam-card-meta">
                        {{ $exam->subject ?: 'Tanpa mapel' }}
                        @if($exam->grade_level) | {{ $exam->grade_level }} @endif
                        @if($exam->starts_at) | {{ $exam->starts_at->format('d M Y H:i') }} @endif
                    </div>
                    <div class="row" style="gap:.35rem;flex-wrap:wrap">
                        @foreach($exam->classrooms->take(3) as $classroom)
                            <span class="badge" style="font-size:11px;padding:.2rem .5rem">{{ $classroom->nama_kelas }}</span>
                        @endforeach
                        @if($exam->classrooms_count > 3)
                            <span class="muted small">+{{ $exam->classrooms_count - 3 }}</span>
                        @endif
                    </div>
                    <div class="exam-card-footer">
                        <span class="muted small" style="font-size:11px;min-width:60px">{{ $exam->questions_count }} soal</span>
                        @if($exam->participants_count > 0)
                            <div class="progress-mini">
                                <div class="progress-mini-fill" style="width:{{ $pct }}%;background:{{ $pct >= 100 ? 'var(--success)' : 'var(--primary)' }}"></div>
                            </div>
                            <span class="muted small" style="font-size:11px;white-space:nowrap">{{ $exam->submitted_count }}/{{ $exam->participants_count }} submit</span>
                        @else
                            <span class="muted small" style="font-size:11px">Belum ada peserta</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:.4rem;min-width:80px">
                    <span class="badge" style="text-align:center;justify-content:center;font-size:11px">{{ $exam->access_code }}</span>
                    <a class="btn soft" href="{{ route('exams.show', $exam) }}" style="padding:.4rem .65rem;font-size:12px;justify-content:center">Kelola</a>
                    @if($exam->canEditQuestions())
                        <a class="btn ghost" href="{{ route('exams.question-bank.select', $exam) }}" style="padding:.4rem .65rem;font-size:12px;justify-content:center">+ Soal</a>
                    @endif
                    <a class="btn ghost" href="{{ route('exams.monitor', $exam) }}" style="padding:.4rem .65rem;font-size:12px;justify-content:center">Monitor</a>
                </div>
            </div>
        @empty
            <div class="empty-state" style="grid-column:1/-1">
                <span class="empty-state-icon">0</span>
                <b style="display:block;font-size:18px;color:var(--heading);margin-bottom:.5rem">Belum ada ujian</b>
                <p class="muted">Buat ujian pertama dengan mengklik tombol di atas.</p>
                <a class="btn primary" href="{{ route('exams.create') }}" style="margin-top:.75rem">+ Buat Ujian Pertama</a>
            </div>
        @endforelse
        </div>
    </div>

    <div class="table-meta between">
        <div class="small muted">Menampilkan <b>{{ $exams->firstItem() ?? 0 }}-{{ $exams->lastItem() ?? 0 }}</b> dari <b>{{ $exams->total() }}</b> ujian</div>
        <div>{{ $exams->links() }}</div>
    </div>
</div>
@endsection
