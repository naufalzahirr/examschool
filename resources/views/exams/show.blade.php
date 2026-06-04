@extends('layouts.app', ['title' => $exam->title])

@section('content')

{{-- ===== HEADER ===== --}}
<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0;">{{ $exam->title }}</h1>
            <p class="muted" style="margin:.4rem 0 .6rem">
                {{ $exam->subject ?: 'Tanpa mapel' }} · {{ $exam->grade_level ?: 'Tanpa jenjang' }} ·
                Kode: <b>{{ $exam->access_code }}</b>
            </p>
            <div class="row">
                <span class="badge {{ $exam->status }}">{{ $exam->operationalStatus() }}</span>
                @if($exam->starts_at)
                    <span class="pill">{{ $exam->starts_at->format('d M Y H:i') }} – {{ optional($exam->ends_at)->format('H:i') ?: 'fleksibel' }}</span>
                @else
                    <span class="pill">Jadwal fleksibel</span>
                @endif
                @if($exam->published_at)
                    <span class="pill muted small">Publish: {{ $exam->published_at->format('d M Y H:i') }}</span>
                @endif
            </div>
        </div>

        {{-- Tombol utama kontekstual di header --}}
        <div class="row" style="align-items:flex-start;flex-direction:column;gap:.5rem">
            @if(!in_array($exam->status, ['published','closed','archived'], true))
                @if(collect($readiness)->every(fn($item) => $item['ok']))
                    <form method="POST" action="{{ route('exams.publish', $exam) }}" style="width:100%">
                        @csrf
                        <button class="btn green" style="width:100%;justify-content:center">
                            ✅ Publish Ujian
                        </button>
                    </form>
                @else
                    <span class="btn" style="opacity:.5;cursor:not-allowed;width:100%;justify-content:center">
                        Belum Siap Publish
                    </span>
                    <span class="muted small" style="text-align:center">Selesaikan checklist dulu</span>
                @endif
            @elseif($exam->status === 'published')
                <span class="badge published" style="padding:.6rem 1rem">Ujian Aktif / Published</span>
                <a class="btn soft" href="{{ route('exams.monitor', $exam) }}" style="width:100%;justify-content:center">
                    Monitor Pelaksanaan
                </a>
            @elseif($exam->status === 'closed')
                <span class="badge closed" style="padding:.6rem 1rem">Ujian Ditutup</span>
                <a class="btn soft" href="{{ route('exams.results', $exam) }}" style="width:100%;justify-content:center">
                    Lihat Hasil
                </a>
            @endif
        </div>
    </div>
</div>

{{-- ===== STATISTIK SINGKAT ===== --}}
<div class="grid mb">
    <div class="card">
        <div class="muted small">Soal</div>
        <div class="stat">{{ $exam->questions_count }}</div>
    </div>
    <div class="card">
        <div class="muted small">Kelas</div>
        <div class="stat">{{ $exam->classrooms_count }}</div>
    </div>
    <div class="card">
        <div class="muted small">Peserta</div>
        <div class="stat">{{ $exam->participants_count }}</div>
    </div>
    <div class="card">
        <div class="muted small">Durasi</div>
        <div class="stat">{{ $exam->duration_minutes }}</div>
        <div class="muted small">menit</div>
    </div>
</div>

