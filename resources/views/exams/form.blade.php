@extends('layouts.app', ['title' => $exam->exists ? 'Edit Ujian' : 'Buat Ujian Baru'])

@push('head')
<style>
.form-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.form-step{display:flex;gap:.75rem;align-items:flex-start;padding:.95rem 1rem;border:1px solid var(--line);border-radius:var(--radius);background:#fff;position:relative}
.form-step.active{border-color:var(--primary);background:var(--primary-soft)}
.form-step-no{width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;display:grid;place-items:center;font-weight:950;font-size:14px;flex-shrink:0}
.form-step.pending .form-step-no{background:var(--line);color:var(--muted)}
.section-header{display:flex;align-items:center;gap:.75rem;margin-bottom:1.1rem;padding-bottom:.9rem;border-bottom:1px solid var(--line)}
.section-header-icon{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;font-size:18px;flex-shrink:0}
.classroom-checkbox{display:flex;align-items:center;gap:.5rem;padding:.6rem .75rem;border:1px solid var(--line);border-radius:var(--radius);background:#fff;cursor:pointer;font-weight:800;font-size:13px;transition:all .15s}
.classroom-checkbox:hover{border-color:var(--primary);background:var(--primary-soft)}
.classroom-checkbox input{width:auto;min-height:auto}
.classroom-checkbox:has(input:checked){background:var(--primary-soft);border-color:var(--primary);color:var(--primary-strong)}
.lock-option{display:flex;align-items:flex-start;gap:.75rem;padding:1rem;border:1px solid var(--line);border-radius:var(--radius);background:#fff;cursor:pointer;transition:all .15s}
.lock-option:has(input:checked){border-color:var(--primary);background:var(--primary-soft)}
.simple-mode-card{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:1rem 1.1rem;border:1px solid #bbf7d0;border-radius:var(--radius);background:linear-gradient(135deg,#f0fdf9,#ecfdf5);margin-bottom:1rem}
.simple-mode-card b{color:#166534}
.advanced-settings{border:1px solid var(--line);border-radius:var(--radius);background:#fff;margin-top:1rem;overflow:hidden}
.advanced-settings summary{padding:1rem 1.1rem;font-weight:950;color:var(--heading);cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:1rem}
.advanced-settings summary:after{content:"Buka";font-size:11px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;color:var(--primary-strong);background:var(--primary-soft);border:1px solid #bdeee7;border-radius:999px;padding:.25rem .55rem}
.advanced-settings[open] summary{border-bottom:1px solid var(--line)}
.advanced-settings[open] summary:after{content:"Tutup"}
.advanced-body{padding:1rem 1.1rem 1.1rem}
.mode-pick{display:flex;gap:.7rem;align-items:flex-start;padding:1rem;border:2px solid var(--line);border-radius:var(--radius);cursor:pointer;background:#fff}
.mode-pick input{width:auto;min-height:auto;margin-top:.2rem}
.save-bar{position:sticky;bottom:0;background:rgba(255,255,255,.94);backdrop-filter:blur(18px);border-top:1px solid var(--line);padding:1rem 1.35rem;margin:0 -1.35rem -1.35rem;border-radius:0 0 var(--radius) var(--radius);z-index:5}
</style>
@endpush

@section('content')

{{-- ═══ HEADER ═══ --}}
<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0">{{ $exam->exists ? 'Edit Ujian' : 'Buat Ujian Baru' }}</h1>
            <p class="muted mb0">
                @if($exam->exists)
                    Kode: <b>{{ $exam->access_code }}</b>
                    @if($exam->hasStartedWork())
                        | <span style="color:var(--warning);font-weight:800">Beberapa field terkunci karena sudah ada aktivitas siswa</span>
                    @endif
                @else
                    Lengkapi tiga langkah berikut untuk mulai membuat ujian.
                @endif
            </p>
        </div>
        <a class="btn ghost" href="{{ $exam->exists ? route('exams.show', $exam) : route('exams.index') }}">Kembali</a>
    </div>
</div>

{{-- ═══ STEPS INDICATOR (hanya tampil saat buat baru) ═══ --}}
@if(!$exam->exists)
<div class="form-steps">
    <div class="form-step active">
        <div class="form-step-no">1</div>
        <div>
            <b>Konfigurasi</b><br>
            <span class="muted small">Judul, kelas, durasi</span>
        </div>
    </div>
    <div class="form-step pending">
        <div class="form-step-no">2</div>
        <div>
            <b>Pilih Soal</b><br>
            <span class="muted small">Dari Bank Soal setelah disimpan</span>
        </div>
    </div>
    <div class="form-step pending">
        <div class="form-step-no">3</div>
        <div>
            <b>Buka Ujian</b><br>
            <span class="muted small">Seperti tombol di Google Form</span>
        </div>
    </div>
</div>
@endif

{{-- ═══ BANNER: Ujian terkunci parsial ═══ --}}
@if($exam->exists && $exam->hasStartedWork())
<div class="alert warning" style="display:flex;align-items:flex-start;gap:.75rem;margin-bottom:1.25rem">
    <span class="badge warning" style="flex-shrink:0">Terkunci</span>
    <div>
        <b>Sebagian field dikunci karena sudah ada aktivitas siswa</b><br>
        <span style="font-size:13px;font-weight:700">
            Yang masih bisa diubah: <b>Judul, Mata Pelajaran, Jenjang, dan Instruksi untuk Siswa.</b><br>
            Yang tidak bisa diubah: Kelas peserta, Jadwal, Durasi, Urutan acak - untuk menjaga konsistensi soal yang sudah diunduh siswa.
        </span>
    </div>
</div>
@endif

<form method="POST" action="{{ $exam->exists ? route('exams.update', $exam) : route('exams.store') }}" class="form">
    @csrf
    @if($exam->exists) @method('PUT') @endif

    {{-- ═══ SECTION 1: INFO UJIAN ═══ --}}
    <div class="card">
        <div class="section-header">
            <div class="section-header-icon" style="background:var(--primary-soft)">01</div>
            <div>
                <h2 class="mb0">Informasi Ujian</h2>
                <p class="muted small mb0">Tampil di dashboard guru dan aplikasi siswa.</p>
            </div>
            @if($exam->exists)
                <span class="badge info" style="margin-left:auto">{{ $exam->access_code }}</span>
            @endif
        </div>

        <div class="field">
            <label>Judul Ujian <span style="color:var(--danger)">*</span></label>
            <input class="input" name="title" value="{{ old('title', $exam->title) }}"
                   placeholder="Contoh: Ujian Semester Ganjil Bahasa Indonesia XII" required>
        </div>
        <div class="two">
            <div class="field">
                <label>Mata Pelajaran</label>
                <input class="input" name="subject" value="{{ old('subject', $exam->subject) }}"
                       placeholder="Contoh: Bahasa Indonesia">
            </div>
            <div class="field">
                <label>Jenjang / Label</label>
                <input class="input" name="grade_level" value="{{ old('grade_level', $exam->grade_level) }}"
                       placeholder="Contoh: XII / Sumatif Akhir">
            </div>
        </div>
        <div class="field">
            <label>Instruksi untuk Siswa</label>
            <textarea class="input" name="description" rows="3"
                      placeholder="Opsional - contoh: Kerjakan dengan teliti. Baca setiap soal dengan seksama sebelum menjawab.">{{ old('description', $exam->description) }}</textarea>
            <p class="help">Teks ini tampil di layar siswa sebelum ujian dimulai.</p>
        </div>
    </div>

    {{-- ═══ SECTION 2: KELAS PESERTA ═══ --}}
    <div class="card">
        <div class="section-header">
            <div class="section-header-icon" style="background:var(--accent-soft)">02</div>
            <div>
                <h2 class="mb0">Kelas Peserta</h2>
                <p class="muted small mb0">Siswa aktif di kelas terpilih otomatis menjadi peserta ujian.</p>
            </div>
            @if($exam->exists && $exam->hasStartedWork())
                <span class="badge warning" style="margin-left:auto;font-size:11px">Terkunci</span>
            @endif
        </div>

        @if($classrooms->count())
            @php $oldClassrooms = collect(old('classroom_ids', $selectedClassroomIds ?? []))->map(fn($id) => (string) $id)->all(); @endphp

            {{-- Hidden inputs untuk preserve nilai saat disabled (tidak ikut submit jika disabled) --}}
            @if($exam->exists && $exam->hasStartedWork())
                @foreach($oldClassrooms as $cid)
                    <input type="hidden" name="classroom_ids[]" value="{{ $cid }}">
                @endforeach
            @endif

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem">
                @foreach($classrooms->groupBy('tingkat') as $tingkat => $group)
                    <div class="mini-card {{ ($exam->exists && $exam->hasStartedWork()) ? 'opacity-75' : '' }}" style="{{ ($exam->exists && $exam->hasStartedWork()) ? 'opacity:.7' : '' }}">
                        <div class="between" style="margin-bottom:.65rem">
                            <b style="font-size:13px">Tingkat {{ $tingkat ?: '-' }}</b>
                            <span class="muted small">{{ $group->count() }} kelas</span>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:.35rem">
                            @foreach($group as $classroom)
                                <label class="classroom-checkbox" style="{{ ($exam->exists && $exam->hasStartedWork()) ? 'cursor:not-allowed;opacity:.8' : '' }}">
                                    @if($exam->exists && $exam->hasStartedWork())
                                        {{-- Saat locked: tampilkan readonly (tidak dikirim karena sudah ada hidden input di atas) --}}
                                        <input type="checkbox" disabled
                                               @checked(in_array((string) $classroom->id, $oldClassrooms, true))
                                               style="cursor:not-allowed">
                                    @else
                                        <input type="checkbox" name="classroom_ids[]" value="{{ $classroom->id }}"
                                               @checked(in_array((string) $classroom->id, $oldClassrooms, true))>
                                    @endif
                                    {{ $classroom->nama_kelas }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="help" style="margin-top:.75rem">
                @if($exam->exists && $exam->hasStartedWork())
                    Kelas tidak bisa diubah karena sudah ada aktivitas siswa.
                    Kelola peserta dari <a href="{{ route('exams.participants', $exam) }}" style="color:var(--primary)">halaman Peserta Ujian</a>
                @else
                    Bisa pilih lebih dari satu kelas. Sinkron ulang peserta dari halaman Peserta Ujian jika ada perubahan kelas.
                @endif
            </p>
        @else
            <div class="alert warning" style="margin-bottom:0">
                Belum ada data kelas. Sinkron dari SILAP atau import dari menu <b>Kelas</b> terlebih dahulu.
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('silap.index') }}" style="font-weight:900">Sinkron SILAP</a>
                @endif
            </div>
        @endif
    </div>

    {{-- ═══ SECTION 3: JADWAL ═══ --}}
    <div class="card">
        <div class="section-header">
            <div class="section-header-icon" style="background:var(--warning-soft)">03</div>
            <div>
                <h2 class="mb0">Akses Ujian</h2>
                <p class="muted small mb0">Default dibuat sederhana: publish dulu, lalu buka/tutup ujian dari halaman detail.</p>
            </div>
            @if($exam->exists && $exam->hasStartedWork())
                <span class="badge warning" style="margin-left:auto;font-size:11px">Terkunci</span>
            @endif
        </div>

        {{-- Preserve nilai yang disabled agar tidak hilang --}}
        @if($exam->exists && $exam->hasStartedWork())
            @if($exam->starts_at)<input type="hidden" name="starts_at" value="{{ $exam->starts_at->format('Y-m-d\TH:i') }}">@endif
            @if($exam->ends_at)<input type="hidden" name="ends_at" value="{{ $exam->ends_at->format('Y-m-d\TH:i') }}">@endif
            <input type="hidden" name="duration_minutes" value="{{ $exam->duration_minutes }}">
            @if($exam->shuffle_questions)<input type="hidden" name="shuffle_questions" value="1">@endif
            @if($exam->shuffle_options)<input type="hidden" name="shuffle_options" value="1">@endif
        @endif

        @php
            $currentMode = old('schedule_mode', $exam->schedule_mode ?? \App\Models\Exam::MODE_MANUAL);
            $advancedOpen = $currentMode === \App\Models\Exam::MODE_SCHEDULED
                || old('shuffle_questions')
                || old('shuffle_options')
                || (bool) $exam->shuffle_questions
                || (bool) $exam->shuffle_options;
        @endphp

        @if($exam->exists && $exam->hasStartedWork())
        <input type="hidden" name="schedule_mode" value="{{ $exam->schedule_mode ?? \App\Models\Exam::MODE_MANUAL }}">
        {{-- Mode baca: tampilkan nilai tapi tidak bisa diedit --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:.85rem">
            <div class="mini-card">
                <div class="muted small">Cara Membuka Ujian</div>
                <b>{{ $exam->schedule_mode === \App\Models\Exam::MODE_SCHEDULED ? 'Terjadwal otomatis' : 'Manual buka/tutup' }}</b>
            </div>
            <div class="mini-card">
                <div class="muted small">Jam Mulai</div>
                <b>{{ optional($exam->starts_at)->format('d M Y, H:i') ?: 'Tidak diatur' }}</b>
            </div>
            <div class="mini-card">
                <div class="muted small">Jam Selesai</div>
                <b>{{ optional($exam->ends_at)->format('d M Y, H:i') ?: 'Tidak diatur' }}</b>
            </div>
            <div class="mini-card">
                <div class="muted small">Durasi</div>
                <b>{{ $exam->duration_minutes }} menit</b>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:.85rem">
            <div class="mini-card">
                <div class="muted small">Acak Soal</div>
                <b>{{ $exam->shuffle_questions ? 'Ya' : 'Tidak' }}</b>
            </div>
            <div class="mini-card">
                <div class="muted small">Acak Opsi Jawaban</div>
                <b>{{ $exam->shuffle_options ? 'Ya' : 'Tidak' }}</b>
            </div>
        </div>
        <p class="help">Untuk mengubah jadwal atau durasi, hubungi admin karena sudah ada aktivitas siswa.</p>

        @else
        <div class="simple-mode-card">
            <div>
                <b>Mode sederhana aktif: buka/tutup manual</b>
                <p class="muted small mb0" style="margin-top:.2rem">Sama seperti Google Form. Setelah ujian dipublish, guru tinggal menyalakan atau mematikan "menerima jawaban" dari halaman detail ujian.</p>
            </div>
            <span class="badge success" style="flex-shrink:0">Default</span>
        </div>

        <div class="two" style="margin-bottom:.5rem">
            <div class="field">
                <label>Durasi Mengerjakan (menit) <span style="color:var(--danger)">*</span></label>
                <input class="input" type="number" name="duration_minutes"
                       value="{{ old('duration_minutes', $exam->duration_minutes ?: 90) }}"
                       min="1" max="600" required>
                <p class="help">Timer di HP siswa akan mundur sesuai durasi ini.</p>
            </div>
            <div class="mini-card">
                <div class="muted small">Status setelah disimpan</div>
                <b>{{ $exam->exists && $exam->status === \App\Models\Exam::STATUS_PUBLISHED ? 'Dipublish, mengikuti tombol buka/tutup' : 'Draft, belum bisa dibuka siswa' }}</b>
                <p class="help">{{ $exam->exists ? 'Setelah simpan, buka halaman detail untuk mengatur menerima jawaban.' : 'Pilih soal dulu, publish, lalu nyalakan ujian.' }}</p>
            </div>
        </div>

        <details class="advanced-settings" @if($advancedOpen) open @endif>
            <summary>Pengaturan lanjutan (opsional)</summary>
            <div class="advanced-body">
                <p class="muted small">Bagian ini jarang perlu diubah. Pakai hanya kalau ujian harus otomatis sesuai jam, atau butuh acak soal/opsi.</p>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin-bottom:1rem">
                    <label class="mode-pick">
                        <input type="radio" name="schedule_mode" value="manual" @checked($currentMode === \App\Models\Exam::MODE_MANUAL)>
                        <div>
                            <b style="font-size:14px;color:var(--heading)">Manual buka/tutup</b>
                            <p class="muted small mb0" style="margin-top:.2rem">Direkomendasikan. Guru menyalakan dan mematikan ujian sendiri.</p>
                        </div>
                    </label>
                    <label class="mode-pick">
                        <input type="radio" name="schedule_mode" value="scheduled" @checked($currentMode === \App\Models\Exam::MODE_SCHEDULED)>
                        <div>
                            <b style="font-size:14px;color:var(--heading)">Terjadwal otomatis</b>
                            <p class="muted small mb0" style="margin-top:.2rem">Pakai kalau ujian wajib buka/tutup berdasarkan jam.</p>
                        </div>
                    </label>
                </div>

                <div id="scheduledFields">
                    <div class="alert info" style="margin-bottom:1.1rem;font-size:13px">
                        Siswa bisa download soal <b>sebelum</b> jam mulai. Soal baru bisa dibuka saat jam mulai.
                        Pastikan <b>Jam Selesai tidak lebih cepat dari Jam Mulai + Durasi</b>.
                    </div>
                    <div class="two" style="margin-bottom:.85rem">
                        <div class="field">
                            <label>Jam Mulai</label>
                            <input id="starts_at_input" class="input" type="datetime-local" name="starts_at"
                                   value="{{ old('starts_at', optional($exam->starts_at)->format('Y-m-d\TH:i')) }}">
                            <p class="help" id="starts_at_preview">
                                @if(old('starts_at', optional($exam->starts_at)->format('Y-m-d\TH:i')))
                                    <b>{{ optional($exam->starts_at)->format('l, d M Y - H:i') ?? '' }}</b>
                                @else
                                    Pilih tanggal dan jam mulai
                                @endif
                            </p>
                        </div>
                        <div class="field">
                            <label>Jam Selesai</label>
                            <input id="ends_at_input" class="input" type="datetime-local" name="ends_at"
                                   value="{{ old('ends_at', optional($exam->ends_at)->format('Y-m-d\TH:i')) }}">
                            <p class="help" id="ends_at_preview">
                                @if(old('ends_at', optional($exam->ends_at)->format('Y-m-d\TH:i')))
                                    <b>{{ optional($exam->ends_at)->format('l, d M Y - H:i') ?? '' }}</b>
                                @else
                                    Pilih tanggal dan jam selesai
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="alert warning" style="font-size:13px;margin-bottom:.9rem">
                        <b>Windows Chrome / Edge:</b> Input jam kadang tampil AM/PM. Jam 7 malam = <b>7:00 PM</b>. Cek teks konfirmasi di bawah kolom.
                    </div>
                </div>

                <div id="manualInfo" class="alert success" style="display:none;font-size:13px;margin-bottom:1rem">
                    <b>Manual aktif.</b> Ujian tidak memakai jam otomatis. Setelah publish, buka/tutup ujian dari halaman detail.
                </div>

                <div class="two">
                    <label class="check-pill" style="cursor:pointer">
                        <input type="checkbox" name="shuffle_questions" value="1"
                               @checked(old('shuffle_questions', $exam->shuffle_questions))>
                        Acak urutan soal
                    </label>
                    <label class="check-pill" style="cursor:pointer">
                        <input type="checkbox" name="shuffle_options" value="1"
                               @checked(old('shuffle_options', $exam->shuffle_options))>
                        Acak urutan opsi jawaban
                    </label>
                </div>
            </div>
        </details>
        @endif
    </div>

    {{-- ═══ SAVE BAR ═══ --}}
    <div class="card">
        <div class="save-bar between">
            <div>
                <b>{{ $exam->exists ? 'Simpan Perubahan' : 'Simpan & Pilih Soal' }}</b>
                <p class="muted small mb0">{{ $exam->exists ? 'Perubahan langsung berlaku.' : 'Setelah disimpan, Anda diarahkan ke Bank Soal untuk memilih soal.' }}</p>
            </div>
            <div class="row">
                <a class="btn ghost" href="{{ $exam->exists ? route('exams.show', $exam) : route('exams.index') }}">Batal</a>
                <button class="btn primary" style="min-width:160px">
                    {{ $exam->exists ? 'Simpan Konfigurasi' : 'Simpan & Pilih Soal' }}
                </button>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
const HARI  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
const BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function formatPreview(val) {
    if (!val) return '<span style="color:var(--muted)">Belum dipilih</span>';
    const d = new Date(val);
    if (isNaN(d)) return '<span style="color:var(--danger)">Format tidak valid</span>';
    const jam  = String(d.getHours()).padStart(2,'0');
    const mnt  = String(d.getMinutes()).padStart(2,'0');
    const waktu = d.getHours() >= 12 ? 'siang/malam' : 'pagi';
    return `<b>${HARI[d.getDay()]}, ${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()} - ${jam}:${mnt}</b> <span style="color:var(--muted)">${waktu}</span>`;
}

function bindPreview(inputId, previewId) {
    const el  = document.getElementById(inputId);
    const pre = document.getElementById(previewId);
    if (!el || !pre) return;
    const update = () => { pre.innerHTML = formatPreview(el.value); };
    el.addEventListener('change', update);
    if (el.value) update();
}

document.addEventListener('DOMContentLoaded', () => {
    bindPreview('starts_at_input', 'starts_at_preview');
    bindPreview('ends_at_input',   'ends_at_preview');

    // Toggle tampilan field jadwal sesuai mode
    const scheduledFields = document.getElementById('scheduledFields');
    const manualInfo = document.getElementById('manualInfo');
    const modeRadios = document.querySelectorAll('input[name="schedule_mode"]');

    function applyMode(){
        const mode = document.querySelector('input[name="schedule_mode"]:checked')?.value || 'manual';
        const isManual = mode === 'manual';
        if(scheduledFields) scheduledFields.style.display = isManual ? 'none' : 'block';
        if(manualInfo) manualInfo.style.display = isManual ? 'block' : 'none';
        // Tandai kartu mode terpilih
        document.querySelectorAll('.mode-pick').forEach(card => {
            const checked = card.querySelector('input')?.checked;
            card.style.borderColor = checked ? 'var(--primary)' : 'var(--line)';
            card.style.background = checked ? 'var(--primary-soft)' : '#fff';
        });
    }
    modeRadios.forEach(r => r.addEventListener('change', applyMode));
    applyMode();
});
</script>
@endpush
