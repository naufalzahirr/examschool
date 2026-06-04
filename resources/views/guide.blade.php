@extends('layouts.app', ['title' => 'Panduan Sistem Ujian'])

@push('head')
<style>
/* ── Panduan-specific styles ── */
.guide-hero{background:linear-gradient(135deg,#4357F2 0%,#6b7ff7 50%,#12B886 100%);border-radius:var(--radius);padding:2.5rem 2rem;color:#fff;margin-bottom:1.5rem}
.guide-hero h1{color:#fff;margin:0 0 .5rem;font-size:34px}
.guide-hero p{margin:0;opacity:.88;font-size:16px}

.concept-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-bottom:1.5rem}
.concept-card{border-radius:var(--radius);padding:1.4rem;position:relative;overflow:hidden}
.concept-card.online-before{background:linear-gradient(135deg,#eef0ff,#dde1ff)}
.concept-card.offline{background:linear-gradient(135deg,#fff3e0,#ffe0b2)}
.concept-card.online-after{background:linear-gradient(135deg,#e8fadf,#c8f5c0)}
.concept-card h3{margin:0 0 .5rem;font-size:15px}
.concept-card p{margin:0;font-size:13px;line-height:1.5;color:var(--muted)}
.concept-icon{font-size:36px;margin-bottom:.75rem;display:block}
.concept-phase{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;opacity:.7;margin-bottom:.3rem}

.flow-grid{display:grid;gap:.85rem}
.flow-step{display:grid;grid-template-columns:52px 1fr auto;gap:1rem;align-items:start;background:var(--card);border-radius:var(--radius);padding:1.1rem 1.25rem;box-shadow:var(--shadow)}
.flow-num{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;font-weight:900;font-size:18px;color:#fff;flex-shrink:0}
.flow-title{font-weight:900;font-size:15px;margin:0 0 .3rem;color:var(--heading)}
.flow-desc{font-size:13px;color:var(--muted);margin:0;line-height:1.5}
.flow-note{font-size:11px;background:var(--info-soft);color:#0787a1;padding:.3rem .65rem;border-radius:999px;white-space:nowrap;align-self:center;font-weight:800}
.flow-note.warn{background:var(--warning-soft);color:#946200}
.flow-note.ok{background:var(--success-soft);color:#2b8a0e}

.mobile-flow{display:grid;grid-template-columns:repeat(5,1fr);gap:0;position:relative}
.mobile-flow::before{content:'';position:absolute;top:28px;left:calc(10%);width:80%;height:2px;background:linear-gradient(90deg,var(--primary),#12B886);z-index:0}
.mobile-step{display:flex;flex-direction:column;align-items:center;text-align:center;position:relative;z-index:1}
.mobile-icon{width:56px;height:56px;border-radius:50%;display:grid;place-items:center;font-size:24px;margin-bottom:.6rem;border:3px solid #fff;box-shadow:0 0 0 2px var(--primary)}
.mobile-label{font-size:12px;font-weight:800;color:var(--heading);margin-bottom:.25rem}
.mobile-sub{font-size:11px;color:var(--muted);line-height:1.4}

.status-pill{display:inline-flex;align-items:center;gap:.4rem;border-radius:999px;padding:.3rem .75rem;font-size:12px;font-weight:800;white-space:nowrap}
.status-flow-row{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem}
.status-arrow{color:var(--muted);font-size:14px}

.faq-item{border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;margin-bottom:.65rem}
.faq-q{padding:.85rem 1.1rem;background:#fafbff;font-weight:800;cursor:pointer;display:flex;justify-content:space-between;align-items:center;color:var(--heading)}
.faq-q:hover{background:#f0f2ff}
.faq-a{padding:0 1.1rem;max-height:0;overflow:hidden;transition:max-height .25s ease,padding .25s ease;font-size:13px;color:var(--muted);line-height:1.6}
.faq-item.open .faq-a{max-height:300px;padding:.85rem 1.1rem}
.faq-item.open .faq-chevron{transform:rotate(180deg)}
.faq-chevron{transition:transform .2s;font-size:14px}

.ref-table{width:100%;border-collapse:collapse}
.ref-table th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#566a7f;background:#f6f7f9;padding:.75rem 1rem;text-align:left;white-space:nowrap}
.ref-table td{padding:.75rem 1rem;border-top:1px solid var(--line);font-size:13px;vertical-align:top}
.ref-table tr:hover td{background:#fafafa}

.tip-box{border-left:4px solid var(--primary);background:var(--primary-soft);padding:.85rem 1.1rem;border-radius:0 var(--radius) var(--radius) 0;margin:.75rem 0;font-size:13px}
.tip-box.warn{border-left-color:var(--warning);background:var(--warning-soft)}
.tip-box.ok{border-left-color:var(--success);background:var(--success-soft)}

@media(max-width:1100px){
    .concept-grid{grid-template-columns:1fr}
    .mobile-flow{grid-template-columns:1fr 1fr;gap:1rem}
    .mobile-flow::before{display:none}
    .flow-step{grid-template-columns:42px 1fr}
    .flow-note{grid-column:2}
}
</style>
@endpush

@section('content')

{{-- ═══════════════════════════════════════════════════ HERO ═══════════════════════════════════════════════════ --}}
<div class="guide-hero">
    <div class="between" style="align-items:flex-start">
        <div>
            <h1>📖 Panduan Sistem Ujian Sekolah</h1>
            <p>Konsep, prosedur, dan referensi cepat untuk guru dan administrator.</p>
        </div>
        <a class="btn" href="{{ route('dashboard') }}" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35)">
            ← Kembali ke Dashboard
        </a>
    </div>
</div>

{{-- ═══════════════════════════════════════════════ KONSEP UTAMA ═══════════════════════════════════════════════ --}}
<div class="card mb">
    <h2 style="margin-bottom:.3rem">Konsep: Ujian Semi-Online</h2>
    <p class="muted small" style="margin-bottom:1.25rem">
        Sistem ini memisahkan fase online dan offline. Soal <b>tidak pernah dikirim mentah</b> ke HP siswa —
        selalu terenkripsi. Internet hanya dibutuhkan saat download dan upload jawaban, bukan saat mengerjakan.
    </p>
    <div class="concept-grid">
        <div class="concept-card online-before">
            <span class="concept-phase">Fase 1 · Online</span>
            <span class="concept-icon">📦</span>
            <h3>Download Paket Terenkripsi</h3>
            <p>
                Siswa mengunduh file paket soal yang sudah dienkripsi ke HP, mulai
                <b>{{ $settings['download_open_hours'] }} jam sebelum</b> ujian dimulai.
                Isi soal tidak bisa dibaca karena terkunci dengan kunci yang belum diberikan.
            </p>
        </div>
        <div class="concept-card offline">
            <span class="concept-phase">Fase 2 · Offline Wajib</span>
            <span class="concept-icon">✈️</span>
            <h3>Kerjakan Soal Tanpa Internet</h3>
            <p>
                Saat jam ujian tiba, server memberikan kunci kecil (unlock key).
                Siswa wajib aktifkan <b>mode pesawat</b> sebelum soal bisa dibuka.
                Seluruh pengerjaan berlangsung offline, jawaban tersimpan di HP.
            </p>
        </div>
        <div class="concept-card online-after">
            <span class="concept-phase">Fase 3 · Online</span>
            <span class="concept-icon">☁️</span>
            <h3>Upload Jawaban ke Server</h3>
            <p>
                Setelah ujian selesai, siswa matikan mode pesawat dan nyalakan internet.
                Jawaban yang sudah dikunci lokal dikirim ke server.
                Nilai muncul seketika setelah server menerima jawaban.
            </p>
        </div>
    </div>
    <div class="tip-box ok">
        <b>Keunggulan:</b> gangguan jaringan tidak mengganggu ujian. Soal sudah di HP sebelum ujian dimulai,
        jawaban tersimpan aman meski koneksi putus saat submit. Siswa tinggal coba kirim ulang saat internet tersedia.
    </div>
</div>

{{-- ═══════════════════════════════════════════════ ALUR GURU ═══════════════════════════════════════════════ --}}
<div class="card mb">
    <h2 style="margin-bottom:.3rem">Alur Kerja Guru — 7 Langkah</h2>
    <p class="muted small" style="margin-bottom:1.25rem">Dari persiapan data sampai melihat hasil ujian.</p>

    <div class="flow-grid">

        {{-- 1 --}}
        <div class="flow-step">
            <div class="flow-num" style="background:#6366f1">1</div>
            <div>
                <p class="flow-title">Siapkan Data Master (Admin)</p>
                <p class="flow-desc">
                    Sinkron data kelas, siswa, dan guru dari SILAP agar semua terdaftar dengan NIS yang benar.
                    Buat akun guru dan pengawas dari menu <b>Akun</b>.
                </p>
            </div>
            @if(auth()->user()->isAdmin())
                <a class="btn soft" href="{{ route('silap.index') }}">Sinkron SILAP</a>
            @else
                <span class="flow-note">Admin</span>
            @endif
        </div>

        {{-- 2 --}}
        <div class="flow-step">
            <div class="flow-num" style="background:#8b5cf6">2</div>
            <div>
                <p class="flow-title">Buat Soal di Bank Soal</p>
                <p class="flow-desc">
                    Bank Soal adalah repositori soal yang bisa dipakai ulang di banyak ujian.
                    Buat soal per paket (mapel + jenjang + topik). Tersedia 5 jenis soal:
                    pilihan ganda, pilihan ganda kompleks, benar/salah, menjodohkan, dan jawaban singkat.
                    Soal bisa dibagikan ke guru lain atau dibuat pribadi.
                </p>
            </div>
            @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
                <a class="btn soft" href="{{ route('question-bank.create') }}">+ Buat Soal</a>
            @endif
        </div>

        {{-- 3 --}}
        <div class="flow-step">
            <div class="flow-num" style="background:#4357F2">3</div>
            <div>
                <p class="flow-title">Buat & Konfigurasi Ujian</p>
                <p class="flow-desc">
                    Isi judul, mata pelajaran, <b>jadwal mulai & selesai</b>, durasi, dan pilih kelas peserta.
                    Siswa aktif di kelas terpilih otomatis menjadi peserta.
                    Set juga mode keamanan (wajib mode pesawat / kiosk / standar) per ujian.
                </p>
            </div>
            @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
                <a class="btn soft" href="{{ route('exams.create') }}">+ Buat Ujian</a>
            @endif
        </div>

        {{-- 4 --}}
        <div class="flow-step">
            <div class="flow-num" style="background:#0ea5e9">4</div>
            <div>
                <p class="flow-title">Pilih Soal dari Bank Soal</p>
                <p class="flow-desc">
                    Setelah ujian tersimpan, pilih satu atau beberapa paket soal dari Bank Soal.
                    Semua soal aktif dalam paket disalin ke ujian. Soal bisa direview/diedit dari menu
                    <b>Lihat Soal</b> sebelum publish.
                </p>
            </div>
            <span class="flow-note">Setelah simpan ujian</span>
        </div>

        {{-- 5 --}}
        <div class="flow-step">
            <div class="flow-num" style="background:#12B886">5</div>
            <div>
                <p class="flow-title">Publish Ujian</p>
                <p class="flow-desc">
                    Pastikan <b>semua checklist hijau</b> di halaman detail ujian: soal tersedia, kelas dipilih,
                    jadwal diisi. Klik <b>Publish Ujian</b>.
                    Server otomatis membuat paket soal terenkripsi. Kode ujian siap dibagikan ke siswa.
                </p>
            </div>
            <span class="flow-note ok">Paket otomatis dibuat</span>
        </div>

        {{-- 6 --}}
        <div class="flow-step">
            <div class="flow-num" style="background:#f59e0b">6</div>
            <div>
                <p class="flow-title">Monitor Pelaksanaan</p>
                <p class="flow-desc">
                    Pantau progress download, antrean, dan submit dari halaman <b>Monitor Pelaksanaan</b>.
                    Tersedia filter per kelas dan status. Jika siswa ganti HP, klik <b>Ganti HP</b>.
                    Auto-refresh setiap 30 detik.
                </p>
            </div>
            <span class="flow-note warn">Saat ujian berlangsung</span>
        </div>

        {{-- 7 --}}
        <div class="flow-step">
            <div class="flow-num" style="background:#10b981">7</div>
            <div>
                <p class="flow-title">Lihat Hasil & Export</p>
                <p class="flow-desc">
                    Setelah ujian selesai, buka halaman <b>Hasil Ujian</b> untuk melihat statistik:
                    rata-rata nilai, distribusi nilai per rentang, dan soal yang paling banyak dijawab salah.
                    Export CSV untuk pengolahan di spreadsheet.
                </p>
            </div>
            <span class="flow-note ok">Nilai otomatis dihitung</span>
        </div>

    </div>

    <div class="tip-box" style="margin-top:1.25rem">
        <b>Perhatian jadwal:</b> Di browser Windows, input jam mungkin tampil dalam format 12 jam (AM/PM).
        Jam 7 malam = <b>7:20 PM</b>, bukan AM. Cek teks konfirmasi di bawah kolom Mulai/Selesai saat mengisi jadwal ujian.
    </div>
</div>

{{-- ═══════════════════════════════════════════════ ALUR SISWA ═══════════════════════════════════════════════ --}}
<div class="card mb">
    <h2 style="margin-bottom:.3rem">Alur Siswa di Aplikasi HP</h2>
    <p class="muted small" style="margin-bottom:1.5rem">Yang dialami siswa dari buka aplikasi sampai nilai keluar.</p>

    <div class="mobile-flow">
        <div class="mobile-step">
            <div class="mobile-icon" style="background:#eef0ff">🔐</div>
            <div class="mobile-label">1. Login</div>
            <div class="mobile-sub">Kode ujian + NIS + password</div>
        </div>
        <div class="mobile-step">
            <div class="mobile-icon" style="background:#fff3e0">📦</div>
            <div class="mobile-label">2. Download</div>
            <div class="mobile-sub">Paket terenkripsi disimpan di HP ({{ $settings['download_open_hours'] }} jam sebelum mulai)</div>
        </div>
        <div class="mobile-step">
            <div class="mobile-icon" style="background:#f0fdf4">🔑</div>
            <div class="mobile-label">3. Unlock</div>
            <div class="mobile-sub">Ambil kunci soal saat jadwal mulai → aktifkan mode pesawat</div>
        </div>
        <div class="mobile-step">
            <div class="mobile-icon" style="background:#ffe4e6">✏️</div>
            <div class="mobile-label">4. Kerjakan</div>
            <div class="mobile-sub">Offline penuh, jawaban disimpan lokal di HP</div>
        </div>
        <div class="mobile-step">
            <div class="mobile-icon" style="background:#e8fadf">☁️</div>
            <div class="mobile-label">5. Submit</div>
            <div class="mobile-sub">Nyalakan internet → kirim jawaban → nilai muncul</div>
        </div>
    </div>

    <div class="tip-box warn" style="margin-top:1.5rem">
        <b>Mode Pesawat Wajib:</b> Sebelum soal bisa dibuka, aplikasi memeriksa tidak ada koneksi internet.
        Jika internet aktif saat mengerjakan, ujian otomatis terkunci dan alarm berbunyi.
        Pengawas dapat melihat pelanggaran ini di halaman Monitor.
    </div>
    <div class="tip-box ok">
        <b>Jawaban aman meski belum terkirim:</b> Siswa tidak perlu panik jika gagal upload.
        Jawaban sudah terkunci di HP dan bisa dikirim ulang kapan saja selama aplikasi tidak dihapus,
        hingga {{ $settings['upload_grace'] }} menit setelah jam selesai ujian.
    </div>
</div>

{{-- ═══════════════════════════════════════════════ STATUS REFERENSI ═══════════════════════════════════════════════ --}}
<div class="two mb">

    <div class="card">
        <h2 style="margin-bottom:1rem">Status Ujian</h2>
        <div class="status-flow-row">
            <span class="badge draft">Draft</span>
            <span class="status-arrow">→</span>
            <span class="badge info">Siap Publish</span>
            <span class="status-arrow">→</span>
            <span class="badge published">Published</span>
        </div>
        <div class="status-flow-row">
            <span class="badge published">Published</span>
            <span class="status-arrow">→</span>
            <span class="badge closed">Ditutup</span>
            <span class="status-arrow">→</span>
            <span class="badge archived">Diarsipkan</span>
        </div>
        <table class="ref-table" style="margin-top:.75rem">
            <thead><tr><th>Status</th><th>Artinya</th></tr></thead>
            <tbody>
                <tr><td><span class="badge draft">Draft</span></td><td>Baru dibuat, bisa diedit bebas</td></tr>
                <tr><td><span class="badge info">Siap Publish</span></td><td>Semua checklist terpenuhi, siap di-publish</td></tr>
                <tr><td><span class="badge published">Published</span></td><td>Aktif — siswa bisa download dan mengerjakan</td></tr>
                <tr><td><span class="badge closed">Ditutup</span></td><td>Selesai — tidak bisa submit baru</td></tr>
                <tr><td><span class="badge archived">Diarsipkan</span></td><td>Disembunyikan dari daftar aktif</td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2 style="margin-bottom:1rem">Status Peserta</h2>
        <table class="ref-table">
            <thead><tr><th>Status</th><th>Artinya</th></tr></thead>
            <tbody>
                <tr><td><span class="badge assigned">Belum login</span></td><td>Belum pernah login di aplikasi</td></tr>
                <tr><td><span class="badge download_ready">Siap download</span></td><td>Login berhasil, menunggu slot download</td></tr>
                <tr><td><span class="badge downloading">Mengunduh</span></td><td>Sedang mengunduh paket soal</td></tr>
                <tr><td><span class="badge downloaded">Paket terunduh</span></td><td>Paket berhasil diunduh ke HP</td></tr>
                <tr><td><span class="badge unlocked">Soal terbuka</span></td><td>Unlock key diterima, soal bisa dikerjakan</td></tr>
                <tr><td><span class="badge in_progress">Mengerjakan</span></td><td>Sedang mengerjakan soal</td></tr>
                <tr><td><span class="badge locked">Terkunci</span></td><td>Ujian terkunci karena pelanggaran integritas</td></tr>
                <tr><td><span class="badge synced">Tersinkron</span></td><td>Jawaban tersinkron sebagian ke server</td></tr>
                <tr><td><span class="badge submitted">Sudah submit</span></td><td>Jawaban diterima server, nilai tersedia</td></tr>
            </tbody>
        </table>
    </div>

</div>

{{-- ═══════════════════════════════════════════════ FAQ MASALAH UMUM ═══════════════════════════════════════════════ --}}
<div class="card mb">
    <h2 style="margin-bottom:1rem">Penanganan Masalah Umum</h2>

    @php
    $faqs = [
        [
            'q' => 'Siswa tidak bisa login — "Kode ujian tidak ditemukan"',
            'a' => 'Pastikan kode ujian yang diberikan benar (huruf kapital). Cek di halaman detail ujian apakah ujian sudah berstatus Published. Ujian Draft/Siap tidak bisa diakses siswa.',
        ],
        [
            'q' => '"Jendela download belum dibuka" padahal ujian sudah dipublish',
            'a' => 'Download baru tersedia ' . $settings['download_open_hours'] . ' jam sebelum starts_at. Jika ujian belum memiliki jadwal mulai, jendela selalu terbuka. Cek jadwal di halaman detail ujian → Timeline Ujian.',
        ],
        [
            'q' => '"Waktu ujian sudah berakhir" saat siswa coba download',
            'a' => 'Waktu ends_at sudah terlewati. Guru perlu memperpanjang ends_at dari halaman edit ujian, atau buat ujian baru jika sudah ada aktivitas siswa.',
        ],
        [
            'q' => 'Soal tidak bisa dibuka — tombol unlock tidak aktif',
            'a' => 'Tombol unlock hanya aktif setelah waktu starts_at tercapai. Cek jadwal mulai di layar download HP siswa. Jika jadwal sudah lewat dan masih gagal, pastikan ujian masih berstatus Published (bukan Ditutup).',
        ],
        [
            'q' => 'HP siswa terkunci, tidak bisa keluar atau nyalakan internet',
            'a' => 'Ini terjadi jika kiosk mode aktif. Minta siswa tekan tombol Submit terlebih dahulu — setelah submit berhasil, kunci otomatis terlepas. Jika darurat, admin bisa klik "Ganti HP" di halaman Monitor untuk membebaskan kunci.',
        ],
        [
            'q' => 'Jawaban tidak terupload — "server tidak dapat dihubungi"',
            'a' => 'Jawaban sudah aman di HP. Minta siswa: (1) matikan mode pesawat, (2) nyalakan WiFi/data, (3) tekan "Kirim Ulang" di aplikasi. Jawaban bisa dikirim ulang kapan saja selama aplikasi belum dihapus dan dalam batas toleransi ' . $settings['upload_grace'] . ' menit setelah ujian selesai.',
        ],
        [
            'q' => 'Siswa perlu ganti HP karena HP lama rusak atau hilang',
            'a' => 'Buka halaman Peserta ujian → cari siswa tersebut → klik "Ganti HP". Ini menghapus kunci perangkat lama sehingga siswa bisa login dari HP baru. Jawaban yang sudah dijawab di HP lama TIDAK terhapus.',
        ],
        [
            'q' => 'Jadwal yang diset 19:20 muncul sebagai 07:20 setelah disimpan',
            'a' => 'Ini terjadi di browser Windows yang menampilkan jam dalam format 12 jam. Jam 7 malam harus dipilih sebagai 7:20 PM, bukan AM. Lihat teks konfirmasi di bawah kolom Mulai/Selesai saat mengisi form ujian untuk memverifikasi jam yang benar sebelum simpan.',
        ],
        [
            'q' => 'Soal ujian perlu diubah tapi ujian sudah Published',
            'a' => 'Soal yang sudah dikunci tidak bisa diedit langsung. Opsi: (1) Kembalikan ke Draft dari Zona Berbahaya (hanya jika belum ada siswa yang mulai), edit soal, lalu publish ulang. (2) Jika sudah ada aktivitas siswa, buat ujian baru.',
        ],
        [
            'q' => 'Ada siswa yang masuk ke ujian padahal salah kelas',
            'a' => 'Buka halaman Peserta → cari siswa tersebut → tombol "Hapus" akan muncul jika siswa belum pernah login/download. Jika sudah ada aktivitas, gunakan "Ulangi Ujian" untuk reset, lalu komunikasikan dengan siswa.',
        ],
    ];
    @endphp

    @foreach($faqs as $i => $faq)
    <div class="faq-item" id="faq{{ $i }}">
        <div class="faq-q" onclick="toggleFaq({{ $i }})">
            <span>{{ $faq['q'] }}</span>
            <span class="faq-chevron">▾</span>
        </div>
        <div class="faq-a">{{ $faq['a'] }}</div>
    </div>
    @endforeach
</div>

{{-- ═══════════════════════════════════════════════ REFERENSI PENGATURAN ═══════════════════════════════════════════════ --}}
<div class="card mb">
    <h2 style="margin-bottom:1rem">Referensi Pengaturan Sistem</h2>
    <p class="muted small" style="margin-bottom:1rem">Nilai saat ini dari <a href="{{ route('settings.school.edit') }}">Pengaturan Sekolah</a>. Pengaturan ini bisa diubah oleh admin.</p>

    <div class="table-wrap">
        <table class="ref-table">
            <thead>
                <tr>
                    <th>Pengaturan</th>
                    <th>Nilai Saat Ini</th>
                    <th>Penjelasan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><b>Jendela download sebelum ujian</b></td>
                    <td><span class="badge info">{{ $settings['download_open_hours'] }} jam</span></td>
                    <td>Siswa bisa mengunduh paket soal mulai N jam sebelum starts_at</td>
                </tr>
                <tr>
                    <td><b>Slot download bersamaan</b></td>
                    <td><span class="badge info">{{ $settings['concurrent_limit'] }} slot</span></td>
                    <td>Maksimal HP yang mengunduh secara bersamaan untuk mencegah server kewalahan</td>
                </tr>
                <tr>
                    <td><b>Toleransi terlambat login</b></td>
                    <td><span class="badge info">{{ $settings['late_tolerance'] }} menit</span></td>
                    <td>Berapa menit setelah starts_at siswa masih bisa login dan mulai ujian</td>
                </tr>
                <tr>
                    <td><b>Grace upload jawaban</b></td>
                    <td><span class="badge info">{{ $settings['upload_grace'] }} menit</span></td>
                    <td>Berapa menit setelah ends_at siswa masih bisa mengupload jawaban</td>
                </tr>
            </tbody>
        </table>
    </div>
    @if(auth()->user()->isAdmin())
        <div style="margin-top:.75rem">
            <a class="btn soft" href="{{ route('settings.school.edit') }}">⚙️ Ubah Pengaturan Sekolah</a>
        </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════ TOMBOL AKSI CEPAT ═══════════════════════════════════════════════ --}}
@if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
<div class="card" style="background:linear-gradient(135deg,#f8f9ff,#eef0ff)">
    <h2 style="margin-bottom:.75rem">Mulai Sekarang</h2>
    <div class="row" style="flex-wrap:wrap;gap:.75rem">
        <a class="btn primary" href="{{ route('exams.create') }}">+ Buat Ujian Baru</a>
        <a class="btn soft" href="{{ route('question-bank.create') }}">+ Tambah Soal ke Bank</a>
        <a class="btn soft" href="{{ route('exams.index') }}">📝 Daftar Ujian</a>
        <a class="btn soft" href="{{ route('question-bank.index') }}">🧩 Bank Soal</a>
        @if(auth()->user()->isAdmin())
            <a class="btn" href="{{ route('students.index') }}">🎓 Data Siswa</a>
            <a class="btn" href="{{ route('settings.school.edit') }}">⚙️ Pengaturan</a>
        @endif
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function toggleFaq(id) {
    const item = document.getElementById('faq' + id);
    item.classList.toggle('open');
}
</script>
@endpush
