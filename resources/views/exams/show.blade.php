@extends('layouts.app', ['title' => $exam->title])

@push('head')
<style>
.next-action{border-radius:var(--radius);padding:1.2rem 1.4rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:1.1rem}
.next-action.draft{background:linear-gradient(135deg,#fffbf0,#fff7e0);border:1px solid #fde68a}
.next-action.ok{background:linear-gradient(135deg,#f0fdf9,#e8f9f5);border:1px solid #99f6e4}
.next-action.closed{background:linear-gradient(135deg,#fafafa,#f1f5f9);border:1px solid var(--line)}
.na-ico{width:48px;height:48px;border-radius:12px;display:grid;place-items:center;font-size:13px;font-weight:950;flex-shrink:0}

.code-box{background:#0f172a;border-radius:var(--radius);padding:1.1rem 1.35rem;display:flex;align-items:center;gap:1.1rem;margin-bottom:1.25rem;flex-wrap:wrap}
.code-val{font-size:26px;font-weight:950;letter-spacing:.14em;color:#fff;font-family:ui-monospace,monospace}
.code-copy{background:rgba(20,184,166,.2);border:1px solid rgba(20,184,166,.35);color:#5eead4;border-radius:8px;padding:.5rem 1rem;font-weight:900;font-size:13px;cursor:pointer;transition:.18s}
.code-copy:hover{background:rgba(20,184,166,.35)}

.resp-ctl{border:1px solid {{ $exam->manual_exam_open ? '#bbf7d0' : 'var(--line)' }};background:#fff;border-radius:var(--radius);box-shadow:var(--shadow-soft);padding:1.25rem;margin-bottom:1.25rem}
.resp-switch{min-width:120px;height:46px;border-radius:999px;border:0;display:inline-flex;align-items:center;justify-content:center;gap:.5rem;font-weight:950;cursor:pointer;box-shadow:0 8px 18px rgba(15,23,42,.10);transition:.16s}
.resp-switch.on{background:var(--danger-soft);color:#991b1b}
.resp-switch.off{background:linear-gradient(135deg,var(--primary),#10b981);color:#fff}
.resp-dot{width:11px;height:11px;border-radius:50%;background:currentColor;opacity:.75}

.cl-row{display:flex;align-items:center;gap:.65rem;padding:.7rem .85rem;border-radius:var(--radius);border:1px solid var(--line);background:#fff;margin-bottom:.4rem}
.cl-row.ok{background:var(--success-soft);border-color:#bbf7d0}
.cl-row.fail{background:#fffbf0;border-color:#fde68a}
.cl-icon{width:24px;height:24px;border-radius:50%;display:grid;place-items:center;font-size:11px;flex-shrink:0;font-weight:900;color:#fff}
.cl-icon.ok{background:var(--success)}.cl-icon.fail{background:var(--warning)}

.tl-step{display:flex;gap:.8rem;padding:.8rem .95rem;border-radius:var(--radius);border:1px solid var(--line);background:#fff;margin-bottom:.4rem;align-items:center}
.tl-step.active{background:var(--success-soft);border-color:#bbf7d0}
.tl-num{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;font-weight:950;font-size:12px;flex-shrink:0;color:#fff}
.mini-stat{text-align:center;padding:1rem}
.mini-stat .v{font-size:30px;font-weight:950;line-height:1}
.mini-stat .l{font-size:12px;color:var(--muted);margin-top:.3rem}
</style>
@endpush

@section('content')

@php
    $isReady = collect($readiness)->every(fn($i) => $i['ok']);
    $dlOpen  = $queueStats['download_window_open'];
    $now     = now();
    $started = $exam->starts_at && $now->greaterThanOrEqualTo($exam->starts_at);
    $ended   = $exam->ends_at   && $now->greaterThan($exam->ends_at);
    $isDraft     = in_array($exam->status, ['draft','ready']);
    $isPublished = $exam->status === 'published';
    $isClosed    = in_array($exam->status, ['closed','archived']);
    $isManual    = $exam->isManualMode();
    $examOpenNow = $isManual ? (bool) $exam->manual_exam_open : ($started && ! $ended);
@endphp

@include('exams._workspace', ['tab' => 'ringkasan'])

{{-- ═══ NEXT ACTION ═══ --}}
@if($isDraft && !$isReady)
<div class="next-action draft">
    <div class="na-ico" style="background:#fef3c7">!</div>
    <div style="flex:1">
        <b style="font-size:15px;color:#92400e">Ujian belum siap dipublish</b>
        <p class="mb0" style="font-size:13px;color:#78350f;margin-top:.2rem">{{ collect($readiness)->filter(fn($i) => !$i['ok'])->count() }} item belum lengkap. Selesaikan checklist di bawah.</p>
    </div>
</div>
@elseif($isDraft && $isReady)
<div class="next-action ok">
    <div class="na-ico" style="background:var(--success-soft)">OK</div>
    <div style="flex:1">
        <b style="font-size:15px;color:#166534">Semua siap. Publish ujian sekarang.</b>
        <p class="mb0" style="font-size:13px;color:#14532d;margin-top:.2rem">Setelah publish, kamu bisa buka/tutup ujian seperti Google Form dari sini.</p>
    </div>
    <form method="POST" action="{{ route('exams.publish', $exam) }}">@csrf
        <button class="btn primary" style="white-space:nowrap;min-width:130px">Publish Ujian</button>
    </form>
</div>
@elseif($isClosed)
<div class="next-action closed">
    <div class="na-ico" style="background:#f1f5f9">HS</div>
    <div style="flex:1">
        <b style="font-size:15px;color:var(--heading)">Ujian selesai — lihat & export hasil</b>
        <p class="mb0" style="font-size:13px;color:var(--muted);margin-top:.2rem">Nilai siswa sudah tersedia di tab Hasil.</p>
    </div>
    <div class="row">
        <a class="btn primary" href="{{ route('exams.results', $exam) }}">Lihat Hasil</a>
        <a class="btn soft" href="{{ route('exams.results.export', $exam) }}">Export CSV</a>
    </div>
</div>
@endif

{{-- ═══ PUBLISHED: kode + kontrol ═══ --}}
@if($isPublished)
<div class="code-box">
    <div>
        <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:.25rem">Kode Ujian</div>
        <div class="code-val">{{ $exam->access_code }}</div>
    </div>
    <button class="code-copy" onclick="copyCode('{{ $exam->access_code }}',this)">Salin Kode</button>
    <div style="border-left:1px solid rgba(255,255,255,.12);padding-left:1.1rem;color:#94a3b8;font-size:12px;line-height:1.5">
        Siswa buka aplikasi →<br>masukkan Kode · NIS · Password
    </div>
</div>

@if($isManual)
<div class="resp-ctl">
    <div class="between" style="gap:1.25rem">
        <div>
            <div class="muted small" style="font-weight:900;text-transform:uppercase;letter-spacing:.08em">Form Siswa</div>
            <div style="font-size:21px;font-weight:950;color:var(--heading);margin:.1rem 0">{{ $exam->manual_exam_open ? 'Menerima jawaban' : 'Belum menerima jawaban' }}</div>
            <p class="muted mb0" style="font-size:13px">{{ $exam->manual_exam_open ? 'Siswa bisa membuka soal dan mulai mengerjakan.' : 'Buka tombol ini saat siswa siap mengerjakan (mis. ujian susulan).' }}</p>
        </div>
        <form method="POST" action="{{ route('exams.toggleManual', $exam) }}">@csrf
            <input type="hidden" name="target" value="exam">
            <input type="hidden" name="state" value="{{ $exam->manual_exam_open ? 0 : 1 }}">
            <button class="resp-switch {{ $exam->manual_exam_open ? 'on' : 'off' }}"><span class="resp-dot"></span>{{ $exam->manual_exam_open ? 'Tutup' : 'Buka' }}</button>
        </form>
    </div>
</div>
@endif
@endif

{{-- ═══ STATS ═══ --}}
<div class="grid mb">
    <div class="card mini-stat"><div class="v" style="color:var(--primary)">{{ $exam->questions_count }}</div><div class="l">Soal</div></div>
    <div class="card mini-stat"><div class="v" style="color:var(--violet)">{{ $exam->classrooms_count }}</div><div class="l">Kelas</div></div>
    <div class="card mini-stat"><div class="v" style="color:var(--accent)">{{ $exam->participants_count }}</div><div class="l">Peserta</div></div>
    <div class="card mini-stat"><div class="v" style="color:var(--warning)">{{ $exam->duration_minutes }}</div><div class="l">Menit</div></div>
</div>

<div class="two mb">
    {{-- Kiri --}}
    <div style="display:flex;flex-direction:column;gap:1.1rem">
        @if($isDraft)
        <div class="card">
            <div class="between" style="margin-bottom:.85rem">
                <h2 class="mb0" style="font-size:16px">Checklist Sebelum Publish</h2>
                <span class="badge {{ $isReady ? 'published' : 'warning' }}">{{ $isReady ? 'Siap' : 'Belum Lengkap' }}</span>
            </div>
            @foreach($readiness as $item)
                <div class="cl-row {{ $item['ok'] ? 'ok' : 'fail' }}">
                    <div class="cl-icon {{ $item['ok'] ? 'ok' : 'fail' }}">{{ $item['ok'] ? 'OK' : '!' }}</div>
                    <div style="flex:1">
                        <div style="font-size:13px;font-weight:800">{{ $item['label'] }}</div>
                        <div class="muted small">{{ $item['note'] }}</div>
                    </div>
                    @if(!$item['ok'])
                        @if(str_contains(strtolower($item['label']), 'soal'))
                            <a href="{{ route('exams.question-bank.select', $exam) }}" class="btn soft" style="font-size:11px;padding:.3rem .65rem">Tambah</a>
                        @elseif(str_contains(strtolower($item['label']), 'kelas') || str_contains(strtolower($item['label']), 'peserta') || str_contains(strtolower($item['label']), 'jadwal'))
                            <a href="{{ route('exams.edit', $exam) }}" class="btn soft" style="font-size:11px;padding:.3rem .65rem">Atur</a>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
        @else
        {{-- Ringkasan pelaksanaan singkat (published/closed) --}}
        <div class="card">
            <div class="between" style="margin-bottom:.85rem">
                <h2 class="mb0" style="font-size:16px">Progres Pengerjaan</h2>
                <a href="{{ route('exams.monitor', $exam) }}" class="btn ghost" style="font-size:12px;padding:.3rem .65rem">Buka Monitor</a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.6rem">
                <div class="mini-card" style="text-align:center"><div style="font-size:22px;font-weight:950;color:var(--primary)">{{ $queueStats['downloaded'] }}</div><div class="muted small">Sudah download</div></div>
                <div class="mini-card" style="text-align:center"><div style="font-size:22px;font-weight:950;color:var(--success)">{{ $queueStats['unlocked'] }}</div><div class="muted small">Soal terbuka</div></div>
            </div>
            @if($isPublished)
            <div class="alert info" style="margin-top:.75rem;margin-bottom:0;font-size:12px">{{ $queueStats['active'] }}/{{ $queueStats['limit'] }} slot download aktif · {{ $queueStats['waiting'] }} menunggu antrean</div>
            @endif
        </div>
        @endif

        {{-- Kelas peserta --}}
        <div class="card">
            <div class="between" style="margin-bottom:.75rem">
                <h2 class="mb0" style="font-size:16px">Kelas Peserta</h2>
                <a href="{{ route('exams.participants', $exam) }}" class="btn ghost" style="font-size:12px;padding:.3rem .65rem">Kelola</a>
            </div>
            <div class="row" style="flex-wrap:wrap;gap:.35rem">
                @forelse($exam->classrooms as $classroom)
                    <span class="badge" style="font-size:11px">{{ $classroom->nama_kelas }}</span>
                @empty
                    <span class="muted small">Belum ada kelas. <a href="{{ route('exams.edit', $exam) }}" style="color:var(--primary)">Pilih kelas →</a></span>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Kanan: timeline --}}
    <div class="card">
        <div class="between" style="margin-bottom:.85rem">
            <h2 class="mb0" style="font-size:16px">{{ $isManual ? 'Status Akses Siswa' : 'Alur Waktu Ujian' }}</h2>
            <a href="{{ route('exams.edit', $exam) }}" class="btn ghost" style="font-size:12px;padding:.3rem .65rem">Ubah</a>
        </div>

        <div class="tl-step {{ $dlOpen ? 'active' : '' }}">
            <div class="tl-num" style="background:{{ $dlOpen ? 'var(--primary)' : '#94a3b8' }}">1</div>
            <div style="flex:1"><div style="font-size:13px;font-weight:800">Siswa download soal</div><div class="muted small">Paket terenkripsi, belum bisa dibaca</div></div>
            @if($dlOpen)<span class="badge published" style="font-size:11px">Dibuka</span>
            @elseif($isManual)<span class="badge warning" style="font-size:11px">Menunggu</span>
            @elseif($queueStats['download_opens_at'])<span class="badge" style="font-size:11px">{{ $queueStats['download_opens_at'] }}</span>
            @else<span class="badge warning" style="font-size:11px">Setelah publish</span>@endif
        </div>

        <div class="tl-step {{ $examOpenNow ? 'active' : '' }}">
            <div class="tl-num" style="background:{{ $examOpenNow ? 'var(--success)' : '#94a3b8' }}">2</div>
            <div style="flex:1"><div style="font-size:13px;font-weight:800">Ujian dimulai</div><div class="muted small">{{ $isManual ? 'Mengikuti tombol Buka/Tutup' : 'Siswa wajib mode pesawat, lalu soal terbuka' }}</div></div>
            @if($isManual)<span class="badge {{ $exam->manual_exam_open ? 'published' : 'warning' }}" style="font-size:11px">{{ $exam->manual_exam_open ? 'Dibuka' : 'Belum' }}</span>
            @elseif($exam->starts_at)<span class="badge {{ $started ? 'published' : '' }}" style="font-size:11px">{{ $exam->starts_at->format('d M H:i') }}</span>
            @else<span class="badge warning" style="font-size:11px">Belum diatur</span>@endif
        </div>

        <div class="tl-step">
            <div class="tl-num" style="background:var(--warning)">3</div>
            <div style="flex:1"><div style="font-size:13px;font-weight:800">Durasi mengerjakan</div><div class="muted small">Timer mundur, jawaban auto-tersimpan</div></div>
            <span class="badge warning" style="font-size:11px">{{ $exam->duration_minutes }} menit</span>
        </div>

        <div class="tl-step {{ $ended ? 'active' : '' }}">
            <div class="tl-num" style="background:{{ $ended ? 'var(--danger)' : '#94a3b8' }}">4</div>
            <div style="flex:1"><div style="font-size:13px;font-weight:800">Siswa kirim jawaban</div><div class="muted small">Nyalakan internet, lalu kirim</div></div>
            @if($isManual)<span class="badge {{ $exam->manual_exam_open ? 'published' : 'closed' }}" style="font-size:11px">{{ $exam->manual_exam_open ? 'Menerima' : 'Ditutup' }}</span>
            @elseif($exam->ends_at)<span class="badge {{ $ended ? 'closed' : '' }}" style="font-size:11px">{{ $exam->ends_at->format('d M H:i') }}</span>
            @else<span class="badge warning" style="font-size:11px">Tanpa batas</span>@endif
        </div>

        @if(!$isManual && (!$exam->starts_at || !$exam->ends_at))
        <div class="alert warning" style="margin-top:.75rem;margin-bottom:0;font-size:13px">Jadwal belum diatur. <a href="{{ route('exams.edit', $exam) }}" style="font-weight:900">Atur di Pengaturan →</a></div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
function copyCode(code, btn){
    navigator.clipboard.writeText(code).then(() => {
        const t = btn.textContent; btn.textContent = 'Tersalin!';
        setTimeout(() => btn.textContent = t, 1800);
    });
}
</script>
@endpush
