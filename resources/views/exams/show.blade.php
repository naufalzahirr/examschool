@extends('layouts.app', ['title' => $exam->title])

@section('content')

@php
    $isReady = collect($readiness)->every(fn ($item) => $item['ok']);
    $downloadOpensAt = $queueStats['download_opens_at'];
    $downloadIsOpen = $queueStats['download_window_open'];
    $now = now();
    $examStarted = $exam->starts_at && $now->greaterThanOrEqualTo($exam->starts_at);
    $examEnded = $exam->ends_at && $now->greaterThan($exam->ends_at);
@endphp

<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0;">{{ $exam->title }}</h1>
            <p class="muted" style="margin:.4rem 0 .6rem">
                {{ $exam->subject ?: 'Tanpa mapel' }} | {{ $exam->grade_level ?: 'Tanpa jenjang' }} |
                Kode: <b>{{ $exam->access_code }}</b>
            </p>
            <div class="row">
                <span class="badge {{ $exam->status }}">{{ $exam->operationalStatus() }}</span>
                @if($exam->starts_at)
                    <span class="pill">{{ $exam->starts_at->format('d M Y H:i') }} - {{ optional($exam->ends_at)->format('H:i') ?: 'fleksibel' }}</span>
                @else
                    <span class="pill">Jadwal fleksibel</span>
                @endif
                @if($exam->published_at)
                    <span class="pill muted small">Publish: {{ $exam->published_at->format('d M Y H:i') }}</span>
                @endif
            </div>
        </div>

        <div class="row" style="align-items:flex-start;flex-direction:column;gap:.5rem">
            @if(!in_array($exam->status, ['published','closed','archived'], true))
                @if($isReady)
                    <form method="POST" action="{{ route('exams.publish', $exam) }}" style="width:100%">
                        @csrf
                        <button class="btn green" style="width:100%;justify-content:center">Publish Ujian</button>
                    </form>
                @else
                    <span class="btn" style="opacity:.5;cursor:not-allowed;width:100%;justify-content:center">
                        Belum Siap Publish
                    </span>
                    <span class="muted small" style="text-align:center">Selesaikan checklist dulu</span>
                @endif
            @elseif($exam->status === 'published')
                <span class="badge published" style="padding:.6rem 1rem">Ujian Aktif</span>
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

<div class="two mb">
    <div class="card">
        <div class="between mb">
            <div>
                <h2 class="mb0">Checklist Kesiapan</h2>
                <p class="muted small mb0">Semua harus hijau sebelum publish.</p>
            </div>
            <span class="badge {{ $isReady ? 'published' : 'warning' }}">
                {{ $isReady ? 'Siap Publish' : 'Belum Siap' }}
            </span>
        </div>
        <div class="check-list">
            @foreach($readiness as $item)
                <div class="check-pill" style="justify-content:space-between">
                    <span>{{ $item['ok'] ? 'OK' : 'Perlu dicek' }} - {{ $item['label'] }}</span>
                    <span class="muted small">{{ $item['note'] }}</span>
                </div>
            @endforeach
        </div>

        @if(!in_array($exam->status, ['published','closed','archived'], true))
            <div style="margin-top:1rem;padding-top:.75rem;border-top:1px solid var(--line)">
                @php $unfinished = collect($readiness)->filter(fn ($item) => ! $item['ok']); @endphp
                @if($unfinished->isEmpty())
                    <p class="muted small mb0">Semua checklist terpenuhi. Klik <b>Publish Ujian</b> di atas.</p>
                @else
                    <p class="muted small mb0">{{ $unfinished->count() }} item perlu diselesaikan.</p>
                    <div class="row" style="margin-top:.5rem">
                        @if($unfinished->contains(fn ($i) => str_contains(strtolower($i['label']), 'soal')))
                            <a href="{{ route('exams.question-bank.select', $exam) }}" class="btn soft">Ambil Soal dari Bank</a>
                        @endif
                        @if($unfinished->contains(fn ($i) => str_contains(strtolower($i['label']), 'kelas') || str_contains(strtolower($i['label']), 'peserta')))
                            <a href="{{ route('exams.edit', $exam) }}" class="btn soft">Atur Kelas Peserta</a>
                        @endif
                        @if($unfinished->contains(fn ($i) => str_contains(strtolower($i['label']), 'jadwal') || str_contains(strtolower($i['label']), 'durasi')))
                            <a href="{{ route('exams.edit', $exam) }}" class="btn soft">Atur Jadwal &amp; Durasi</a>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="card">
        <h2>Navigasi Cepat</h2>
        <div class="check-list">
            @if($exam->canEditQuestions())
                <a class="check-pill" href="{{ route('exams.question-bank.select', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                    <span>Ambil Soal dari Bank Soal</span>
                    <span class="badge">{{ $exam->questions_count }} soal</span>
                </a>
                <a class="check-pill" href="{{ route('exams.builder', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                    <span>Review / Edit Soal</span>
                    <span class="muted small">draft mode</span>
                </a>
            @else
                <a class="check-pill" href="{{ route('exams.builder', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                    <span>Lihat Soal</span>
                    <span class="badge">{{ $exam->questions_count }} soal</span>
                </a>
            @endif
            <a class="check-pill" href="{{ route('exams.participants', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                <span>Kelola Peserta</span>
                <span class="badge">{{ $exam->participants_count }} peserta</span>
            </a>
            <a class="check-pill" href="{{ route('exams.monitor', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                <span>Monitor Pelaksanaan</span>
                @if($exam->isOpenNow())
                    <span class="badge published">sedang dibuka</span>
                @else
                    <span class="muted small">real-time</span>
                @endif
            </a>
            <a class="check-pill" href="{{ route('exams.results', $exam) }}" style="text-decoration:none;color:inherit;justify-content:space-between">
                <span>Lihat Hasil</span>
                <span class="muted small">nilai siswa</span>
            </a>
        </div>
    </div>
