@extends('layouts.app', ['title' => 'Panduan Sistem Ujian'])

@push('head')
<style>
.guide-hero{background:linear-gradient(135deg,#4357F2 0%,#6b7ff7 55%,#12B886 100%);border-radius:var(--radius);padding:2.25rem 2rem;color:#fff;margin-bottom:1.5rem}
.guide-hero h1{color:#fff;margin:0 0 .5rem;font-size:34px}
.guide-hero p{margin:0;opacity:.9;font-size:16px}
.concept-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-bottom:1.5rem}
.concept-card{border-radius:var(--radius);padding:1.35rem;background:#fff;border:1px solid var(--line)}
.concept-card h3{margin:0 0 .5rem;font-size:15px}
.concept-card p{margin:0;font-size:13px;line-height:1.55;color:var(--muted)}
.concept-phase{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:var(--primary);margin-bottom:.35rem}
.flow-grid{display:grid;gap:.85rem}
.flow-step{display:grid;grid-template-columns:52px 1fr auto;gap:1rem;align-items:start;background:var(--card);border-radius:var(--radius);padding:1.1rem 1.25rem;box-shadow:var(--shadow)}
.flow-num{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;font-weight:900;font-size:18px;color:#fff;flex-shrink:0}
.flow-title{font-weight:900;font-size:15px;margin:0 0 .3rem;color:var(--heading)}
.flow-desc{font-size:13px;color:var(--muted);margin:0;line-height:1.55}
.flow-note{font-size:11px;background:var(--info-soft);color:#0787a1;padding:.3rem .65rem;border-radius:999px;white-space:nowrap;align-self:center;font-weight:800}
.flow-note.warn{background:var(--warning-soft);color:#946200}
.flow-note.ok{background:var(--success-soft);color:#2b8a0e}
.mobile-flow{display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem}
.mobile-step{border:1px solid var(--line);border-radius:var(--radius);padding:1rem;text-align:center;background:#fff}
.mobile-label{font-size:12px;font-weight:900;color:var(--heading);margin-bottom:.25rem}
.mobile-sub{font-size:11px;color:var(--muted);line-height:1.4}
.status-flow-row{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem}
.status-arrow{color:var(--muted);font-size:14px}
.faq-item{border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;margin-bottom:.65rem}
.faq-q{padding:.85rem 1.1rem;background:#fafbff;font-weight:800;cursor:pointer;display:flex;justify-content:space-between;align-items:center;color:var(--heading)}
.faq-q:hover{background:#f0f2ff}
.faq-a{padding:0 1.1rem;max-height:0;overflow:hidden;transition:max-height .25s ease,padding .25s ease;font-size:13px;color:var(--muted);line-height:1.6}
.faq-item.open .faq-a{max-height:320px;padding:.85rem 1.1rem}
.faq-item.open .faq-chevron{transform:rotate(180deg)}
.faq-chevron{transition:transform .2s;font-size:14px}
.ref-table{width:100%;border-collapse:collapse}
.ref-table th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#566a7f;background:#f6f7f9;padding:.75rem 1rem;text-align:left;white-space:nowrap}
.ref-table td{padding:.75rem 1rem;border-top:1px solid var(--line);font-size:13px;vertical-align:top}
.tip-box{border-left:4px solid var(--primary);background:var(--primary-soft);padding:.85rem 1.1rem;border-radius:0 var(--radius) var(--radius) 0;margin:.75rem 0;font-size:13px}
.tip-box.warn{border-left-color:var(--warning);background:var(--warning-soft)}
.tip-box.ok{border-left-color:var(--success);background:var(--success-soft)}
@media(max-width:1100px){
    .concept-grid,.mobile-flow{grid-template-columns:1fr}
    .flow-step{grid-template-columns:42px 1fr}
    .flow-note{grid-column:2}
}
</style>
@endpush

@section('content')

<div class="guide-hero">
    <div class="between" style="align-items:flex-start">
        <div>
            <h1>Panduan Sistem Ujian Sekolah</h1>
            <p>Prosedur ringkas untuk guru dan administrator.</p>
        </div>
        <a class="btn" href="{{ route('dashboard') }}" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35)">
            Kembali ke Dashboard
        </a>
    </div>
</div>

<div class="card mb">
    <h2 style="margin-bottom:.3rem">Konsep Utama</h2>
    <p class="muted small" style="margin-bottom:1.25rem">
        Guru menyiapkan bank soal, membuat ujian, memilih satu bank soal untuk ujian, lalu publish.
        Aplikasi siswa menangani download, pengerjaan, penguncian, alarm pelanggaran, dan submit secara otomatis.
    </p>
    <div class="concept-grid">
        <div class="concept-card">
            <div class="concept-phase">Tahap 1</div>
            <h3>Bank Soal</h3>
            <p>
                Buat kumpulan soal per mapel, jenjang, dan topik. Satu bank soal bisa berisi banyak soal
                dan bisa dipakai ulang untuk ujian berikutnya.
            </p>
        </div>
        <div class="concept-card">
            <div class="concept-phase">Tahap 2</div>
            <h3>Ujian</h3>
            <p>
                Isi judul, jadwal, durasi, kelas peserta, lalu pilih satu paket dari Bank Soal.
                Guru tidak perlu mengatur mode lock aplikasi.
            </p>
        </div>
        <div class="concept-card">
            <div class="concept-phase">Tahap 3</div>
            <h3>Pelaksanaan</h3>
            <p>
                Siswa download soal sebelum atau saat ujian berlangsung, mengerjakan di aplikasi,
                lalu mengirim jawaban. Guru memantau status dari halaman Monitor.
            </p>
        </div>
    </div>
    <div class="tip-box ok">
        <b>Prinsipnya:</b> form guru dibuat sederhana. Detail teknis aplikasi siswa berjalan otomatis dari sistem.
    </div>
</div>

<div class="card mb">
    <h2 style="margin-bottom:.3rem">Alur Kerja Guru - 7 Langkah</h2>
    <p class="muted small" style="margin-bottom:1.25rem">Dari persiapan data sampai melihat hasil ujian.</p>

    <div class="flow-grid">
        <div class="flow-step">
            <div class="flow-num" style="background:#6366f1">1</div>
            <div>
                <p class="flow-title">Siapkan Data Master</p>
                <p class="flow-desc">
                    Admin memastikan data kelas, siswa, guru, dan akun sudah benar. Siswa aktif pada kelasnya
                    akan otomatis menjadi peserta saat kelas dipilih di form ujian.
                </p>
            </div>
            @if(auth()->user()->isAdmin())
                <a class="btn soft" href="{{ route('silap.index') }}">Sinkron SILAP</a>
            @else
                <span class="flow-note">Admin</span>
            @endif
        </div>

        <div class="flow-step">
            <div class="flow-num" style="background:#8b5cf6">2</div>
            <div>
                <p class="flow-title">Buat Bank Soal</p>
                <p class="flow-desc">
                    Masuk ke Bank Soal, buat kumpulan soal, lalu tambahkan semua soal yang dibutuhkan.
                    Satu bank soal boleh berisi banyak soal dan bisa ditambah kapan saja sebelum dipakai ujian.
                </p>
            </div>
            @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
                <a class="btn soft" href="{{ route('question-bank.create') }}">Buat Soal</a>
            @endif
        </div>

        <div class="flow-step">
            <div class="flow-num" style="background:#4357F2">3</div>
            <div>
                <p class="flow-title">Buat Ujian</p>
                <p class="flow-desc">
                    Isi judul, mata pelajaran, jadwal mulai, jadwal selesai, durasi, dan kelas peserta.
                    Sistem otomatis memakai aturan aplikasi siswa yang sudah ditentukan.
                </p>
            </div>
            @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
                <a class="btn soft" href="{{ route('exams.create') }}">Buat Ujian</a>
            @endif
        </div>

        <div class="flow-step">
            <div class="flow-num" style="background:#0ea5e9">4</div>
            <div>
                <p class="flow-title">Pilih Satu Paket dari Bank Soal</p>
                <p class="flow-desc">
                    Setelah ujian disimpan, pilih satu paket bank soal yang akan dipakai.
                    Semua soal aktif di paket tersebut disalin ke ujian dan bisa direview sebelum publish.
                </p>
            </div>
            <span class="flow-note">Setelah simpan ujian</span>
        </div>

        <div class="flow-step">
            <div class="flow-num" style="background:#12B886">5</div>
            <div>
                <p class="flow-title">Publish Ujian</p>
                <p class="flow-desc">
                    Pastikan checklist detail ujian sudah hijau: soal tersedia, kelas dipilih,
                    peserta tersinkron, jadwal valid, dan durasi valid. Setelah publish, siswa bisa mulai download sesuai jadwal.
                </p>
            </div>
            <span class="flow-note ok">Siap untuk siswa</span>
        </div>

        <div class="flow-step">
            <div class="flow-num" style="background:#f59e0b">6</div>
            <div>
                <p class="flow-title">Monitor Pelaksanaan</p>
                <p class="flow-desc">
                    Pantau siswa yang belum login, sedang download, mengerjakan, terkena pelanggaran,
                    atau sudah submit. Gunakan filter kelas dan status jika peserta banyak.
                </p>
            </div>
            <span class="flow-note warn">Saat ujian</span>
        </div>

        <div class="flow-step">
            <div class="flow-num" style="background:#10b981">7</div>
            <div>
                <p class="flow-title">Lihat Hasil dan Export</p>
                <p class="flow-desc">
                    Setelah submit diterima, nilai tersedia di halaman Hasil Ujian.
                    Data bisa diexport untuk rekap sekolah.
                </p>
            </div>
            <span class="flow-note ok">Selesai</span>
        </div>
    </div>

    <div class="tip-box" style="margin-top:1.25rem">
        <b>Perhatian jadwal:</b> Di browser Windows, input jam bisa tampil format 12 jam (AM/PM).
        Jam 7 malam harus dipilih sebagai 7:00 PM, bukan 7:00 AM.
    </div>
</div>

<div class="card mb">
    <h2 style="margin-bottom:.3rem">Alur Siswa di Aplikasi</h2>
    <p class="muted small" style="margin-bottom:1.25rem">Ringkasan yang perlu diketahui guru saat mengawasi.</p>

    <div class="mobile-flow">
        <div class="mobile-step">
            <div class="mobile-label">1. Login</div>
            <div class="mobile-sub">Kode ujian, NIS, dan password.</div>
        </div>
        <div class="mobile-step">
            <div class="mobile-label">2. Download Soal</div>
            <div class="mobile-sub">Mulai {{ $settings['download_open_hours'] }} jam sebelum ujian, tetap bisa saat ujian berlangsung.</div>
        </div>
        <div class="mobile-step">
            <div class="mobile-label">3. Buka Soal</div>
            <div class="mobile-sub">Sesuai jadwal mulai yang diatur guru.</div>
        </div>
        <div class="mobile-step">
            <div class="mobile-label">4. Kerjakan</div>
            <div class="mobile-sub">Jawaban tersimpan di aplikasi selama pengerjaan.</div>
        </div>
        <div class="mobile-step">
            <div class="mobile-label">5. Submit</div>
            <div class="mobile-sub">Jawaban dikirim ke server dan nilai diproses.</div>
        </div>
    </div>

    <div class="tip-box warn" style="margin-top:1.5rem">
        Aplikasi siswa otomatis menjaga fokus ujian. Jika siswa memaksa keluar dari aplikasi,
        alarm pelanggaran berbunyi dan statusnya tercatat di Monitor.
    </div>
    <div class="tip-box ok">
        Jika submit gagal karena koneksi, jawaban tetap ada di aplikasi. Minta siswa menyalakan internet
        dan tekan kirim ulang selama masih dalam toleransi {{ $settings['upload_grace'] }} menit setelah ujian selesai.
    </div>
</div>

<div class="two mb">
    <div class="card">
        <h2 style="margin-bottom:1rem">Status Ujian</h2>
        <div class="status-flow-row">
            <span class="badge draft">Draft</span>
            <span class="status-arrow">-></span>
            <span class="badge info">Siap Publish</span>
            <span class="status-arrow">-></span>
            <span class="badge published">Published</span>
        </div>
        <div class="status-flow-row">
            <span class="badge published">Published</span>
            <span class="status-arrow">-></span>
            <span class="badge closed">Ditutup</span>
            <span class="status-arrow">-></span>
            <span class="badge archived">Diarsipkan</span>
        </div>
        <table class="ref-table" style="margin-top:.75rem">
            <thead><tr><th>Status</th><th>Artinya</th></tr></thead>
            <tbody>
                <tr><td><span class="badge draft">Draft</span></td><td>Baru dibuat dan masih bisa diedit.</td></tr>
                <tr><td><span class="badge info">Siap Publish</span></td><td>Checklist utama sudah terpenuhi.</td></tr>
                <tr><td><span class="badge published">Published</span></td><td>Aktif untuk siswa sesuai jadwal.</td></tr>
                <tr><td><span class="badge closed">Ditutup</span></td><td>Siswa tidak bisa submit baru.</td></tr>
                <tr><td><span class="badge archived">Diarsipkan</span></td><td>Disembunyikan dari daftar aktif.</td></tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2 style="margin-bottom:1rem">Status Peserta</h2>
        <table class="ref-table">
            <thead><tr><th>Status</th><th>Artinya</th></tr></thead>
            <tbody>
                <tr><td><span class="badge assigned">Belum login</span></td><td>Belum pernah login di aplikasi.</td></tr>
                <tr><td><span class="badge download_ready">Siap download</span></td><td>Login berhasil dan menunggu giliran download.</td></tr>
                <tr><td><span class="badge downloading">Mengunduh</span></td><td>Sedang download soal.</td></tr>
                <tr><td><span class="badge downloaded">Sudah download</span></td><td>Soal sudah ada di aplikasi siswa.</td></tr>
                <tr><td><span class="badge unlocked">Soal terbuka</span></td><td>Siswa sudah membuka soal.</td></tr>
                <tr><td><span class="badge in_progress">Mengerjakan</span></td><td>Siswa sedang mengerjakan.</td></tr>
                <tr><td><span class="badge locked">Terkunci</span></td><td>Ada pelanggaran atau sesi perlu ditangani pengawas.</td></tr>
                <tr><td><span class="badge submitted">Sudah submit</span></td><td>Jawaban diterima server.</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb">
    <h2 style="margin-bottom:1rem">Penanganan Masalah Umum</h2>

    @php
    $faqs = [
        [
            'q' => 'Siswa tidak bisa login: kode ujian tidak ditemukan',
            'a' => 'Pastikan kode ujian benar dan ujian sudah Published. Ujian Draft atau Siap Publish belum bisa diakses siswa.',
        ],
        [
            'q' => 'Download belum tersedia padahal ujian sudah dipublish',
            'a' => 'Download tersedia ' . $settings['download_open_hours'] . ' jam sebelum jadwal mulai. Jika jadwal belum diisi, download terbuka setelah publish.',
        ],
        [
            'q' => 'Siswa baru download saat ujian sudah mulai',
            'a' => 'Bisa. Selama ujian belum melewati jadwal selesai dan status masih Published, siswa tetap bisa download lalu mengerjakan.',
        ],
        [
            'q' => 'Siswa terkena alarm pelanggaran',
            'a' => 'Cek halaman Monitor untuk melihat status siswa. Jika perlu ganti perangkat, gunakan tombol Ganti HP pada peserta tersebut.',
        ],
        [
            'q' => 'Jawaban tidak terkirim karena koneksi',
            'a' => 'Minta siswa memastikan internet aktif lalu tekan kirim ulang. Jawaban bisa dikirim ulang selama aplikasi belum dihapus dan masih dalam toleransi ' . $settings['upload_grace'] . ' menit setelah ujian selesai.',
        ],
        [
            'q' => 'Siswa perlu ganti HP',
            'a' => 'Buka halaman Peserta ujian, cari siswa, lalu klik Ganti HP. Ini menghapus kunci perangkat lama agar siswa bisa login dari perangkat baru.',
        ],
        [
            'q' => 'Soal ujian perlu diubah tapi ujian sudah Published',
            'a' => 'Jika belum ada aktivitas siswa, kembalikan ke Draft, ubah soal, lalu publish lagi. Jika sudah ada aktivitas, buat ujian baru agar data tetap konsisten.',
        ],
        [
            'q' => 'Ada siswa salah kelas',
            'a' => 'Jika belum ada aktivitas, hapus dari peserta atau ubah kelas ujian. Jika sudah ada aktivitas, tangani dari halaman peserta dan catat ke pengawas.',
        ],
    ];
    @endphp

    @foreach($faqs as $i => $faq)
        <div class="faq-item" id="faq{{ $i }}">
            <div class="faq-q" onclick="toggleFaq({{ $i }})">
                <span>{{ $faq['q'] }}</span>
                <span class="faq-chevron">v</span>
            </div>
            <div class="faq-a">{{ $faq['a'] }}</div>
        </div>
    @endforeach
</div>

<div class="card mb">
    <h2 style="margin-bottom:1rem">Referensi Pengaturan Sistem</h2>
    <p class="muted small" style="margin-bottom:1rem">Nilai saat ini dari <a href="{{ route('settings.school.edit') }}">Pengaturan Sekolah</a>.</p>

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
                    <td><b>Download dibuka sebelum ujian</b></td>
                    <td><span class="badge info">{{ $settings['download_open_hours'] }} jam</span></td>
                    <td>Siswa bisa download mulai N jam sebelum jadwal mulai dan tetap bisa saat ujian berlangsung.</td>
                </tr>
                <tr>
                    <td><b>Slot download bersamaan</b></td>
                    <td><span class="badge info">{{ $settings['concurrent_limit'] }} slot</span></td>
                    <td>Maksimal perangkat yang download bersamaan agar server stabil.</td>
                </tr>
                <tr>
                    <td><b>Toleransi terlambat login</b></td>
                    <td><span class="badge info">{{ $settings['late_tolerance'] }} menit</span></td>
                    <td>Berapa menit setelah jadwal mulai siswa masih bisa login dan mulai ujian.</td>
                </tr>
                <tr>
                    <td><b>Grace upload jawaban</b></td>
                    <td><span class="badge info">{{ $settings['upload_grace'] }} menit</span></td>
                    <td>Berapa menit setelah jadwal selesai siswa masih bisa upload jawaban.</td>
                </tr>
            </tbody>
        </table>
    </div>
    @if(auth()->user()->isAdmin())
        <div style="margin-top:.75rem">
            <a class="btn soft" href="{{ route('settings.school.edit') }}">Ubah Pengaturan Sekolah</a>
        </div>
    @endif
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
<div class="card" style="background:linear-gradient(135deg,#f8f9ff,#eef0ff)">
    <h2 style="margin-bottom:.75rem">Mulai Sekarang</h2>
    <div class="row" style="flex-wrap:wrap;gap:.75rem">
        <a class="btn primary" href="{{ route('exams.create') }}">Buat Ujian Baru</a>
        <a class="btn soft" href="{{ route('question-bank.create') }}">Tambah Soal ke Bank</a>
        <a class="btn soft" href="{{ route('exams.index') }}">Daftar Ujian</a>
        <a class="btn soft" href="{{ route('question-bank.index') }}">Bank Soal</a>
        @if(auth()->user()->isAdmin())
            <a class="btn" href="{{ route('students.index') }}">Data Siswa</a>
            <a class="btn" href="{{ route('settings.school.edit') }}">Pengaturan</a>
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
