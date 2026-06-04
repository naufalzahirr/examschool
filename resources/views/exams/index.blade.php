@extends('layouts.app', ['title' => 'Daftar Ujian'])

@section('content')
<div class="between mb">
    <div>
        <span class="badge info">Ujian</span>
        <h1>Daftar Ujian</h1>
        <p class="muted">Buat, publish, monitor, dan lihat hasil ujian dari satu daftar yang mudah dipindai.</p>
    </div>
    <a class="btn primary" href="{{ route('exams.create') }}">Buat Ujian Baru</a>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Daftar Ujian</h2>
            <p class="muted small mb0">Menampilkan {{ $exams->firstItem() ?? 0 }}-{{ $exams->lastItem() ?? 0 }} dari {{ $exams->total() }} ujian.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('exams.index') }}">
            <div class="tool-field">
                <label>Status</label>
                <select class="input" name="status" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
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
                <label>Cari Cepat</label>
                <div class="live-search-wrap">
                    <input class="input" data-live-search="examsTable" name="q" value="{{ request('q') }}" placeholder="Cari judul, kode, mapel, kelas">
                </div>
            </div>
            <button class="btn primary">Cari</button>
            @if(request('q') || request('status') || request('classroom_id'))
                <a class="btn" href="{{ route('exams.index') }}">Reset</a>
            @else
                <button class="btn" type="button" data-live-reset="examsTable">Clear</button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="table" id="examsTable">
            <thead>
                <tr>
                    <th>Ujian</th>
                    <th>Kode</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Soal</th>
                    <th>Peserta / Submit</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($exams as $exam)
                <tr>
                    <td>
                        <b>{{ $exam->title }}</b><br>
                        <span class="muted small">{{ $exam->subject ?: '-' }}
                            @if($exam->starts_at)
                                | {{ $exam->starts_at->format('d M Y H:i') }}
                            @endif
                        </span>
                    </td>
                    <td><span class="badge">{{ $exam->access_code }}</span></td>
                    <td>
                        @forelse($exam->classrooms->take(3) as $classroom)
                            <span class="badge">{{ $classroom->nama_kelas }}</span>
                        @empty
                            <span class="muted small">Belum dipilih</span>
                        @endforelse
                        @if($exam->classrooms_count > 3)
                            <span class="muted small">+{{ $exam->classrooms_count - 3 }} kelas</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $exam->status }}">{{ $exam->operationalStatus() }}</span>
                        @if(!$exam->hasGeneratedPackage())
                            <br><span class="muted tiny">soal belum siap</span>
                        @endif
                    </td>
                    <td>{{ $exam->questions_count }}</td>
                    <td>
                        @if($exam->participants_count > 0)
                            @php $pct = round($exam->submitted_count / $exam->participants_count * 100); @endphp
                            <b>{{ $exam->submitted_count }}</b><span class="muted small"> / {{ $exam->participants_count }}</span><br>
                            <div style="background:#ebeef0;border-radius:999px;height:5px;margin-top:.3rem;overflow:hidden;width:80px">
                                <div style="height:100%;border-radius:999px;background:{{ $pct >= 100 ? 'var(--success)' : 'var(--primary)' }};width:{{ $pct }}%"></div>
                            </div>
                        @else
                            <span class="muted small">-</span>
                        @endif
                    </td>
                    <td class="row">
                        <a class="btn soft" href="{{ route('exams.show', $exam) }}">Kelola</a>
                        @if($exam->canEditQuestions())
                            <a class="btn primary" href="{{ route('exams.question-bank.select', $exam) }}">Pilih Soal</a>
                        @endif
                        <a class="btn" href="{{ route('exams.monitor', $exam) }}">Monitor</a>
                    </td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="7">Belum ada ujian. <a href="{{ route('exams.create') }}">Buat ujian pertama</a></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-meta between">
        <div class="small muted">Terlihat di halaman ini: <b data-live-count="examsTable">{{ $exams->count() }}</b> baris</div>
        <div>{{ $exams->links() }}</div>
    </div>
</div>
@endsection