</div>

<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Timeline Ujian</h2>
            <p class="muted small mb0">Urutan yang dialami siswa dari download soal sampai selesai.</p>
        </div>
        @if($exam->starts_at)
            <a class="btn soft" href="{{ route('exams.edit', $exam) }}">Ubah Jadwal</a>
        @endif
    </div>

    <div style="display:grid;gap:.65rem">
        <div class="check-pill" style="justify-content:space-between;background:{{ $downloadIsOpen ? 'var(--success-soft)' : '#f6f7f9' }}">
            <span>
                <b>Siswa download soal</b><br>
                <span class="muted small">Bisa sebelum mulai dan tetap bisa saat ujian berjalan selama belum melewati jadwal selesai.</span>
            </span>
            @if($downloadIsOpen)
                <span class="badge published">sedang dibuka</span>
            @elseif($downloadOpensAt)
                <span class="badge">{{ $downloadOpensAt }}</span>
            @else
                <span class="badge warning">setelah publish</span>
            @endif
        </div>

        <div class="check-pill" style="justify-content:space-between;background:{{ $examStarted && ! $examEnded ? 'var(--success-soft)' : '#f6f7f9' }}">
            <span>
                <b>Ujian dimulai</b><br>
                <span class="muted small">Siswa membuka soal di aplikasi sesuai jadwal mulai.</span>
            </span>
            @if($exam->starts_at)
                <span class="badge {{ $examStarted ? 'published' : '' }}">{{ $exam->starts_at->format('d M Y H:i') }}</span>
            @else
                <span class="badge warning">jadwal fleksibel</span>
            @endif
        </div>

        <div class="check-pill" style="justify-content:space-between">
            <span>
                <b>Durasi mengerjakan</b><br>
                <span class="muted small">Timer berjalan sesuai durasi ujian yang ditetapkan guru.</span>
            </span>
            <span class="badge">{{ $exam->duration_minutes }} menit</span>
        </div>

        <div class="check-pill" style="justify-content:space-between;background:{{ $examEnded ? '#f0fdf4' : '#f6f7f9' }}">
            <span>
                <b>Ujian selesai</b><br>
                <span class="muted small">Setelah waktu selesai, siswa tidak bisa membuka sesi baru.</span>
            </span>
            @if($exam->ends_at)
                <span class="badge {{ $examEnded ? 'closed' : '' }}">{{ $exam->ends_at->format('d M Y H:i') }}</span>
            @else
                <span class="badge warning">tidak ada batas</span>
            @endif
        </div>
    </div>

    @if(!$exam->starts_at || !$exam->ends_at)
        <div class="alert warning" style="margin-top:.75rem;margin-bottom:0">
            Jadwal belum lengkap. <a href="{{ route('exams.edit', $exam) }}">Atur jadwal</a>
        </div>
    @endif