{{-- ===== LANGKAH SELANJUTNYA + CHECKLIST ===== --}}
<div class="two mb">

    {{-- Checklist kesiapan dengan quick-fix links --}}
    <div class="card">
        <div class="between mb">
            <div>
                <h2 class="mb0">Checklist Kesiapan</h2>
                <p class="muted small mb0">Semua harus hijau sebelum publish.</p>
            </div>
            <span class="badge {{ collect($readiness)->every(fn($item) => $item['ok']) ? 'published' : 'warning' }}">
                {{ collect($readiness)->every(fn($item) => $item['ok']) ? 'Siap Publish' : 'Belum Siap' }}
            </span>
        </div>
        <div class="check-list">
            @foreach($readiness as $item)
                <div class="check-pill" style="justify-content:space-between">
                    <span>{{ $item['ok'] ? '✅' : '⚠️' }} {{ $item['label'] }}</span>
                    <span class="muted small">{{ $item['note'] }}</span>
                </div>
            @endforeach
        </div>

        @if(!in_array($exam->status, ['published','closed','archived'], true))
            <div style="margin-top:1rem;padding-top:.75rem;border-top:1px solid var(--line)">
                <p class="muted small mb0">
                    @php $unfinished = collect($readiness)->filter(fn($item) => !$item['ok']); @endphp
                    @if($unfinished->isEmpty())
                        Semua checklist terpenuhi. Klik <b>Publish Ujian</b> di atas.
                    @else
                        {{ $unfinished->count() }} item perlu diselesaikan:
                        @if($unfinished->contains(fn($i) => str_contains(strtolower($i['label']), 'soal')))
                            <a href="{{ route('exams.question-bank.select', $exam) }}" class="btn soft" style="margin-top:.5rem;display:inline-flex">Ambil Soal dari Bank</a>
                        @endif
                        @if($unfinished->contains(fn($i) => str_contains(strtolower($i['label']), 'kelas') || str_contains(strtolower($i['label']), 'peserta')))
                            <a href="{{ route('exams.edit', $exam) }}" class="btn soft" style="margin-top:.5rem;display:inline-flex">Atur Kelas Peserta</a>
                        @endif
                        @if($unfinished->contains(fn($i) => str_contains(strtolower($i['label']), 'jadwal') || str_contains(strtolower($i['label']), 'durasi')))
                            <a href="{{ route('exams.edit', $exam) }}" class="btn soft" style="margin-top:.5rem;display:inline-flex">Atur Jadwal & Durasi</a>
                        @endif
                    @endif
                </p>
            </div>
        @endif
    </div>

    {{-- Navigasi cepat --}}
    <div class="card">
        <h2>Navigasi Cepat</h2>
        <div class="check-list">
            @if($exam->canEditQuestions())
                <a class="check-pill" href="{{ route('exams.question-bank.select', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                    <span>📚 Ambil Soal dari Bank Soal</span>
                    <span class="badge">{{ $exam->questions_count }} soal</span>
                </a>
                <a class="check-pill" href="{{ route('exams.builder', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                    <span>✏️ Review / Edit Soal</span>
                    <span class="muted small">draft mode</span>
                </a>
            @else
                <a class="check-pill" href="{{ route('exams.builder', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                    <span>📋 Lihat Soal</span>
                    <span class="badge">{{ $exam->questions_count }} soal</span>
                </a>
            @endif
            <a class="check-pill" href="{{ route('exams.participants', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                <span>👥 Kelola Peserta</span>
                <span class="badge">{{ $exam->participants_count }} peserta</span>
            </a>
            <a class="check-pill" href="{{ route('exams.monitor', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                <span>📡 Monitor Pelaksanaan</span>
                @if($exam->isOpenNow())
                    <span class="badge published">sedang dibuka</span>
                @else
                    <span class="muted small">real-time</span>
                @endif
            </a>
            <a class="check-pill" href="{{ route('exams.results', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                <span>📊 Lihat Hasil</span>
                <span class="muted small">nilai siswa</span>
            </a>
        </div>
    </div>
</div>


