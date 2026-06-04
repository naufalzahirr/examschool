@extends('layouts.app', ['title' => $exam->exists ? 'Edit Ujian' : 'Buat Ujian'])

@section('content')
<div class="between mb">
    <div>
        <h1>{{ $exam->exists ? 'Edit Ujian' : 'Buat Ujian' }}</h1>
        <p class="muted">Atur judul, jadwal, durasi, dan kelas peserta. Setelah disimpan, soal ujian bisa langsung dipilih dari Bank Soal.</p>
    </div>
    <a class="btn" href="{{ $exam->exists ? route('exams.show', $exam) : route('exams.index') }}">Kembali</a>
</div>

<form method="POST" action="{{ $exam->exists ? route('exams.update', $exam) : route('exams.store') }}" class="card form">
    @csrf
    @if($exam->exists) @method('PUT') @endif

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
        <div class="alert info" style="margin-bottom:0">Kode ujian akan otomatis dibuat setelah konfigurasi disimpan. Langkah berikutnya langsung memilih soal dari Bank Soal.</div>
    @endif

    <div class="field">
        <label>Judul Ujian</label>
        <input class="input" name="title" value="{{ old('title', $exam->title) }}" required>
    </div>

    <div class="two">
        <div class="field"><label>Mata Pelajaran</label><input class="input" name="subject" value="{{ old('subject', $exam->subject) }}"></div>
        <div class="field"><label>Tingkat/Label Ujian</label><input class="input" name="grade_level" value="{{ old('grade_level', $exam->grade_level) }}" placeholder="Contoh: Ujian Sekolah XII / Sumatif XI"></div>
    </div>

    <div class="field">
        <label>Kelas Peserta</label>
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

    <div class="field"><label>Deskripsi/Instruksi</label><textarea name="description" rows="4">{{ old('description', $exam->description) }}</textarea></div>

    <div class="three">
        <div class="field"><label>Mulai</label><input class="input" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($exam->starts_at)->format('Y-m-d\TH:i')) }}"></div>
        <div class="field"><label>Selesai</label><input class="input" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($exam->ends_at)->format('Y-m-d\TH:i')) }}"></div>
        <div class="field"><label>Durasi (menit)</label><input class="input" type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes ?: 90) }}" min="1" required></div>
    </div>

    <div class="card" style="box-shadow:none;background:#fafafa">
        <h3>Aturan Paket Soal</h3>
        <div class="row">
            <label class="row"><input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $exam->shuffle_questions))> Acak urutan soal</label>
            <label class="row"><input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options', $exam->shuffle_options))> Acak urutan opsi/bank pasangan</label>
        </div>
        <p class="help">Kunci jawaban tetap hanya tersimpan di server, tidak ikut dikirim ke aplikasi siswa.</p>
    </div>


    @if($exam->exists)
        <div class="card" style="box-shadow:none;background:#fbfbff;border-color:#dbe3ff">
            <div class="between">
                <div>
                    <h3>Mode Ujian Offline & Kunci Aplikasi</h3>
                    <p class="muted small mb0">Konfigurasi ini diambil dari default sekolah saat ujian dibuat.</p>
                </div>
                <span class="badge warning">Edit hati-hati</span>
            </div>

            <div class="field">
                <label>Mode Kunci Android</label>
                <select class="input" name="lock_mode">
                    @foreach(($lockModes ?? \App\Models\Exam::LOCK_MODES) as $value => $label)
                        <option value="{{ $value }}" @selected(old('lock_mode', $exam->lock_mode ?: \App\Models\Exam::LOCK_STRICT_AIRPLANE) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="help">Jika siswa keluar paksa atau internet aktif saat mengerjakan, mobile mengunci layar ujian, membunyikan alarm/notifikasi, dan mencatat event integritas.</p>
            </div>
        </div>
    @endif

    @if($exam->exists)
        <button class="btn primary">Simpan Konfigurasi</button>
    @else
        <button class="btn primary">Simpan & Pilih Bank Soal</button>
    @endif
</form>
@endsection
