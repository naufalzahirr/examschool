@extends('layouts.app', ['title' => $exam->exists ? 'Edit Ujian' : 'Buat Ujian'])

@section('content')
<div class="between mb">
    <div>
        <h1>{{ $exam->exists ? 'Edit Ujian' : 'Buat Ujian' }}</h1>
        <p class="muted">Isi informasi ujian, pilih kelas peserta, lalu ambil soal dari Bank Soal.</p>
    </div>
    <a class="btn" href="{{ $exam->exists ? route('exams.show', $exam) : route('exams.index') }}">Kembali</a>
</div>

<div class="card mb">
    <div class="step-list">
        <div class="step">
            <span class="step-no">1</span>
            <div><b>Atur ujian</b><br><span class="muted small">Judul, mapel, kelas peserta, jadwal, dan durasi.</span></div>
        </div>
        <div class="step">
            <span class="step-no">2</span>
            <div><b>Pilih soal</b><br><span class="muted small">Setelah disimpan, ambil soal dari Bank Soal.</span></div>
        </div>
        <div class="step">
            <span class="step-no">3</span>
            <div><b>Publish</b><br><span class="muted small">Sistem membuat paket soal terenkripsi untuk aplikasi siswa.</span></div>
        </div>
    </div>
</div>

<form method="POST" action="{{ $exam->exists ? route('exams.update', $exam) : route('exams.store') }}" class="form">
    @csrf
    @if($exam->exists) @method('PUT') @endif

    <div class="card">
        <div class="between mb">
            <div>
                <h2 class="mb0">Informasi Ujian</h2>
                <p class="muted small mb0">Data ini tampil di dashboard guru dan aplikasi siswa.</p>
            </div>
            @if($exam->exists)
                <span class="badge info">{{ $exam->access_code }}</span>
            @else
                <span class="badge info">Kode otomatis</span>
            @endif
        </div>

        @if($exam->exists)
            <div class="alert info" style="margin-bottom:0">
            <b>Kode ujian otomatis:</b> {{ $exam->access_code }}<br>
            <span class="small">Kode ini dipakai siswa saat login di aplikasi Android bersama NIS dan password.</span>
            </div>
            @if($exam->hasStartedWork())
                <div class="alert warning" style="margin-bottom:0">
                    Ujian sudah memiliki aktivitas siswa. Demi konsistensi paket soal, kelas, durasi, dan aturan acak tidak boleh diubah.
                </div>
            @endif
        @else
            <div class="alert info" style="margin-bottom:0">Kode ujian akan otomatis dibuat setelah konfigurasi disimpan.</div>
        @endif

        <div class="field">
            <label>Judul Ujian</label>
            <input class="input" name="title" value="{{ old('title', $exam->title) }}" placeholder="Contoh: Ujian Sekolah Bahasa Indonesia XII" required>
        </div>

        <div class="two">
            <div class="field"><label>Mata Pelajaran</label><input class="input" name="subject" value="{{ old('subject', $exam->subject) }}" placeholder="Contoh: Bahasa Indonesia"></div>
            <div class="field"><label>Jenjang / Label</label><input class="input" name="grade_level" value="{{ old('grade_level', $exam->grade_level) }}" placeholder="Contoh: XII / Sumatif Akhir"></div>
        </div>

        <div class="field"><label>Instruksi untuk Siswa</label><textarea class="input" name="description" rows="4" placeholder="Opsional. Contoh: Kerjakan dengan teliti, jangan keluar dari aplikasi sebelum submit.">{{ old('description', $exam->description) }}</textarea></div>
    </div>

    <div class="card">
        <div class="between mb">
            <div>
                <h2 class="mb0">Peserta</h2>
                <p class="muted small mb0">Pilih kelas yang mengikuti ujian. Siswa aktif di kelas terpilih otomatis menjadi peserta.</p>
            </div>
        </div>
        @if($classrooms->count())
            @php($oldClassrooms = collect(old('classroom_ids', $selectedClassroomIds ?? []))->map(fn($id) => (string) $id)->all())
            <div class="class-grid">
                @foreach($classrooms->groupBy('tingkat') as $tingkat => $group)
                    <div class="mini-card">
                        <div class="between"><b>Tingkat {{ $tingkat ?: '-' }}</b><span class="muted small">{{ $group->count() }} kelas</span></div>
                        <div class="check-list">
                            @foreach($group as $classroom)
                                <label class="check-pill">
                                    <input type="checkbox" name="classroom_ids[]" value="{{ $classroom->id }}" @checked(in_array((string) $classroom->id, $oldClassrooms, true))>
                                    <span>{{ $classroom->nama_kelas }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="help">Bisa pilih lebih dari satu kelas. Siswa aktif pada kelas terpilih akan otomatis menjadi peserta ujian.</p>
        @else
            <div class="alert warning" style="margin-bottom:0">
                Belum ada data kelas. Import/sinkron data kelas dari menu <b>Kelas</b> atau <b>Sinkron SILAP</b> terlebih dahulu.
            </div>
        @endif
    </div>

    <div class="card">
        <div class="between mb">
            <div>
                <h2 class="mb0">Jadwal & Aturan Soal</h2>
                <p class="muted small mb0">Jadwal menentukan kapan siswa bisa download paket dan kapan soal bisa dibuka.</p>
            </div>
        </div>
        <div class="alert info" style="padding:.65rem .9rem;margin-bottom:.75rem">
            📱 <b>Alur di HP siswa:</b>
            <span class="muted">Download paket</span> (12 jam sebelum Mulai)
            → <span class="muted">Soal terbuka</span> (saat jam Mulai)
            → <span class="muted">Tidak bisa lagi</span> (setelah jam Selesai).
            Pastikan <b>Selesai</b> ≥ <b>Mulai</b> + durasi ujian.
        </div>
        <div class="three">
            <div class="field">
                <label>Mulai</label>
                <input id="starts_at_input" class="input" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($exam->starts_at)->format('Y-m-d\TH:i')) }}">
                <p class="help" id="starts_at_preview">
                    @if(old('starts_at', optional($exam->starts_at)->format('Y-m-d\TH:i')))
                        Tersimpan: <b>{{ optional($exam->starts_at)->format('d M Y, H:i') ?? '' }}</b>
                    @else
                        Isi tanggal dan jam mulai.
                    @endif
                </p>
            </div>
            <div class="field">
                <label>Selesai</label>
                <input id="ends_at_input" class="input" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($exam->ends_at)->format('Y-m-d\TH:i')) }}">
                <p class="help" id="ends_at_preview">
                    @if(old('ends_at', optional($exam->ends_at)->format('Y-m-d\TH:i')))
                        Tersimpan: <b>{{ optional($exam->ends_at)->format('d M Y, H:i') ?? '' }}</b>
                    @else
                        Isi tanggal dan jam selesai.
                    @endif
                </p>
            </div>
            <div class="field"><label>Durasi Pengerjaan (menit)</label><input class="input" type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes ?: 90) }}" min="1" required></div>
        </div>
        <div class="alert warning" style="padding:.65rem .9rem;margin:.5rem 0">
            ⚠️ <b>Perhatikan jam:</b> Di beberapa browser Windows, jam tampil dalam format 12 jam (AM/PM).
            Jam 7 malam harus dipilih sebagai <b>7:20 PM</b> (bukan AM). Cek tulisan di bawah kolom Mulai/Selesai untuk konfirmasi.
        </div>
        <div class="row">
            <label class="check-pill"><input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $exam->shuffle_questions))> Acak urutan soal</label>
            <label class="check-pill"><input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options', $exam->shuffle_options))> Acak urutan opsi jawaban</label>
        </div>
        <p class="help">Kunci jawaban tetap hanya tersimpan di server, tidak ikut dikirim ke aplikasi siswa.</p>

        <hr style="border:0;border-top:1px solid var(--line);margin:1rem 0">

        <div class="two">
            <div class="field">
                <label>Mode Keamanan Ujian</label>
                <select class="input" name="lock_mode">
                    @foreach($lockModes as $value => $label)
                        <option value="{{ $value }}" @selected(old('lock_mode', $exam->lock_mode ?? \App\Models\Exam::LOCK_STRICT_AIRPLANE) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="help">Menentukan cara aplikasi siswa mengunci HP selama ujian berlangsung.</p>
            </div>
            <div class="field">
                <label>Aturan Keluar Ujian</label>
                <select class="input" name="exit_policy">
                    @foreach($exitPolicies as $value => $label)
                        <option value="{{ $value }}" @selected(old('exit_policy', $exam->exit_policy ?? \App\Models\Exam::EXIT_AFTER_SUBMIT) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="help">Kapan siswa diizinkan keluar dari aplikasi ujian.</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="between">
            <div>
                <h2 class="mb0">{{ $exam->exists ? 'Simpan Perubahan' : 'Simpan & Lanjut Pilih Soal' }}</h2>
                <p class="muted small mb0">{{ $exam->exists ? 'Jika soal atau kelas berubah, paket ujian akan ditandai perlu dibuat ulang.' : 'Setelah disimpan, Anda langsung diarahkan ke Bank Soal untuk memilih soal ujian.' }}</p>
            </div>
            <div class="row">
                <a class="btn" href="{{ $exam->exists ? route('exams.show', $exam) : route('exams.index') }}">Batal</a>
                <button class="btn primary">{{ $exam->exists ? 'Simpan Konfigurasi' : 'Simpan & Pilih Soal' }}</button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
// Preview jam yang dipilih di bawah input agar user bisa konfirmasi AM/PM
const HARI = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
const BULAN = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

function formatPreview(val) {
    if (!val) return 'Belum diisi.';
    const d = new Date(val);
    if (isNaN(d)) return 'Format tidak valid.';
    const jam = String(d.getHours()).padStart(2, '0');
    const mnt = String(d.getMinutes()).padStart(2, '0');
    const hari = HARI[d.getDay()];
    const tgl = d.getDate();
    const bln = BULAN[d.getMonth()];
    const thn = d.getFullYear();
    const pm = d.getHours() >= 12 ? ' (siang/malam)' : ' (pagi)';
    return `<b>${hari}, ${tgl} ${bln} ${thn} jam ${jam}:${mnt}</b>${pm}`;
}

function bindPreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;
    input.addEventListener('change', function() {
        preview.innerHTML = formatPreview(this.value);
    });
    // Init
    if (input.value) preview.innerHTML = formatPreview(input.value);
}

document.addEventListener('DOMContentLoaded', function() {
    bindPreview('starts_at_input', 'starts_at_preview');
    bindPreview('ends_at_input', 'ends_at_preview');
});
</script>
@endpush
