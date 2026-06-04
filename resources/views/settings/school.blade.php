@extends('layouts.app', ['title' => 'Pengaturan Sekolah'])

@section('content')
<div class="between mb">
    <div>
        <h1>Pengaturan Sekolah</h1>
        <p class="muted">Konfigurasi identitas sekolah, jadwal download, keamanan ujian, dan akun default.</p>
    </div>
</div>

<form class="form" method="POST" action="{{ route('settings.school.update') }}">
@csrf

{{-- ===== IDENTITAS SEKOLAH ===== --}}
<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Identitas Sekolah</h2>
            <p class="muted small mb0">Tampil di laporan dan header halaman.</p>
        </div>
    </div>
    <div class="two">
        <div class="field">
            <label>Nama Sekolah</label>
            <input class="input" name="school_name" value="{{ old('school_name', $settings['school_name']) }}" required>
        </div>
        <div class="field">
            <label>Logo URL / Path</label>
            <input class="input" name="school_logo" value="{{ old('school_logo', $settings['school_logo']) }}" placeholder="Opsional">
        </div>
    </div>
    <div class="field">
        <label>Alamat Sekolah</label>
        <textarea class="input" name="school_address" rows="2">{{ old('school_address', $settings['school_address']) }}</textarea>
    </div>
    <div class="three">
        <div class="field">
            <label>Tahun Ajaran</label>
            <input class="input" name="academic_year" value="{{ old('academic_year', $settings['academic_year']) }}" placeholder="2024/2025" required>
        </div>
        <div class="field">
            <label>Semester</label>
            <input class="input" name="semester" value="{{ old('semester', $settings['semester']) }}" placeholder="Ganjil" required>
        </div>
        <div class="field">
            <label>Zona Waktu</label>
            <input class="input" name="timezone" value="{{ old('timezone', $settings['timezone']) }}" list="tzOptions" placeholder="Asia/Jakarta" required>
            <datalist id="tzOptions">
                <option value="Asia/Jakarta">
                <option value="Asia/Makassar">
                <option value="Asia/Jayapura">
            </datalist>
            <p class="help">Wajib sesuai zona sekolah agar jadwal buka/tutup ujian tidak meleset.</p>
        </div>
    </div>
    <div class="two">
        <div class="field">
            <label>Kepala Sekolah</label>
            <input class="input" name="principal_name" value="{{ old('principal_name', $settings['principal_name']) }}">
        </div>
        <div class="field">
            <label>Proktor / Penanggung Jawab</label>
            <input class="input" name="proctor_name" value="{{ old('proctor_name', $settings['proctor_name']) }}">
        </div>
    </div>
</div>

{{-- ===== JADWAL & ANTREAN DOWNLOAD ===== --}}
<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Jadwal & Antrean Download Paket Soal</h2>
            <p class="muted small mb0">Mengatur kapan dan berapa banyak siswa bisa mengunduh paket soal terenkripsi sebelum ujian.</p>
        </div>
    </div>
    <div class="alert info" style="margin-bottom:1rem">
        Alur download: Siswa bisa download paket soal <b>N jam sebelum ujian mulai</b>.
        Jumlah download bersamaan dibatasi agar server tidak kewalahan.
        Saat jam ujian tiba, server memberikan unlock key kecil — siswa wajib mode pesawat sebelum menjawab.
    </div>
    <div class="two">
        <div class="field">
            <label>Buka Download Sebelum Ujian (jam)</label>
            <input class="input" type="number" name="package_download_open_hours" min="0" max="168"
                   value="{{ old('package_download_open_hours', $settings['package_download_open_hours']) }}" required>
            <p class="help">Default 12 jam. Siswa bisa download mulai N jam sebelum <code>starts_at</code>.</p>
        </div>
        <div class="field">
            <label>Slot Download Bersamaan</label>
            <input class="input" type="number" name="package_download_concurrent_limit" min="1" max="1000"
                   value="{{ old('package_download_concurrent_limit', $settings['package_download_concurrent_limit']) }}" required>
            <p class="help">Berapa HP yang bisa mengunduh paket secara bersamaan. Default 50.</p>
        </div>
    </div>
    <div class="two">
        <div class="field">
            <label>Masa Berlaku Slot Download (menit)</label>
            <input class="input" type="number" name="package_queue_slot_ttl_minutes" min="1" max="60"
                   value="{{ old('package_queue_slot_ttl_minutes', $settings['package_queue_slot_ttl_minutes']) }}" required>
            <p class="help">Slot yang tidak dipakai dalam waktu ini akan dibebaskan untuk siswa lain.</p>
        </div>
        <div class="field">
            <label>Maks. Percobaan Download per Siswa</label>
            <input class="input" type="number" name="package_download_max_attempts" min="1" max="20"
                   value="{{ old('package_download_max_attempts', $settings['package_download_max_attempts']) }}" required>
            <p class="help">Setelah batas ini tercapai, siswa perlu di-reset oleh pengawas.</p>
        </div>
    </div>
    <div class="two">
        <div class="field">
            <label>Toleransi Terlambat Login (menit)</label>
            <input class="input" type="number" name="late_tolerance_minutes" min="0" max="240"
                   value="{{ old('late_tolerance_minutes', $settings['late_tolerance_minutes']) }}" required>
            <p class="help">Berapa menit setelah <code>starts_at</code> siswa masih bisa login dan mulai ujian.</p>
        </div>
        <div class="field">
            <label>Grace Upload Jawaban (menit)</label>
            <input class="input" type="number" name="upload_grace_minutes" min="0" max="240"
                   value="{{ old('upload_grace_minutes', $settings['upload_grace_minutes']) }}" required>
            <p class="help">Berapa menit setelah <code>ends_at</code> siswa masih bisa mengupload jawaban.</p>
        </div>
    </div>