</div>

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
            <span class="muted">Belum ada kelas dipilih. <a href="{{ route('exams.edit', $exam) }}">Pilih kelas</a></span>
        @endforelse
    </div>
</div>

<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Soal untuk Aplikasi Siswa</h2>
            <p class="muted small mb0">Status kesiapan soal dan download siswa.</p>
        </div>
        <span class="badge {{ $exam->hasGeneratedPackage() ? 'published' : 'warning' }}">
            {{ $exam->hasGeneratedPackage() ? 'Soal Siap' : 'Belum Siap' }}
        </span>
    </div>

    <div class="grid mb">
        <div class="mini-card">
            <div class="muted small">Slot Download Aktif</div>
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
            <div class="muted small">Soal Dibuka</div>
            <div class="stat">{{ $queueStats['unlocked'] }}</div>
        </div>
    </div>

    <div class="alert info" style="margin-bottom:.75rem">
        @if($queueStats['download_window_open'])
            Download sedang dibuka. Siswa tetap bisa download selama ujian belum melewati jadwal selesai.
        @else
            Download dibuka mulai {{ $queueStats['download_opens_at'] ?: 'setelah ujian dipublish' }}.
        @endif
    </div>

    <div class="row">
        <form method="POST" action="{{ route('exams.package.regenerate', $exam) }}" onsubmit="return confirm('Siapkan ulang soal untuk aplikasi siswa? Jangan lakukan saat siswa sedang mengerjakan ujian.')">
            @csrf
            <button class="btn soft">Siapkan Ulang Soal</button>
        </form>
        @if(!$exam->hasGeneratedPackage())
            <span class="help">Soal otomatis disiapkan saat publish. Tombol ini hanya dipakai jika perlu menyiapkan ulang sebelum ada aktivitas siswa.</span>
        @else
            <span class="help">Soal sudah siap. Siapkan ulang hanya jika soal berubah dan belum ada siswa yang mulai mengerjakan.</span>
        @endif
    </div>
</div>

<div class="card mb">
    <div class="between">
        <div>
            <h2 class="mb0">Konfigurasi Ujian</h2>
            <p class="muted small mb0">
                @if($exam->hasStartedWork())
                    Terkunci karena sudah ada aktivitas siswa. Hanya buat ujian baru jika butuh perubahan besar.
                @else
                    Ubah judul, jadwal, durasi, kelas, dan aturan soal.
                @endif
            </p>
        </div>
        <div class="row">
            <a class="btn soft" href="{{ route('exams.edit', $exam) }}">Edit Konfigurasi</a>
            @if(!in_array($exam->status, ['published','closed','archived'], true))
                <form method="POST" action="{{ route('exams.regenerateCode', $exam) }}" onsubmit="return confirm('Buat ulang kode ujian? Kode lama tidak bisa dipakai lagi.')">
                    @csrf
                    <button class="btn">Buat Ulang Kode</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="card mb" style="border:1px solid var(--line)">
    <details>
        <summary style="cursor:pointer;font-weight:800;color:var(--muted)">
            Aksi Lanjutan
        </summary>
        <div class="row" style="margin-top:1rem;flex-wrap:wrap">
            @if(in_array($exam->status, ['published','closed'], true))
                <form method="POST" action="{{ route('exams.unpublish', $exam) }}"
                      onsubmit="return confirm('Kembalikan ke draft?\n\nHanya bisa jika belum ada siswa yang mulai mengerjakan.')">
                    @csrf
                    <button class="btn warning">Kembalikan ke Draft</button>
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
                    <button class="btn danger" style="background:#b42318;color:#fff">Hapus Permanen</button>
                </form>
            @else
                <span class="help" style="align-self:center">
                    Ujian tidak bisa dihapus karena sudah ada aktivitas siswa. Gunakan Arsipkan.
                </span>
            @endif
        </div>
        <p class="help" style="margin-top:.75rem">
            <b>Tutup</b>: siswa tidak bisa submit baru.
            <b>Arsipkan</b>: sembunyikan dari daftar, data tetap ada.
            <b>Kembalikan ke Draft</b>: hanya jika belum ada aktivitas.
            <b>Hapus Permanen</b>: hanya tersedia selama belum ada siswa yang login/download.
        </p>
    </details>
</div>

@endsection
