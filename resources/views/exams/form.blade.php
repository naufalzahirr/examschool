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
                <p class="muted small mb0">Jadwal menentukan kapan unlock key diberikan ke aplikasi siswa.</p>
            </div>
        </div>
        <div class="three">
            <div class="field"><label>Mulai</label><input class="input" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($exam->starts_at)->format('Y-m-d\TH:i')) }}"></div>
            <div class="field"><label>Selesai</label><input class="input" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($exam->ends_at)->format('Y-m-d\TH:i')) }}"></div>
            <div class="field"><label>Durasi Pengerjaan (menit)</label><input class="input" type="number" name="duration_minutes" value="{{ old('duration_minutes', $exam->duration_minutes ?: 90) }}" min="1" required></div>
        </div>
        <div class="row">
            <label class="check-pill"><input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $exam->shuffle_questions))> Acak urutan soal</label>
            <label class="check-pill"><input type="checkbox" name="shuffle_options" value="1" @checked(old('shuffle_options', $exam->shuffle_options))> Acak urutan opsi jawaban</label>
        </div>
        <p class="help">Kunci jawaban tetap hanya tersimpan di server, tidak ikut dikirim ke aplikasi siswa.</p>
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