{{-- ===== TIMELINE UJIAN ===== --}}
<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Timeline Ujian</h2>
            <p class="muted small mb0">Urutan waktu yang dialami siswa dari download sampai selesai.</p>
        </div>
        @if($exam->starts_at)
            <a class="btn soft" href="{{ route('exams.edit', $exam) }}">Ubah Jadwal</a>
        @endif
    </div>

    @php
        $downloadOpensAt  = $queueStats['download_opens_at'];   // sudah format 'd M Y H:i'
        $downloadIsOpen   = $queueStats['download_window_open'];
        $now              = now();
        $step1Done = $downloadOpensAt && $exam->starts_at && $now->greaterThanOrEqualTo($exam->starts_at->copy()->subHours(12));
        $step2Done = $exam->starts_at && $now->greaterThanOrEqualTo($exam->starts_at);
        $step3Done = $exam->ends_at   && $now->greaterThan($exam->ends_at);
    @endphp

    <div style="display:grid;gap:.65rem">

        {{-- Step 1: Download tersedia --}}
        <div class="check-pill" style="justify-content:space-between;background:{{ $downloadIsOpen ? 'var(--success-soft)' : '#f6f7f9' }}">
            <span style="display:flex;align-items:center;gap:.5rem">
                <span style="font-size:18px">{{ $downloadIsOpen ? '✅' : '⏳' }}</span>
                <span>
                    <b>Siswa boleh download paket soal</b><br>
                    <span class="muted small">Download dibuka 12 jam sebelum ujian mulai</span>
                </span>
            </span>
            @if($downloadIsOpen)
                <span class="badge published">sedang dibuka</span>
            @elseif($downloadOpensAt)
                <span class="badge">{{ $downloadOpensAt }}</span>
            @else
                <span class="badge warning">belum ada jadwal</span>
            @endif
        </div>

        {{-- Step 2: Ujian mulai (unlock key tersedia) --}}
        <div class="check-pill" style="justify-content:space-between;background:{{ $step2Done && !$step3Done ? 'var(--success-soft)' : '#f6f7f9' }}">
            <span style="display:flex;align-items:center;gap:.5rem">
                <span style="font-size:18px">{{ $step2Done && !$step3Done ? '🟢' : ($step3Done ? '✅' : '🔒') }}</span>
                <span>
                    <b>Ujian dimulai — soal bisa dibuka</b><br>
                    <span class="muted small">Server memberikan unlock key. Siswa wajib mode pesawat sebelum mulai menjawab.</span>
                </span>
            </span>
            @if($exam->starts_at)
                <span class="badge {{ $step2Done ? 'published' : '' }}">{{ $exam->starts_at->format('d M Y H:i') }}</span>
            @else
                <span class="badge warning">jadwal fleksibel</span>
            @endif
        </div>

        {{-- Step 3: Durasi mengerjakan --}}
        <div class="check-pill" style="justify-content:space-between">
            <span style="display:flex;align-items:center;gap:.5rem">
                <span style="font-size:18px">⏱️</span>
                <span>
                    <b>Durasi mengerjakan</b><br>
                    <span class="muted small">Timer mundur dimulai saat siswa membuka soal (unlock key diterima).</span>
                </span>
            </span>
            <span class="badge">{{ $exam->duration_minutes }} menit</span>
        </div>

        {{-- Step 4: Ujian selesai --}}
        <div class="check-pill" style="justify-content:space-between;background:{{ $step3Done ? '#f0fdf4' : '#f6f7f9' }}">
            <span style="display:flex;align-items:center;gap:.5rem">
                <span style="font-size:18px">{{ $step3Done ? '🏁' : '🔚' }}</span>
                <span>
                    <b>Ujian ditutup — download & unlock tidak tersedia</b><br>
                    <span class="muted small">Setelah waktu ini, siswa tidak bisa download atau membuka soal baru.</span>
                </span>
            </span>
            @if($exam->ends_at)
                <span class="badge {{ $step3Done ? 'closed' : '' }}">{{ $exam->ends_at->format('d M Y H:i') }}</span>
            @else
                <span class="badge warning">tidak ada batas</span>
            @endif
        </div>

    </div>

    @if(!$exam->starts_at || !$exam->ends_at)
        <div class="alert warning" style="margin-top:.75rem;margin-bottom:0">
            Jadwal belum diatur. Tanpa jadwal, ujian bisa dibuka kapan saja dan jendela download selalu terbuka setelah publish.
            <a href="{{ route('exams.edit', $exam) }}">Atur jadwal →</a>
        </div>
    @endif
</div>

