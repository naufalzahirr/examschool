@extends('layouts.app', ['title' => $exam->title])

@section('content')
<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0;">{{ $exam->title }}</h1>
            <p class="muted">Kode ujian: <b>{{ $exam->access_code }}</b> · Versi paket: {{ $exam->package_version }} · Paket: <b>{{ $exam->packageStatusLabel() }}</b></p>
            <div class="row">
                <span class="badge {{ $exam->status }}">{{ $exam->operationalStatus() }}</span>
                @if($exam->published_at)<span class="pill">Publish: {{ $exam->published_at->format('d M Y H:i') }}</span>@endif
                @if($exam->closed_at)<span class="pill">Tutup: {{ $exam->closed_at->format('d M Y H:i') }}</span>@endif
            </div>
        </div>
        <div class="row">
            <a class="btn" href="{{ route('exams.edit', $exam) }}">Edit Konfigurasi</a>
            @if($exam->canEditQuestions())
                <a class="btn primary" href="{{ route('exams.question-bank.select', $exam) }}">Ambil dari Bank Soal</a>
            @endif
            <a class="btn primary" href="{{ route('exams.builder', $exam) }}">Builder Soal</a>
            <a class="btn soft" href="{{ route('exams.monitor', $exam) }}">Monitor</a>
        </div>
    </div>
</div>

<div class="grid mb">
    <div class="card"><div class="muted">Soal</div><div class="stat">{{ $exam->questions_count }}</div></div>
    <div class="card"><div class="muted">Kelas</div><div class="stat">{{ $exam->classrooms_count }}</div></div>
    <div class="card"><div class="muted">Peserta</div><div class="stat">{{ $exam->participants_count }}</div></div>
    <div class="card"><div class="muted">Durasi</div><div class="stat">{{ $exam->duration_minutes }}</div><div class="muted">menit</div></div>
    <div class="card"><div class="muted">Download Paket</div><div class="stat">{{ $exam->package_downloads_count ?? 0 }}</div><div class="muted">kali</div></div>
</div>

<div class="two mb">
    <div class="card">
        <div class="between mb">
            <div>
                <h2 class="mb0">Checklist Kesiapan</h2>
                <p class="muted small mb0">Dipakai sebelum publish agar pelaksanaan ujian tidak bermasalah.</p>
            </div>
            <span class="badge {{ collect($readiness)->every(fn($item) => $item['ok']) ? 'active' : 'warning' }}">
                {{ collect($readiness)->every(fn($item) => $item['ok']) ? 'siap publish' : 'belum siap' }}
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
    </div>

    <div class="card">
        <h2>Aturan Teknis</h2>
        <div class="check-list">
            <div class="check-pill">🔒 Soal dikunci setelah publish</div>
            <div class="check-pill">📥 Siswa download paket soal di awal</div>
            <div class="check-pill">📴 Pengerjaan bisa offline di aplikasi</div>
            <div class="check-pill">📤 Jawaban dikirim saat online kembali</div>
            <div class="check-pill">📱 Akun siswa terkunci ke perangkat pertama</div>
        </div>
    </div>
</div>


<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Mode Ujian Offline & Alarm Pelanggaran</h2>
            <p class="muted small mb0">Aturan ini ikut masuk ke paket soal terenkripsi. Aplikasi Android memakainya untuk mengunci layar ujian dan mencatat pelanggaran saat siswa keluar paksa atau internet aktif.</p>
        </div>
        <span class="badge warning">{{ $exam->lockModeLabel() }}</span>
    </div>
    <div class="grid mb">
        <div><div class="muted small">Mode Kunci</div><b>{{ $exam->lockModeLabel() }}</b></div>
        <div><div class="muted small">Aturan Keluar</div><b>{{ $exam->exitPolicyLabel() }}</b></div>
        <div><div class="muted small">Buka Kembali</div><b>Otomatis setelah kondisi aman</b></div>
        <div><div class="muted small">Event Integritas</div><b>Dikirim saat sync/submit</b></div>
    </div>
    <div class="alert info">
        Jika siswa keluar paksa, membuka internet, atau aplikasi kehilangan fokus, mobile akan mengunci layar ujian, membunyikan alarm/notifikasi keras, mencatat event integritas, lalu event dikirim saat perangkat kembali online/saat submit.
    </div>
</div>

<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Kelas Peserta</h2>
            <p class="muted small mb0">Siswa aktif dari kelas ini otomatis dijadikan peserta ujian.</p>
        </div>
        <a class="btn soft" href="{{ route('exams.edit', $exam) }}">Ubah Kelas</a>
    </div>
    <div class="row">
        @forelse($exam->classrooms as $classroom)
            <span class="badge">{{ $classroom->nama_kelas }}</span>
        @empty
            <span class="muted">Belum ada kelas dipilih.</span>
        @endforelse
    </div>
</div>