</div>

{{-- ===== MODE KEAMANAN DEFAULT ===== --}}
<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Mode Keamanan Ujian Default</h2>
            <p class="muted small mb0">Dipakai saat membuat ujian baru. Setiap ujian bisa diubah secara individual dari form ujian.</p>
        </div>
    </div>
    <div class="two">
        <div class="field">
            <label>Mode Kunci Layar Default</label>
            <select class="input" name="default_exam_lock_mode">
                <option value="strict_airplane" @selected(old('default_exam_lock_mode', $settings['default_exam_lock_mode'])==='strict_airplane')>
                    Ketat / Mode Pesawat wajib (Rekomendasi)
                </option>
                <option value="standard" @selected(old('default_exam_lock_mode', $settings['default_exam_lock_mode'])==='standard')>
                    Standar / BYOD: deteksi pelanggaran saja
                </option>
                <option value="strict_kiosk" @selected(old('default_exam_lock_mode', $settings['default_exam_lock_mode'])==='strict_kiosk')>
                    Ketat / Kiosk: coba kunci tombol Home/Recent
                </option>
            </select>
        </div>
        <div class="field">
            <label>Maks. Toleransi Pelanggaran Keluar</label>
            <input class="input" type="number" name="exit_violation_max_allowed" min="0" max="50"
                   value="{{ old('exit_violation_max_allowed', $settings['exit_violation_max_allowed']) }}" required>
            <p class="help">Berapa kali siswa boleh keluar aplikasi sebelum ujian dikunci permanen. 0 = tidak ada toleransi.</p>
        </div>
    </div>
    <div class="alert warning" style="margin-bottom:0">
        <b>Rekomendasi produksi:</b> gunakan Mode Pesawat Wajib. Siswa mengambil unlock key saat jadwal dibuka, lalu wajib aktifkan mode pesawat sebelum menjawab.
        Jika internet aktif atau keluar aplikasi, ujian langsung terkunci di mobile dan pelanggaran tercatat otomatis.
    </div>
</div>

{{-- ===== AKUN & PASSWORD ===== --}}
<div class="card mb">
    <div class="between mb">
        <div>
            <h2 class="mb0">Akun & Password Default</h2>
            <p class="muted small mb0">Pengaturan password untuk akun yang baru dibuat.</p>
        </div>
    </div>
    <div class="two">
        <div class="field">
            <label>Password Awal Guru Default</label>
            <input class="input" type="password" name="default_teacher_password"
                   value="{{ old('default_teacher_password', $settings['default_teacher_password']) }}"
                   minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter">
            <p class="help">Dipakai saat generate akun guru massal. Guru wajib ganti password setelah login pertama.</p>
        </div>
        <div class="field">
            <label>Mode Password Awal Siswa</label>
            <select class="input" name="default_student_password_mode">
                <option value="custom" @selected(old('default_student_password_mode', $settings['default_student_password_mode'])==='custom')>
                    Dari proses sinkron / import (Produksi)
                </option>
                <option value="nis" @selected(old('default_student_password_mode', $settings['default_student_password_mode'])==='nis')>
                    Gunakan NIS sebagai password (Hanya development)
                </option>
            </select>
            <p class="help">Di production, mode NIS akan ditolak saat disimpan.</p>
        </div>
    </div>
</div>

{{-- ===== TOMBOL SIMPAN ===== --}}
<div class="card">
    <div class="between">
        <div>
            <b>Simpan Semua Pengaturan</b>
            <p class="muted small mb0">Perubahan langsung berlaku untuk ujian yang dibuat setelahnya. Ujian yang sudah ada tetap memakai konfigurasi masing-masing.</p>
        </div>
        <button class="btn primary">Simpan Pengaturan</button>
    </div>
</div>

</form>
@endsection