{{-- ===== KELAS PESERTA ===== --}}
<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Kelas Peserta</h2>
            <p class="muted small mb0">Siswa aktif dari kelas ini otomatis menjadi peserta.</p>
        </div>
        <div class="row">
            @if($exam->canEditQuestions())
                <a class="btn soft" href="{{ route('exams.edit', $exam) }}">Ubah Kelas</a>
            @endif
            <a class="btn soft" href="{{ route('exams.participants', $exam) }}">Kelola Peserta</a>
        </div>
    </div>
    <div class="row">
        @forelse($exam->classrooms as $classroom)
            <span class="badge">{{ $classroom->nama_kelas }}</span>
        @empty
            <span class="muted">Belum ada kelas dipilih. <a href="{{ route('exams.edit', $exam) }}">Pilih kelas →</a></span>
        @endforelse
    </div>
</div>


{{-- ===== MODE UJIAN OFFLINE ===== --}}
<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Aturan Ujian Offline</h2>
            <p class="muted small mb0">Dikirim ke paket soal. Aplikasi siswa menggunakan ini untuk mengunci layar dan mencatat pelanggaran.</p>
        </div>
        <a class="btn soft" href="{{ route('exams.edit', $exam) }}">Ubah Aturan</a>
    </div>
    <div class="three">
        <div>
            <div class="muted small">Mode Kunci Layar</div>
            <b>{{ $exam->lockModeLabel() }}</b>
        </div>
        <div>
            <div class="muted small">Aturan Keluar</div>
            <b>{{ $exam->exitPolicyLabel() }}</b>
        </div>
        <div>
            <div class="muted small">Pelanggaran</div>
            <b>Dicatat & dikirim saat submit</b>
        </div>
    </div>
</div>


{{-- ===== STATUS PAKET SOAL ===== --}}
<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Paket Soal untuk Siswa</h2>
            <p class="muted small mb0">Paket terenkripsi yang diunduh aplikasi siswa sebelum ujian dimulai.</p>
        </div>
        <span class="badge {{ $exam->hasGeneratedPackage() ? 'published' : 'warning' }}">
            {{ $exam->hasGeneratedPackage() ? 'Paket Tersedia' : 'Belum Ada Paket' }}
        </span>
    </div>

    <div class="grid mb">
        <div class="mini-card">
            <div class="muted small">Slot Aktif (download)</div>
            <div class="stat">{{ $queueStats['active'] }} / {{ $queueStats['limit'] }}</div>
        </div>
        <div class="mini-card">
            <div class="muted small">Menunggu Antrean</div>
            <div class="stat">{{ $queueStats['waiting'] }}</div>
        </div>
        <div class="mini-card">
            <div class="muted small">Sudah Download</div>
            <div class="stat">{{ $queueStats['downloaded'] }}</div>
        </div>
        <div class="mini-card">
            <div class="muted small">Sudah Unlock Soal</div>
            <div class="stat">{{ $queueStats['unlocked'] }}</div>
        </div>
    </div>

    <div class="alert info" style="margin-bottom:.75rem">
        <b>Jendela download:</b>
        @if($queueStats['download_window_open'])
            sedang dibuka.
        @else
            dibuka mulai {{ $queueStats['download_opens_at'] ?: 'setelah ujian dipublish' }}.
        @endif
        Soal baru terbuka setelah aplikasi meminta unlock key saat jam ujian dimulai.
    </div>

    <div class="row">
        <form method="POST" action="{{ route('exams.package.regenerate', $exam) }}" onsubmit="return confirm('Generate ulang paket soal? Jangan lakukan saat siswa sedang mengerjakan ujian.')">
            @csrf
            <button class="btn soft">⟳ Generate Ulang Paket</button>
        </form>
        @if(!$exam->hasGeneratedPackage())
            <span class="help">Paket otomatis dibuat saat publish. Bisa juga dibuat manual dari tombol ini.</span>
        @else
            <span class="help">Paket tersedia. Generate ulang hanya jika soal berubah dan belum ada yang mulai mengerjakan.</span>
        @endif
    </div>

    {{-- Detail teknis bisa disembunyikan karena membingungkan --}}
    <details style="margin-top:.75rem">
        <summary class="muted small" style="cursor:pointer">Detail teknis paket</summary>
        <div class="three" style="margin-top:.5rem">
            <div><div class="muted small">Versi</div><b>v{{ $exam->package_version }}</b></div>
            <div><div class="muted small">Dibuat</div><b>{{ optional($exam->package_generated_at)->format('d M Y H:i') ?: '-' }}</b></div>
            <div><div class="muted small">Ukuran</div><b>{{ $exam->package_size_bytes ? number_format($exam->package_size_bytes / 1024, 1) . ' KB' : '-' }}</b></div>
        </div>
    </details>