<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Paket Soal Statis</h2>
            <p class="muted small mb0">Paket dibuat saat publish sebagai JSON terenkripsi. Siswa boleh download sebelum ujian lewat antrean slot, lalu saat jam mulai aplikasi meminta unlock key kecil.</p>
        </div>
        <span class="badge {{ $exam->hasGeneratedPackage() ? 'active' : 'warning' }}">{{ $exam->packageStatusLabel() }}</span>
    </div>
    <div class="grid mb">
        <div><div class="muted small">Checksum File</div><code class="small">{{ $exam->package_checksum ?: '-' }}</code></div>
        <div><div class="muted small">Plain Checksum</div><code class="small">{{ $exam->package_plain_checksum ?: '-' }}</code></div>
        <div><div class="muted small">Generated</div><b>{{ optional($exam->package_generated_at)->format('d M Y H:i:s') ?: '-' }}</b></div>
        <div><div class="muted small">Ukuran</div><b>{{ $exam->package_size_bytes ? number_format($exam->package_size_bytes / 1024, 1) . ' KB' : '-' }}</b></div>
    </div>

    <div class="grid mb">
        <div class="mini-card"><div class="muted small">Slot Aktif</div><div class="stat">{{ $queueStats['active'] }} / {{ $queueStats['limit'] }}</div></div>
        <div class="mini-card"><div class="muted small">Menunggu Antrean</div><div class="stat">{{ $queueStats['waiting'] }}</div></div>
        <div class="mini-card"><div class="muted small">Sudah Download</div><div class="stat">{{ $queueStats['downloaded'] }}</div></div>
        <div class="mini-card"><div class="muted small">Sudah Unlock</div><div class="stat">{{ $queueStats['unlocked'] }}</div></div>
    </div>
    <div class="alert info">
        <b>Jendela download:</b>
        @if($queueStats['download_window_open'])
            sedang dibuka.
        @else
            dibuka mulai {{ $queueStats['download_opens_at'] ?: 'setelah ujian dipublish' }}.
        @endif
        File publik hanya berisi paket terenkripsi; isi soal baru terbuka setelah mobile meminta unlock key saat jadwal ujian dimulai.
    </div>

    <div class="row">
        <form method="POST" action="{{ route('exams.package.regenerate', $exam) }}" onsubmit="return confirm('Generate ulang paket soal? Jangan lakukan saat siswa sudah mulai ujian kecuali paket belum pernah dibuat.')">
            @csrf
            <button class="btn soft">Generate Paket Soal</button>
        </form>
        @if($exam->hasGeneratedPackage())
            <span class="help">Mobile boleh download sebelum jam mulai. Start mengikuti jadwal, sedangkan submit final boleh sampai batas grace upload jika HP sempat offline.</span>
        @else
            <span class="help">Paket belum ada. Paket otomatis dibuat saat publish, atau bisa dibuat manual dari tombol ini.</span>
        @endif
    </div>
</div>

<div class="card mb">
    <h2>Aksi Cepat</h2>
    <div class="row">
        <a class="btn soft" href="{{ route('exams.participants', $exam) }}">Kelola Peserta</a>
        <a class="btn soft" href="{{ route('exams.monitor', $exam) }}">Monitor Pelaksanaan</a>
        <a class="btn soft" href="{{ route('exams.results', $exam) }}">Lihat Hasil</a>
        @if(!in_array($exam->status, ['published','closed','archived'], true))
            <form method="POST" action="{{ route('exams.regenerateCode', $exam) }}" onsubmit="return confirm('Generate ulang kode ujian? Kode lama tidak bisa dipakai lagi.')">@csrf <button class="btn warning">Generate Ulang Kode</button></form>
        @endif
        @if(!in_array($exam->status, ['published','closed','archived'], true))
            <form method="POST" action="{{ route('exams.publish', $exam) }}">@csrf <button class="btn green">Publish</button></form>
        @else
            <form method="POST" action="{{ route('exams.unpublish', $exam) }}" onsubmit="return confirm('Kembalikan ujian ke draft? Hanya bisa jika belum ada siswa yang login/mulai ujian.')">@csrf <button class="btn warning">Kembalikan ke Draft</button></form>
        @endif
        @if(!in_array($exam->status, ['closed','archived'], true))
            <form method="POST" action="{{ route('exams.close', $exam) }}" onsubmit="return confirm('Tutup ujian ini?')">@csrf <button class="btn danger">Tutup</button></form>
        @endif
        @if($exam->status !== 'archived')
            <form method="POST" action="{{ route('exams.archive', $exam) }}" onsubmit="return confirm('Arsipkan ujian ini?')">@csrf <button class="btn">Arsipkan</button></form>
        @endif
    </div>
    @if(!$exam->canEditQuestions())
        <p class="help">Catatan: soal terkunci karena ujian sudah publish atau sudah ada aktivitas siswa.</p>
    @endif
</div>

<div class="card">
    <h2>Endpoint Mobile</h2>
    <p class="muted">Aplikasi Android login memakai <b>kode ujian + NIS + password</b>. Setelah login, API mengembalikan Bearer token untuk download paket soal dan submit jawaban.</p>
    <pre class="code">POST /api/mobile/login
Body: access_code={{ $exam->access_code }}, nis=4728, password=******, device_id=ANDROID_DEVICE_ID

POST /api/mobile/exam-package/queue
Body: access_code={{ $exam->access_code }}, device_id=ANDROID_DEVICE_ID
Response granted: download_url, queue_token, active, limit, position

GET /api/mobile/exam-package?access_code={{ $exam->access_code }}&queue_token=QUEUE_TOKEN
Header: Authorization: Bearer TOKEN_DARI_LOGIN
Header opsional: If-None-Match: {{ $exam->package_checksum ?: 'CHECKSUM_LOKAL' }}

POST /api/mobile/exam-package/download-complete
POST /api/mobile/exam-package/unlock
POST /api/mobile/attempt/start
POST /api/mobile/attempt/sync   # opsional; progress utama tetap lokal HP
POST /api/mobile/attempt/submit # final upload + idempotency_key + exit_events</pre>
</div>
@endsection
