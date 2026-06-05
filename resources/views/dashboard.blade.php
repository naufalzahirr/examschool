@extends('layouts.app', ['title' => 'Dashboard'])

@push('head')
<style>
.stat-card{position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 18px 40px rgba(15,23,42,.10)}
.stat-card-icon{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;font-size:20px;margin-bottom:.75rem}
.stat-card-val{font-size:36px;font-weight:950;letter-spacing:0;line-height:1;color:var(--heading)}
.stat-card-label{font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-top:.3rem}
.stat-card::after{content:'';position:absolute;right:-16px;bottom:-16px;width:72px;height:72px;border-radius:50%;opacity:.08}
.s-teal .stat-card-icon{background:var(--primary-soft);color:var(--primary-strong)}.s-teal::after{background:var(--primary)}
.s-pink .stat-card-icon{background:var(--accent-soft);color:#be185d}.s-pink::after{background:var(--accent)}
.s-violet .stat-card-icon{background:var(--violet-soft);color:var(--violet)}.s-violet::after{background:var(--violet)}
.s-amber .stat-card-icon{background:#fef3c7;color:#92400e}.s-amber::after{background:var(--warning)}
.s-green .stat-card-icon{background:var(--success-soft);color:#166534}.s-green::after{background:var(--success)}
.s-sky .stat-card-icon{background:var(--info-soft);color:#075985}.s-sky::after{background:var(--info)}

.exam-row{display:grid;grid-template-columns:1fr auto auto;gap:.6rem;align-items:center;padding:.9rem 1rem;border-bottom:1px solid var(--line);transition:background .15s}
.exam-row:last-child{border-bottom:0}
.exam-row:hover{background:#f8fffd}
.exam-title-col b{display:block;font-size:14px;color:var(--heading)}
.exam-title-col span{font-size:12px;color:var(--muted)}
.pulse-dot{width:9px;height:9px;border-radius:50%;background:var(--success);box-shadow:0 0 0 3px rgba(34,197,94,.25);animation:pulse 1.8s infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 3px rgba(34,197,94,.25)}50%{box-shadow:0 0 0 7px rgba(34,197,94,.10)}}

.upcoming-strip{display:flex;align-items:center;gap:.75rem;padding:.8rem 1rem;border-bottom:1px solid var(--line);transition:background .15s}
.upcoming-strip:last-child{border-bottom:0}
.upcoming-strip:hover{background:#fffbf0}
.upcoming-time{text-align:right;min-width:90px}
.upcoming-time .big{font-size:20px;font-weight:950;color:var(--heading);line-height:1}
.upcoming-time .sub{font-size:11px;color:var(--muted)}
</style>
@endpush

@section('content')

{{-- ───── GREETING ───── --}}
@php
$hour = now()->hour;
$greet = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 18 ? 'Selamat Sore' : 'Selamat Malam'));
@endphp
<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0">{{ $greet }}, {{ explode(' ', auth()->user()->name)[0] }} 👋</h1>
            <p class="muted mb0">{{ now()->isoFormat('dddd, D MMMM Y') }} · Ruang kendali ujian sekolah</p>
        </div>
        <div class="row">
            @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
                <a class="btn primary" href="{{ route('exams.create') }}">+ Buat Ujian</a>
                <a class="btn soft" href="{{ route('question-bank.index') }}">Bank Soal</a>
            @endif
            @if(auth()->user()->isAdmin())
                <a class="btn ghost" href="{{ route('silap.index') }}">↻ Sinkron SILAP</a>
            @endif
        </div>
    </div>
</div>

{{-- ───── PANEL "YANG PERLU DILAKUKAN" ───── --}}
@php
$needsAttention = [];
if($runningExams->isNotEmpty()) $needsAttention[] = ['type'=>'live','msg'=>$runningExams->count().' ujian sedang berjalan','sub'=>'Pantau progress submit siswa secara real-time','url'=>route('exams.monitor',$runningExams->first()),'label'=>'Monitor Sekarang','color'=>'published'];
if($readyToPublishExams->isNotEmpty()) $needsAttention[] = ['type'=>'publish','msg'=>$readyToPublishExams->count().' ujian siap dipublish','sub'=>'Checklist sudah lengkap, tinggal klik Publish','url'=>'#ready-publish','label'=>'Lihat Daftar','color'=>'warning'];
if($upcomingExams->isNotEmpty()) $needsAttention[] = ['type'=>'upcoming','msg'=>$upcomingExams->count().' ujian akan dimulai dalam 48 jam','sub'=>'Pastikan siswa sudah tahu kode ujian','url'=>'#upcoming','label'=>'Lihat Jadwal','color'=>'info'];
@endphp

@if(count($needsAttention) > 0)
<div class="card mb" style="background:linear-gradient(135deg,#0f172a,#1e293b);border:none;padding:1.25rem 1.5rem">
    <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:.85rem">Yang perlu dilakukan sekarang</div>
    <div style="display:grid;gap:.5rem">
        @foreach($needsAttention as $item)
        <div style="display:flex;align-items:center;gap:1rem;padding:.85rem 1rem;background:rgba(255,255,255,.06);border-radius:10px;border:1px solid rgba(255,255,255,.08)">
            <div style="flex:1">
                <div style="font-weight:900;color:#fff;font-size:14px">{{ $item['msg'] }}</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:.15rem">{{ $item['sub'] }}</div>
            </div>
            <a href="{{ $item['url'] }}" class="btn soft" style="font-size:12px;white-space:nowrap;background:rgba(20,184,166,.15);color:#4ade80;border-color:rgba(20,184,166,.25)">{{ $item['label'] }}</a>
        </div>
        @endforeach
    </div>
</div>
@elseif($examCount === 0)
<div class="card mb" style="background:linear-gradient(135deg,#0f172a,#1e293b);border:none;padding:1.5rem">
    <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:.75rem">Mulai dari sini</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.65rem">
        @foreach([['🧩','Buat soal di Bank Soal','Tambah soal yang bisa dipakai ulang',route('question-bank.create')],['📝','Buat Ujian Baru','Isi jadwal, kelas, dan pilih soal',route('exams.create')],['🚀','Publish & Pantau','Bagikan kode ke siswa dan monitor','#']] as [$ico,$title,$sub,$url])
        <a href="{{ $url }}" style="display:flex;gap:.75rem;padding:.9rem;background:rgba(255,255,255,.06);border-radius:10px;border:1px solid rgba(255,255,255,.08);text-decoration:none;transition:.18s;align-items:flex-start" onmouseover="this.style.background='rgba(255,255,255,.1)'" onmouseout="this.style.background='rgba(255,255,255,.06)'">
            <span style="font-size:22px">{{ $ico }}</span>
            <div>
                <div style="font-weight:900;color:#fff;font-size:13px">{{ $title }}</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:.15rem">{{ $sub }}</div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- ───── STAT CARDS ───── --}}
<div class="grid mb">
    <div class="card stat-card s-teal">
        <div class="stat-card-icon">📝</div>
        <div class="stat-card-val">{{ $examCount }}</div>
        <div class="stat-card-label">Total Ujian</div>
    </div>
    <div class="card stat-card s-pink">
        <div class="stat-card-icon">▶</div>
        <div class="stat-card-val">{{ $runningCount }}</div>
        <div class="stat-card-label">Sedang Berjalan</div>
    </div>
    <div class="card stat-card s-violet">
        <div class="stat-card-icon">🧩</div>
        <div class="stat-card-val">{{ $bankCount }}</div>
        <div class="stat-card-label">Bank Soal</div>
    </div>
    <div class="card stat-card s-green">
        <div class="stat-card-icon">✅</div>
        <div class="stat-card-val">{{ $submittedCount }}</div>
        <div class="stat-card-label">Submit Masuk</div>
    </div>
</div>

@if(auth()->user()->isAdmin())
<div class="grid mb" style="grid-template-columns:repeat(4,1fr)">
    <div class="card stat-card s-sky">
        <div class="stat-card-icon">🎓</div>
        <div class="stat-card-val">{{ $studentCount }}</div>
        <div class="stat-card-label">Akun Siswa</div>
    </div>
    <div class="card stat-card s-teal">
        <div class="stat-card-icon">🏫</div>
        <div class="stat-card-val">{{ $classroomCount }}</div>
        <div class="stat-card-label">Kelas</div>
    </div>
    <div class="card stat-card s-amber">
        <div class="stat-card-icon">👨‍🏫</div>
        <div class="stat-card-val">{{ $teacherCount }}</div>
        <div class="stat-card-label">Guru</div>
    </div>
    <div class="card stat-card s-violet">
        <div class="stat-card-icon">📊</div>
        <div class="stat-card-val">{{ $publishedCount }}</div>
        <div class="stat-card-label">Published</div>
    </div>
</div>
@endif

{{-- ───── UPCOMING + SIAP PUBLISH ───── --}}
<div class="two mb">

    {{-- Ujian Segera --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div class="between" style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--line)">
            <div>
                <h2 class="mb0" style="font-size:16px">⏰ Segera Dimulai</h2>
                <p class="muted small mb0">Dalam 48 jam ke depan</p>
            </div>
            @if($upcomingExams->isNotEmpty())
                <span class="badge warning">{{ $upcomingExams->count() }} ujian</span>
            @endif
        </div>
        @forelse($upcomingExams as $exam)
            @php $diffMin = now()->diffInMinutes($exam->starts_at, false); @endphp
            <div class="upcoming-strip">
                <div style="flex:1;min-width:0">
                    <b style="font-size:14px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $exam->title }}</b>
                    <span class="muted small">{{ $exam->access_code }} · {{ $exam->participants_count }} peserta</span>
                </div>
                <div class="upcoming-time">
                    <div class="big">{{ $exam->starts_at->format('H:i') }}</div>
                    <div class="sub">{{ $exam->starts_at->format('d M') }}</div>
                </div>
                <a class="btn soft" href="{{ route('exams.show', $exam) }}" style="padding:.45rem .7rem;font-size:12px">Detail</a>
            </div>
        @empty
            <div style="padding:2rem;text-align:center;color:var(--muted)">
                <div style="font-size:32px;margin-bottom:.5rem">🗓</div>
                <div class="small">Tidak ada ujian dalam 48 jam ke depan</div>
            </div>
        @endforelse
    </div>

    {{-- Siap Dipublish --}}
    <div class="card" style="padding:0;overflow:hidden">
        <div class="between" style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--line)">
            <div>
                <h2 class="mb0" style="font-size:16px">🚀 Siap Dipublish</h2>
                <p class="muted small mb0">Semua checklist terpenuhi</p>
            </div>
            @if($readyToPublishExams->isNotEmpty())
                <span class="badge warning">{{ $readyToPublishExams->count() }} menunggu</span>
            @endif
        </div>
        @forelse($readyToPublishExams as $exam)
            <div class="upcoming-strip">
                <div style="flex:1;min-width:0">
                    <b style="font-size:14px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $exam->title }}</b>
                    <span class="muted small">{{ $exam->questions_count }} soal · {{ $exam->participants_count }} peserta{{ $exam->starts_at ? ' · ' . $exam->starts_at->format('d M H:i') : '' }}</span>
                </div>
                <form method="POST" action="{{ route('exams.publish', $exam) }}">
                    @csrf
                    <button class="btn green" style="padding:.45rem .7rem;font-size:12px;white-space:nowrap">✓ Publish</button>
                </form>
            </div>
        @empty
            <div style="padding:2rem;text-align:center;color:var(--muted)">
                <div style="font-size:32px;margin-bottom:.5rem">✅</div>
                <div class="small">Tidak ada ujian yang menunggu dipublish</div>
            </div>
        @endforelse
    </div>

</div>

{{-- ───── MONITOR AKTIF ───── --}}
<div class="card mb" style="padding:0;overflow:hidden">
    <div class="between" style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--line)">
        <div class="row">
            <h2 class="mb0" style="font-size:16px">Monitor Ujian Aktif</h2>
            @if($runningExams->isNotEmpty())
                <span class="row" style="gap:.35rem"><span class="pulse-dot"></span><span class="badge success" style="font-size:11px">{{ $runningExams->count() }} live</span></span>
            @endif
        </div>
    </div>
    @forelse($runningExams as $exam)
        <div class="exam-row">
            <div class="exam-title-col">
                <b>{{ $exam->title }}</b>
                <span>{{ $exam->access_code }} · {{ $exam->subject ?: 'Tanpa mapel' }}</span>
            </div>
            <div class="row" style="flex-wrap:nowrap">
                @foreach($exam->classrooms->take(2) as $c)
                    <span class="badge" style="font-size:11px">{{ $c->nama_kelas }}</span>
                @endforeach
            </div>
            <a class="btn primary" href="{{ route('exams.monitor', $exam) }}" style="padding:.45rem .85rem;font-size:13px">Monitor</a>
        </div>
    @empty
        <div style="padding:2.5rem;text-align:center;color:var(--muted)">
            <div style="font-size:38px;margin-bottom:.6rem">📡</div>
            <b style="display:block;margin-bottom:.25rem;color:var(--heading)">Tidak ada ujian yang sedang berjalan</b>
            <span class="small">Ujian yang sudah dipublish dan dalam jadwal aktif akan muncul di sini.</span>
        </div>
    @endforelse
</div>

{{-- ───── UJIAN TERBARU ───── --}}
<div class="card data-card mb">
    <div class="table-toolbar">
        <div class="table-title">
            <h2 style="font-size:16px">Ujian Terbaru</h2>
        </div>
        <div class="row">
            @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
                <a class="btn soft" href="{{ route('exams.index') }}">Lihat Semua →</a>
            @endif
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Ujian</th><th>Kode</th><th>Status</th><th>Jadwal</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($recentExams as $exam)
                <tr>
                    <td>
                        <b>{{ $exam->title }}</b><br>
                        <span class="muted small">{{ $exam->subject ?: '–' }} · {{ $exam->grade_level ?: '–' }}</span>
                    </td>
                    <td><span class="badge">{{ $exam->access_code }}</span></td>
                    <td><span class="badge {{ $exam->status }}">{{ $exam->operationalStatus() }}</span></td>
                    <td class="small">
                        {{ optional($exam->starts_at)->format('d M Y H:i') ?: '–' }}<br>
                        <span class="muted">{{ optional($exam->ends_at)->format('H:i') ? '↳ ' . optional($exam->ends_at)->format('H:i') : '' }}</span>
                    </td>
                    <td class="row">
                        @if(auth()->user()->canManageExam($exam))
                            <a class="btn soft" href="{{ route('exams.show', $exam) }}" style="padding:.4rem .75rem;font-size:12px">Kelola</a>
                        @endif
                        <a class="btn ghost" href="{{ route('exams.monitor', $exam) }}" style="padding:.4rem .75rem;font-size:12px">Monitor</a>
                    </td>
                </tr>
            @empty
                <tr data-empty-row>
                    <td colspan="5" style="text-align:center;padding:2.5rem;color:var(--muted)">
                        Belum ada ujian. <a href="{{ route('exams.create') }}" style="color:var(--primary);font-weight:900">Buat ujian pertama →</a>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ───── AUDIT ───── --}}
@if(auth()->user()->isAdmin() && $recentAuditLogs->isNotEmpty())
<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title"><h2 style="font-size:16px">Aktivitas Terbaru</h2></div>
        <a class="btn ghost" href="{{ route('audit.index') }}" style="font-size:13px">Lihat Semua Audit →</a>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Waktu</th><th>Event</th><th>Pengguna</th></tr></thead>
            <tbody>
            @foreach($recentAuditLogs as $log)
                <tr>
                    <td class="small muted">{{ $log->created_at->diffForHumans() }}</td>
                    <td><span class="badge info" style="font-size:11px">{{ $log->event }}</span></td>
                    <td class="small"><b>{{ $log->user?->name ?: 'System' }}</b></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