</div>


{{-- ===== PENGATURAN KONFIGURASI ===== --}}
<div class="card mb">
    <div class="between">
        <div>
            <h2 class="mb0">Konfigurasi Ujian</h2>
            <p class="muted small mb0">
                @if($exam->hasStartedWork())
                    Terkunci karena sudah ada siswa yang login/mulai. Hanya nama & instruksi yang bisa diubah.
                @else
                    Ubah judul, jadwal, durasi, kelas, dan aturan soal.
                @endif
            </p>
        </div>
        <div class="row">
            <a class="btn soft" href="{{ route('exams.edit', $exam) }}">Edit Konfigurasi</a>
            @if(!in_array($exam->status, ['published','closed','archived'], true))
                <form method="POST" action="{{ route('exams.regenerateCode', $exam) }}" onsubmit="return confirm('Generate ulang kode ujian? Kode lama tidak bisa dipakai lagi.')">
                    @csrf
                    <button class="btn">⟳ Buat Ulang Kode</button>
                </form>
            @endif
        </div>
    </div>
</div>


{{-- ===== ZONA BERBAHAYA ===== --}}
<div class="card mb" style="border:1px solid var(--line)">
    <details>
        <summary style="cursor:pointer;font-weight:800;color:var(--muted)">
            ⚠️ Aksi Lanjutan (tutup / arsipkan / hapus / kembalikan ke draft)
        </summary>
        <div class="row" style="margin-top:1rem;flex-wrap:wrap">
            @if(in_array($exam->status, ['published','closed'], true))
                <form method="POST" action="{{ route('exams.unpublish', $exam) }}"
                      onsubmit="return confirm('Kembalikan ke draft?\n\nHanya bisa jika belum ada siswa yang mulai mengerjakan.')">
                    @csrf
                    <button class="btn warning">↩ Kembalikan ke Draft</button>
                </form>
            @endif

            @if(!in_array($exam->status, ['closed','archived'], true))
                <form method="POST" action="{{ route('exams.close', $exam) }}"
                      onsubmit="return confirm('Tutup ujian?\n\nSiswa tidak bisa download atau submit lagi setelah ini.')">
                    @csrf
                    <button class="btn danger">Tutup Ujian</button>
                </form>
            @endif

            @if($exam->status !== 'archived')
                <form method="POST" action="{{ route('exams.archive', $exam) }}"
                      onsubmit="return confirm('Arsipkan ujian?\n\nUjian disembunyikan dari daftar aktif. Data hasil tetap tersimpan.')">
                    @csrf
                    <button class="btn">Arsipkan</button>
                </form>
            @endif

            @if(!$exam->hasStartedWork())
                <form method="POST" action="{{ route('exams.destroy', $exam) }}"
                      onsubmit="return confirm('HAPUS PERMANEN ujian ini?\n\nSemua soal, peserta, dan data ujian akan dihapus selamanya.\nTindakan ini tidak bisa dibatalkan.\n\nKetik OK untuk melanjutkan.')">
                    @csrf @method('DELETE')
                    <button class="btn danger" style="background:#b42318;color:#fff">🗑 Hapus Permanen</button>
                </form>
            @else
                <span class="help" style="align-self:center">
                    Ujian tidak bisa dihapus karena sudah ada aktivitas siswa. Gunakan Arsipkan.
                </span>
            @endif
        </div>
        <p class="help" style="margin-top:.75rem">
            <b>Tutup</b>: siswa tidak bisa submit baru. &nbsp;
            <b>Arsipkan</b>: sembunyikan dari daftar, data tetap ada. &nbsp;
            <b>Kembalikan ke Draft</b>: hanya jika belum ada aktivitas. &nbsp;
            <b>Hapus Permanen</b>: hanya tersedia selama belum ada siswa yang login/download.
        </p>
    </details>
</div>

@endsection
