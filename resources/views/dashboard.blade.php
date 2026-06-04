@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0">Dashboard Ujian Sekolah</h1>
            <p class="muted mb0">Backend produksi: konfigurasi ujian, bank soal, akun guru/pengawas, audit log, dan monitor pelaksanaan.</p>
        </div>
        <div class="row">
            @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
                <a class="btn primary" href="{{ route('exams.create') }}">+ Buat Ujian</a>
                <a class="btn soft" href="{{ route('question-bank.index') }}">Bank Soal</a>
            @endif
            @if(auth()->user()->isAdmin())<a class="btn" href="{{ route('silap.index') }}">Sinkron SILAP</a>@endif
        </div>
    </div>
</div>

<div class="grid mb">
    <div class="card"><div class="muted">Total Ujian</div><div class="stat">{{ $examCount }}</div></div>
    <div class="card"><div class="muted">Sedang Berlangsung</div><div class="stat">{{ $runningCount }}</div></div>
    <div class="card"><div class="muted">Published</div><div class="stat">{{ $publishedCount }}</div></div>
    <div class="card"><div class="muted">Bank Soal</div><div class="stat">{{ $bankCount }}</div></div>
</div>

@if(auth()->user()->isAdmin())
<div class="grid mb">
    <div class="card"><div class="muted">Akun Siswa</div><div class="stat">{{ $studentCount }}</div></div>
    <div class="card"><div class="muted">Data Kelas</div><div class="stat">{{ $classroomCount }}</div></div>
    <div class="card"><div class="muted">Data Guru</div><div class="stat">{{ $teacherCount }}</div></div>
    <div class="card"><div class="muted">Submit Masuk</div><div class="stat">{{ $submittedCount }}</div></div>
</div>
@endif

<div class="two mb">
    <div class="card">
        <div class="between mb">
            <h2 class="mb0">Monitor Ujian Aktif</h2>
            @if($runningExams->isNotEmpty())
                <span class="badge published">{{ $runningExams->count() }} sedang berjalan</span>
            @endif
        </div>
        <div class="table-wrap"><table class="table"><thead><tr><th>Ujian</th><th>Kode</th><th>Kelas</th><th>Aksi</th></tr></thead><tbody>
            @forelse($runningExams as $exam)
                <tr><td><b>{{ $exam->title }}</b><br><span class="muted small">{{ $exam->subject ?: '-' }}</span></td><td><span class="badge published">{{ $exam->access_code }}</span></td><td>@foreach($exam->classrooms->take(2) as $c)<span class="badge">{{ $c->nama_kelas }}</span>@endforeach</td><td><a class="btn soft" href="{{ route('exams.monitor', $exam) }}">Monitor</a></td></tr>
            @empty
                <tr><td colspan="4" class="muted">Tidak ada ujian yang sedang berlangsung saat ini.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>

    <div class="card">
        <div class="between mb">
            <h2 class="mb0">Siap Dipublish</h2>
            @if($readyToPublishExams->isNotEmpty())
                <span class="badge warning">{{ $readyToPublishExams->count() }} ujian menunggu</span>
            @endif
        </div>
        @forelse($readyToPublishExams as $exam)
            <div class="check-pill" style="justify-content:space-between;margin-bottom:.4rem">
                <span>
                    <b>{{ $exam->title }}</b><br>
                    <span class="muted small">{{ $exam->questions_count }} soal · {{ $exam->participants_count }} peserta{{ $exam->starts_at ? ' · ' . $exam->starts_at->format('d M H:i') : '' }}</span>
                </span>
                <form method="POST" action="{{ route('exams.publish', $exam) }}">
                    @csrf
                    <button class="btn green" style="white-space:nowrap">Publish</button>
                </form>
            </div>
        @empty
            <p class="muted small" style="margin:0">Semua ujian sudah dipublish atau masih dalam proses penyiapan soal.</p>
        @endforelse
    </div>
</div>

<div class="card mb">
    <div class="between mb">
        <h2 class="mb0">Ujian Terbaru</h2>
        @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())<a class="btn" href="{{ route('exams.index') }}">Lihat semua</a>@endif
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Ujian</th><th>Kode</th><th>Status</th><th>Jadwal</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($recentExams as $exam)
                <tr>
                    <td><b>{{ $exam->title }}</b><br><span class="muted small">{{ $exam->subject ?: '-' }} · {{ $exam->grade_level ?: '-' }}</span></td>
                    <td><span class="badge">{{ $exam->access_code }}</span></td>
                    <td><span class="badge {{ $exam->status }}">{{ $exam->operationalStatus() }}</span></td>
                    <td class="small">{{ optional($exam->starts_at)->format('d M Y H:i') ?: '-' }}<br>{{ optional($exam->ends_at)->format('d M Y H:i') ?: '-' }}</td>
                    <td class="row">@if(auth()->user()->canManageExam($exam))<a class="btn soft" href="{{ route('exams.show', $exam) }}">Kelola</a>@endif<a class="btn" href="{{ route('exams.monitor', $exam) }}">Monitor</a></td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada ujian.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(auth()->user()->isAdmin() && $recentAuditLogs->isNotEmpty())
<div class="card">
    <div class="between mb"><h2 class="mb0">Audit Terbaru</h2><a class="btn" href="{{ route('audit.index') }}">Lihat Audit</a></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Event</th><th>User</th></tr></thead><tbody>@foreach($recentAuditLogs as $log)<tr><td>{{ $log->created_at->format('d M Y H:i') }}</td><td><span class="badge info">{{ $log->event }}</span></td><td>{{ $log->user?->name ?: 'System' }}</td></tr>@endforeach</tbody></table></div>
</div>
@endif
@endsection
