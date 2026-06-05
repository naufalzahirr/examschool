@extends('layouts.app', ['title' => $exam->title])

@push('head')
<style>
.exam-hero{background:linear-gradient(135deg,rgba(20,184,166,.13) 0%,rgba(255,90,122,.09) 100%),#fff;border:1px solid rgba(20,184,166,.18);border-radius:var(--radius);box-shadow:0 14px 34px rgba(15,23,42,.08);padding:1.55rem;position:relative;overflow:hidden;margin-bottom:1.25rem}
.exam-hero:before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;background:linear-gradient(180deg,var(--primary),var(--accent))}

/* Status-aware banner */
.next-action-banner{border-radius:var(--radius);padding:1.25rem 1.5rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:1.1rem}
.next-action-banner.draft{background:linear-gradient(135deg,#fffbf0,#fff7e0);border:1px solid #fde68a}
.next-action-banner.published{background:linear-gradient(135deg,#f0fdf9,#e8f9f5);border:1px solid #99f6e4}
.next-action-banner.closed{background:linear-gradient(135deg,#fafafa,#f1f5f9);border:1px solid var(--line)}
.next-action-icon{width:54px;height:54px;border-radius:14px;display:grid;place-items:center;font-size:26px;flex-shrink:0}

/* Code share box */
.code-share-box{background:#0f172a;border-radius:var(--radius);padding:1.15rem 1.35rem;display:flex;align-items:center;gap:1rem;margin-bottom:.75rem}
.code-share-code{font-size:28px;font-weight:950;letter-spacing:.12em;color:#fff;font-family:monospace}
.code-share-copy{background:rgba(20,184,166,.2);border:1px solid rgba(20,184,166,.35);color:#4ade80;border-radius:8px;padding:.5rem 1rem;font-weight:900;font-size:13px;cursor:pointer;transition:.18s}
.code-share-copy:hover{background:rgba(20,184,166,.35)}
.code-share-copy.copied{background:rgba(34,197,94,.2);color:#4ade80}

/* Steps nav */
.nav-btn{display:flex;align-items:center;gap:.75rem;padding:.9rem 1rem;border:1px solid var(--line);border-radius:var(--radius);background:#fff;color:var(--heading);font-weight:800;font-size:14px;transition:all .18s;text-decoration:none;cursor:pointer}
.nav-btn:hover{border-color:#99f6e4;background:#f0fdfb;transform:translateX(3px)}
.nav-btn .ico{width:36px;height:36px;border-radius:8px;display:grid;place-items:center;font-size:16px;flex-shrink:0}
.nav-btn .badge-right{margin-left:auto;flex-shrink:0}

/* Checklist */
.cl-row{display:flex;align-items:center;gap:.65rem;padding:.75rem .9rem;border-radius:var(--radius);border:1px solid var(--line);background:#fff;margin-bottom:.4rem}
.cl-row.ok{background:var(--success-soft);border-color:#bbf7d0}
.cl-row.fail{background:#fffbf0;border-color:#fde68a}
.cl-icon{width:26px;height:26px;border-radius:50%;display:grid;place-items:center;font-size:12px;flex-shrink:0;font-weight:900;color:#fff}
.cl-icon.ok{background:var(--success)}
.cl-icon.fail{background:var(--warning)}

/* Danger zone */
.danger-zone{border:1px solid #fee2e2;border-radius:var(--radius);overflow:hidden;margin-bottom:1.25rem}
.danger-summary{padding:.9rem 1.15rem;font-weight:800;color:#94a3b8;cursor:pointer;display:flex;align-items:center;gap:.5rem;list-style:none}
.danger-summary:hover{color:var(--heading)}
.danger-body{padding:1rem 1.15rem;border-top:1px solid #fee2e2;background:#fff9f9}

/* Timeline */
.tl-step{display:flex;gap:.85rem;padding:.85rem 1rem;border-radius:var(--radius);border:1px solid var(--line);background:#fff;margin-bottom:.4rem;align-items:center}
.tl-step.active{background:var(--success-soft);border-color:#bbf7d0}
.tl-step.done{background:#f8fafc;border-color:var(--line)}
.tl-num{width:30px;height:30px;border-radius:50%;display:grid;place-items:center;font-weight:950;font-size:13px;flex-shrink:0;color:#fff}
</style>
@endpush

@section('content')

@php
    $isReady = collect($readiness)->every(fn($i) => $i['ok']);
    $dlOpen  = $queueStats['download_window_open'];
    $dlAt    = $queueStats['download_opens_at'];
    $now     = now();
    $started = $exam->starts_at && $now->greaterThanOrEqualTo($exam->starts_at);
    $ended   = $exam->ends_at   && $now->greaterThan($exam->ends_at);

    $isDraft     = in_array($exam->status, ['draft','ready']);
    $isPublished = $exam->status === 'published';
    $isClosed    = in_array($exam->status, ['closed','archived']);
@endphp

{{-- ═══ HERO ═══ --}}
<div class="exam-hero">
    <div class="between" style="align-items:flex-start">
        <div style="flex:1;min-width:0">
            <div class="row" style="margin-bottom:.55rem;gap:.4rem">
                <span class="badge {{ $exam->status }}" style="font-size:11px">{{ $exam->operationalStatus() }}</span>
                @if($exam->published_at)
                    <span class="muted small">Dipublish {{ $exam->published_at->format('d M Y H:i') }}</span>
                @endif
            </div>
            <h1 style="margin:0 0 .3rem;font-size:24px">{{ $exam->title }}</h1>
            <p class="muted mb0" style="font-size:13px">
                {{ $exam->subject ?: 'Tanpa mapel' }}@if($exam->grade_level) · {{ $exam->grade_level }}@endif
                @if($exam->starts_at) · {{ $exam->starts_at->format('d M Y, H:i') }}@if($exam->ends_at) – {{ $exam->ends_at->format('H:i') }}@endif @endif
            </p>
        </div>
        <div class="row" style="flex-wrap:nowrap;gap:.5rem">
            <a class="btn ghost" href="{{ route('exams.index') }}" style="font-size:12px">← Kembali</a>
            <a class="btn ghost" href="{{ route('exams.edit', $exam) }}" style="font-size:12px">Edit</a>
        </div>
    </div>
</div>

{{-- ═══ BANNER STATUS-AWARE (PANDUAN KONTEKSTUAL) ═══ --}}
@if($isDraft && !$isReady)
{{-- Draft: Masih ada yang kurang --}}
<div class="next-action-banner draft">
    <div class="next-action-icon" style="background:#fef3c7">⚠️</div>
    <div style="flex:1">
        <b style="font-size:15px;color:#92400e">Ujian belum siap dipublish</b>
        <p class="mb0" style="font-size:13px;color:#78350f;margin-top:.2rem">
            {{ collect($readiness)->filter(fn($i) => !$i['ok'])->count() }} item belum lengkap. Selesaikan checklist di bawah, lalu tombol Publish akan aktif.
        </p>
    </div>
</div>

@elseif($isDraft && $isReady)
{{-- Draft: Siap dipublish --}}
<div class="next-action-banner published">
    <div class="next-action-icon" style="background:var(--success-soft)">🚀</div>
    <div style="flex:1">
        <b style="font-size:15px;color:#166534">Semua siap! Ujian bisa dipublish sekarang.</b>
        <p class="mb0" style="font-size:13px;color:#14532d;margin-top:.2rem">
            Setelah dipublish, sistem otomatis menyiapkan soal. Bagikan kode <b>{{ $exam->access_code }}</b> ke siswa — mereka bisa download soal mulai sekarang.
        </p>
    </div>
    <form method="POST" action="{{ route('exams.publish', $exam) }}">
        @csrf
        <button class="btn primary" style="white-space:nowrap;min-width:130px">🚀 Publish Sekarang</button>
    </form>
</div>

@elseif($isPublished)
{{-- Published: Bagikan kode + panduan --}}
<div class="next-action-banner published">
    <div class="next-action-icon" style="background:var(--primary-soft)">📡</div>
    <div style="flex:1">
        <b style="font-size:15px;color:#0f766e">Ujian aktif — bagikan kode ini ke siswa</b>
        <p class="mb0" style="font-size:13px;color:#134e4a;margin-top:.2rem">
            Siswa login di aplikasi menggunakan kode ujian, NIS, dan password mereka.
            @if($exam->starts_at && !$started) Download soal dibuka, soal bisa diakses mulai <b>{{ $exam->starts_at->format('d M H:i') }}</b>. @endif
        </p>
    </div>
</div>
<div class="code-share-box" style="margin-bottom:1.25rem">
    <div>
        <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:.25rem">Kode Ujian</div>
        <div class="code-share-code">{{ $exam->access_code }}</div>
    </div>
    <button class="code-share-copy" onclick="copyCode('{{ $exam->access_code }}',this)" id="copyBtn">Salin Kode</button>
    <div style="border-left:1px solid rgba(255,255,255,.12);padding-left:1rem;color:#94a3b8;font-size:12px;line-height:1.5">
        Siswa masuk ke aplikasi →<br>
        Ketik kode ini → NIS → Password
    </div>
</div>

@elseif($isClosed)
{{-- Closed/Archived: Lihat hasil --}}
<div class="next-action-banner closed">
    <div class="next-action-icon" style="background:#f1f5f9">📊</div>
    <div style="flex:1">
        <b style="font-size:15px;color:var(--heading)">Ujian selesai — lihat dan export hasilnya</b>
        <p class="mb0" style="font-size:13px;color:var(--muted);margin-top:.2rem">
            Nilai siswa sudah tersedia. Export ke CSV untuk pengolahan lebih lanjut.
        </p>
    </div>
    <div class="row">
        <a class="btn primary" href="{{ route('exams.results', $exam) }}">📊 Lihat Hasil</a>
        <a class="btn soft" href="{{ route('exams.results.export', $exam) }}">↓ Export CSV</a>
    </div>
</div>
@endif

{{-- ═══ 4 STAT CARDS ═══ --}}
<div class="grid mb">
    <div class="card" style="text-align:center">
        <div style="font-size:32px;font-weight:950;color:var(--primary)">{{ $exam->questions_count }}</div>
        <div class="muted small">Soal</div>
    </div>
    <div class="card" style="text-align:center">
        <div style="font-size:32px;font-weight:950;color:var(--violet)">{{ $exam->classrooms_count }}</div>
        <div class="muted small">Kelas</div>
    </div>
    <div class="card" style="text-align:center">
        <div style="font-size:32px;font-weight:950;color:var(--accent)">{{ $exam->participants_count }}</div>
        <div class="muted small">Peserta</div>
    </div>
    <div class="card" style="text-align:center">
        <div style="font-size:32px;font-weight:950;color:var(--warning)">{{ $exam->duration_minutes }}</div>
        <div class="muted small">Menit</div>
    </div>
</div>

{{-- ═══ MAIN CONTENT ═══ --}}
<div class="two mb">

    {{-- Kolom kiri: Checklist (draft) atau Menu Aksi (published/closed) --}}
    <div style="display:flex;flex-direction:column;gap:1.1rem">

        @if($isDraft)
        {{-- DRAFT: Tampilkan checklist --}}
        <div class="card">
            <div class="between" style="margin-bottom:.9rem">
                <div>
                    <h2 class="mb0" style="font-size:16px">Checklist Sebelum Publish</h2>
                    <p class="muted small mb0">Semua harus hijau sebelum ujian bisa diaktifkan</p>
                </div>
                <span class="badge {{ $isReady ? 'published' : 'warning' }}">{{ $isReady ? '✓ Siap' : 'Belum Lengkap' }}</span>
            </div>
            @foreach($readiness as $item)
                <div class="cl-row {{ $item['ok'] ? 'ok' : 'fail' }}">
                    <div class="cl-icon {{ $item['ok'] ? 'ok' : 'fail' }}">{{ $item['ok'] ? '✓' : '!' }}</div>
                    <div style="flex:1">
                        <div style="font-size:13px;font-weight:800">{{ $item['label'] }}</div>
                        <div class="muted small">{{ $item['note'] }}</div>
                    </div>
                    @if(!$item['ok'])
                        @if(str_contains(strtolower($item['label']), 'soal'))
                            <a href="{{ route('exams.question-bank.select', $exam) }}" class="btn soft" style="font-size:11px;padding:.3rem .65rem">Tambah Soal</a>
                        @elseif(str_contains(strtolower($item['label']), 'kelas') || str_contains(strtolower($item['label']), 'peserta') || str_contains(strtolower($item['label']), 'jadwal'))
                            <a href="{{ route('exams.edit', $exam) }}" class="btn soft" style="font-size:11px;padding:.3rem .65rem">Edit</a>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
        @endif

        {{-- Menu navigasi --}}
        <div class="card">
            <h2 style="font-size:16px;margin-bottom:.85rem">
                @if($isDraft) Kelola Ujian @elseif($isPublished) Pantau & Kelola @else Riwayat & Hasil @endif
            </h2>

            @if($isDraft && $exam->canEditQuestions())
                <a class="nav-btn" href="{{ route('exams.question-bank.select', $exam) }}" style="margin-bottom:.45rem;display:flex">
                    <div class="ico" style="background:var(--violet-soft)">🧩</div>
                    <div>
                        <div>Ambil Soal dari Bank Soal</div>
                        <div class="muted small" style="font-weight:700;font-size:12px">Pilih paket soal untuk ujian ini</div>
                    </div>
                    <span class="badge nav-btn-badge">{{ $exam->questions_count }} soal</span>
                </a>
            @endif

            @if($exam->canEditQuestions())
                <a class="nav-btn" href="{{ route('exams.builder', $exam) }}" style="margin-bottom:.45rem;display:flex">
                    <div class="ico" style="background:var(--info-soft)">✏️</div>
                    <div>
                        <div>Review Soal Ujian</div>
                        <div class="muted small" style="font-weight:700;font-size:12px">Lihat dan edit salinan soal</div>
                    </div>
                </a>
            @elseif(!$isDraft)
                <a class="nav-btn" href="{{ route('exams.builder', $exam) }}" style="margin-bottom:.45rem;display:flex">
                    <div class="ico" style="background:var(--info-soft)">📋</div>
                    <div>
                        <div>Lihat Soal (Mode Baca)</div>
                        <div class="muted small" style="font-weight:700;font-size:12px">Soal terkunci, hanya bisa dilihat</div>
                    </div>
                    <span class="badge nav-btn-badge">{{ $exam->questions_count }} soal</span>
                </a>
            @endif

            <a class="nav-btn" href="{{ route('exams.participants', $exam) }}" style="margin-bottom:.45rem;display:flex">
                <div class="ico" style="background:var(--accent-soft)">👥</div>
                <div>
                    <div>Daftar Peserta</div>
                    <div class="muted small" style="font-weight:700;font-size:12px">Lihat status, reset HP, hapus peserta</div>
                </div>
                <span class="badge nav-btn-badge">{{ $exam->participants_count }}</span>
            </a>

            @if($isPublished)
                <a class="nav-btn" href="{{ route('exams.monitor', $exam) }}" style="margin-bottom:.45rem;display:flex">
                    <div class="ico" style="background:var(--success-soft)">📡</div>
                    <div>
                        <div>Monitor Live</div>
                        <div class="muted small" style="font-weight:700;font-size:12px">Pantau siapa yang sudah submit, terkunci, dll</div>
                    </div>
                    @if($exam->isOpenNow())
                        <span class="badge published nav-btn-badge" style="font-size:10px">● Live</span>
                    @endif
                </a>
            @endif

            <a class="nav-btn" href="{{ route('exams.results', $exam) }}" style="display:flex">
                <div class="ico" style="background:var(--warning-soft)">📊</div>
                <div>
                    <div>Hasil & Statistik</div>
                    <div class="muted small" style="font-weight:700;font-size:12px">Nilai, distribusi, soal paling banyak salah</div>
                </div>
            </a>
        </div>
    </div>

    {{-- Kolom kanan: Info ujian --}}
    <div style="display:flex;flex-direction:column;gap:1.1rem">

        {{-- Timeline --}}
        <div class="card">
            <div class="between" style="margin-bottom:.85rem">
                <h2 class="mb0" style="font-size:16px">Alur Waktu Ujian</h2>
                <a href="{{ route('exams.edit', $exam) }}" class="btn ghost" style="font-size:12px;padding:.3rem .65rem">Ubah Jadwal</a>
            </div>

            <div class="tl-step {{ $dlOpen ? 'active' : '' }}">
                <div class="tl-num" style="background:{{ $dlOpen ? 'var(--primary)' : '#94a3b8' }}">1</div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:800">Siswa download soal</div>
                    <div class="muted small">Paket terenkripsi, belum bisa dibaca</div>
                </div>
                @if($dlOpen) <span class="badge published" style="font-size:11px">Sedang dibuka</span>
                @elseif($dlAt) <span class="badge" style="font-size:11px">{{ $dlAt }}</span>
                @else <span class="badge warning" style="font-size:11px">Setelah publish</span>
                @endif
            </div>

            <div class="tl-step {{ $started && !$ended ? 'active' : '' }}">
                <div class="tl-num" style="background:{{ $started && !$ended ? 'var(--success)' : '#94a3b8' }}">2</div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:800">Ujian dimulai</div>
                    <div class="muted small">Siswa aktifkan mode pesawat → soal terbuka</div>
                </div>
                @if($exam->starts_at)
                    <span class="badge {{ $started ? 'published' : '' }}" style="font-size:11px">{{ $exam->starts_at->format('d M H:i') }}</span>
                @else
                    <span class="badge warning" style="font-size:11px">Belum diatur</span>
                @endif
            </div>

            <div class="tl-step">
                <div class="tl-num" style="background:var(--warning)">3</div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:800">Durasi mengerjakan</div>
                    <div class="muted small">Timer mundur, jawaban tersimpan otomatis di HP</div>
                </div>
                <span class="badge warning" style="font-size:11px">{{ $exam->duration_minutes }} menit</span>
            </div>

            <div class="tl-step {{ $ended ? 'active' : '' }}">
                <div class="tl-num" style="background:{{ $ended ? 'var(--danger)' : '#94a3b8' }}">4</div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:800">Siswa kirim jawaban</div>
                    <div class="muted small">Matikan mode pesawat → nyalakan internet → kirim</div>
                </div>
                @if($exam->ends_at)
                    <span class="badge {{ $ended ? 'closed' : '' }}" style="font-size:11px">Batas {{ $exam->ends_at->format('d M H:i') }}</span>
                @else
                    <span class="badge warning" style="font-size:11px">Tidak ada batas</span>
                @endif
            </div>

            @if(!$exam->starts_at || !$exam->ends_at)
                <div class="alert warning" style="margin-top:.75rem;margin-bottom:0;font-size:13px">
                    Jadwal belum diatur. <a href="{{ route('exams.edit', $exam) }}" style="font-weight:900">Atur jadwal →</a>
                </div>
            @endif
        </div>

        {{-- Kelas & Status Download --}}
        <div class="card">
            <div class="between" style="margin-bottom:.75rem">
                <h2 class="mb0" style="font-size:16px">Kelas & Progress Download</h2>
            </div>
            <div class="row" style="flex-wrap:wrap;gap:.35rem;margin-bottom:.85rem">
                @forelse($exam->classrooms as $classroom)
                    <span class="badge" style="font-size:11px">{{ $classroom->nama_kelas }}</span>
                @empty
                    <span class="muted small">Belum ada kelas. <a href="{{ route('exams.edit', $exam) }}" style="color:var(--primary)">Pilih kelas →</a></span>
                @endforelse
            </div>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.6rem">
                <div class="mini-card" style="text-align:center">
                    <div style="font-size:22px;font-weight:950;color:var(--primary)">{{ $queueStats['downloaded'] }}</div>
                    <div class="muted small">Sudah download</div>
                </div>
                <div class="mini-card" style="text-align:center">
                    <div style="font-size:22px;font-weight:950;color:var(--success)">{{ $queueStats['unlocked'] }}</div>
                    <div class="muted small">Soal terbuka</div>
                </div>
            </div>
            @if($isPublished)
                <div class="alert info" style="margin-top:.75rem;margin-bottom:0;font-size:12px">
                    {{ $queueStats['active'] }}/{{ $queueStats['limit'] }} slot download aktif ·
                    {{ $queueStats['waiting'] }} menunggu antrean
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══ ZONA BERBAHAYA ═══ --}}
<details class="danger-zone">
    <summary class="danger-summary">
        <span style="color:#dc2626">⚠</span> Aksi Berbahaya — Tutup, Arsipkan, Hapus
        <span class="muted small" style="margin-left:auto;font-weight:700">Klik untuk buka</span>
    </summary>
    <div class="danger-body">
        <div class="row" style="flex-wrap:wrap;gap:.5rem;margin-bottom:.85rem">
            @if(in_array($exam->status, ['published','closed'], true))
                <form method="POST" action="{{ route('exams.unpublish', $exam) }}"
                      onsubmit="return confirm('Kembalikan ke draft?\n\nHanya bisa jika belum ada siswa yang mulai mengerjakan.')">
                    @csrf
                    <button class="btn warning">↩ Kembalikan ke Draft</button>
                </form>
            @endif
            @if(!in_array($exam->status, ['closed','archived'], true))
                <form method="POST" action="{{ route('exams.close', $exam) }}"
                      onsubmit="return confirm('Tutup ujian?\n\nSiswa tidak bisa lagi download atau submit jawaban.')">
                    @csrf
                    <button class="btn danger">Tutup Ujian</button>
                </form>
            @endif
            @if($exam->status !== 'archived')
                <form method="POST" action="{{ route('exams.archive', $exam) }}"
                      onsubmit="return confirm('Arsipkan ujian?\n\nDisembunyikan dari daftar aktif. Data dan hasil tetap tersimpan.')">
                    @csrf
                    <button class="btn">Arsipkan</button>
                </form>
            @endif
            @if(!$exam->hasStartedWork())
                <form method="POST" action="{{ route('exams.destroy', $exam) }}"
                      onsubmit="return confirm('HAPUS PERMANEN?\n\nSemua soal, peserta, dan data ujian dihapus selamanya.\nTidak bisa dibatalkan.')">
                    @csrf @method('DELETE')
                    <button class="btn danger" style="background:#7f1d1d;color:#fff">🗑 Hapus Permanen</button>
                </form>
            @else
                <span class="muted small" style="align-self:center">Tidak bisa dihapus — sudah ada aktivitas siswa. Gunakan Arsipkan.</span>
            @endif
        </div>
        <p class="help mb0">
            <b>Kembalikan ke Draft</b>: hanya jika belum ada aktivitas siswa ·
            <b>Tutup</b>: siswa tidak bisa submit baru ·
            <b>Arsipkan</b>: sembunyikan dari daftar, data tetap ·
            <b>Hapus</b>: permanen, hanya sebelum ada aktivitas
        </p>
    </div>
</details>

@endsection

@push('scripts')
<script>
function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
        btn.textContent = '✓ Tersalin!';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'Salin Kode'; btn.classList.remove('copied'); }, 2000);
    });
}
</script>
@endpush
