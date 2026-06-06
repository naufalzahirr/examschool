{{-- Exam workspace header + tab bar (Google Classroom style). Params: $exam, $tab --}}
@once
@push('head')
<style>
.ws-head{background:#fff;border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow-soft);margin-bottom:1.25rem;overflow:hidden}
.ws-top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:1.25rem 1.4rem .9rem}
.ws-title{font-size:21px;font-weight:950;color:var(--heading);margin:0;line-height:1.2}
.ws-meta{font-size:12.5px;color:var(--muted);margin-top:.25rem}
.ws-tabs{display:flex;gap:.15rem;padding:0 .8rem;border-top:1px solid var(--line);overflow-x:auto;scrollbar-width:none}
.ws-tabs::-webkit-scrollbar{display:none}
.ws-tab{position:relative;display:inline-flex;align-items:center;gap:.45rem;padding:.85rem .95rem;font-weight:800;font-size:13.5px;color:var(--muted);white-space:nowrap;border-bottom:3px solid transparent;transition:.15s}
.ws-tab:hover{color:var(--heading);background:#f8fafc}
.ws-tab.active{color:var(--primary-strong);border-bottom-color:var(--primary)}
.ws-tab .ws-tab-badge{font-size:10.5px;font-weight:900;background:#eef2f6;color:#475569;border-radius:999px;padding:.1rem .42rem;line-height:1.4}
.ws-tab.active .ws-tab-badge{background:var(--primary-soft);color:var(--primary-strong)}
.ws-tab-dot{width:7px;height:7px;border-radius:50%;background:var(--success);box-shadow:0 0 0 3px rgba(34,197,94,.2)}
@media(max-width:640px){.ws-top{flex-direction:column}.ws-tab{padding:.8rem .7rem;font-size:13px}}
</style>
@endpush
@endonce

@php
    $wsBadgeStatus = match($exam->status) {
        'published' => 'published',
        'closed','archived' => 'closed',
        default => 'draft',
    };
@endphp

<div class="ws-head">
    <div class="ws-top">
        <div style="min-width:0">
            <div class="row" style="gap:.4rem;margin-bottom:.35rem">
                <span class="badge {{ $exam->status }}" style="font-size:11px">{{ $exam->operationalStatus() }}</span>
                @if($exam->isManualMode() && $exam->status === 'published')
                    <span class="badge {{ $exam->manual_exam_open ? 'published' : 'warning' }}" style="font-size:11px">
                        {{ $exam->manual_exam_open ? 'Menerima jawaban' : 'Belum dibuka' }}
                    </span>
                @endif
            </div>
            <h1 class="ws-title">{{ $exam->title }}</h1>
            <div class="ws-meta">
                {{ $exam->subject ?: 'Tanpa mapel' }}@if($exam->grade_level) · {{ $exam->grade_level }}@endif
                · Kode <b style="color:var(--heading);letter-spacing:.04em">{{ $exam->access_code }}</b>
            </div>
        </div>
        <a class="btn ghost" href="{{ route('exams.index') }}" style="font-size:12px;flex-shrink:0">Daftar Ujian</a>
    </div>

    <nav class="ws-tabs">
        <a class="ws-tab {{ ($tab ?? '') === 'ringkasan' ? 'active' : '' }}" href="{{ route('exams.show', $exam) }}">
            Ringkasan
        </a>
        <a class="ws-tab {{ ($tab ?? '') === 'soal' ? 'active' : '' }}" href="{{ route('exams.builder', $exam) }}">
            Soal
            @if($exam->questions_count ?? null)<span class="ws-tab-badge">{{ $exam->questions_count }}</span>@endif
        </a>
        <a class="ws-tab {{ ($tab ?? '') === 'peserta' ? 'active' : '' }}" href="{{ route('exams.participants', $exam) }}">
            Peserta
            @if($exam->participants_count ?? null)<span class="ws-tab-badge">{{ $exam->participants_count }}</span>@endif
        </a>
        <a class="ws-tab {{ ($tab ?? '') === 'pelaksanaan' ? 'active' : '' }}" href="{{ route('exams.monitor', $exam) }}">
            Pelaksanaan
            @if($exam->isOpenNow())<span class="ws-tab-dot" title="Sedang berlangsung"></span>@endif
        </a>
        <a class="ws-tab {{ ($tab ?? '') === 'hasil' ? 'active' : '' }}" href="{{ route('exams.results', $exam) }}">
            Hasil
        </a>
        @if(auth()->user()->canManageExam($exam))
        <a class="ws-tab {{ ($tab ?? '') === 'pengaturan' ? 'active' : '' }}" href="{{ route('exams.edit', $exam) }}">
            Pengaturan
        </a>
        @endif
    </nav>
</div>
