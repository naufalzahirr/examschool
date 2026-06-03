@extends('layouts.app', ['title' => 'Pengaturan Sekolah'])

@section('content')
<div class="between mb"><div><h1>Pengaturan Sekolah</h1><p class="muted">Identitas sekolah dan aturan default ujian untuk laporan, monitor, dan aplikasi siswa.</p></div></div>
<div class="card">
<form class="form" method="POST" action="{{ route('settings.school.update') }}">
@csrf
<div class="two"><div class="field"><label>Nama Sekolah</label><input class="input" name="school_name" value="{{ old('school_name', $settings['school_name']) }}" required></div><div class="field"><label>Logo URL/Path</label><input class="input" name="school_logo" value="{{ old('school_logo', $settings['school_logo']) }}"></div></div>
<div class="field"><label>Alamat Sekolah</label><textarea class="input" name="school_address" rows="2">{{ old('school_address', $settings['school_address']) }}</textarea></div>
<div class="three"><div class="field"><label>Tahun Ajaran</label><input class="input" name="academic_year" value="{{ old('academic_year', $settings['academic_year']) }}"></div><div class="field"><label>Semester</label><input class="input" name="semester" value="{{ old('semester', $settings['semester']) }}"></div><div class="field"><label>Zona Waktu</label><input class="input" name="timezone" value="{{ old('timezone', $settings['timezone']) }}"></div></div>
<div class="two"><div class="field"><label>Kepala Sekolah</label><input class="input" name="principal_name" value="{{ old('principal_name', $settings['principal_name']) }}"></div><div class="field"><label>Proktor/Penanggung Jawab</label><input class="input" name="proctor_name" value="{{ old('proctor_name', $settings['proctor_name']) }}"></div></div>
<div class="three"><div class="field"><label>Toleransi Terlambat Login (menit)</label><input class="input" type="number" name="late_tolerance_minutes" value="{{ old('late_tolerance_minutes', $settings['late_tolerance_minutes']) }}"></div><div class="field"><label>Grace Upload Jawaban (menit)</label><input class="input" type="number" name="upload_grace_minutes" value="{{ old('upload_grace_minutes', $settings['upload_grace_minutes']) }}"></div><div class="field"><label>Password Awal Guru Default</label><input class="input" name="default_teacher_password" value="{{ old('default_teacher_password', $settings['default_teacher_password']) }}"></div></div>
<hr style="border:0;border-top:1px solid var(--line);margin:1.25rem 0">
<h2>Antrean Download Paket Soal</h2>
<p class="muted small">Default produksi: siswa boleh download paket terenkripsi 12 jam sebelum ujian. Saat jam mulai, aplikasi hanya minta unlock key kecil dari server.</p>
<div class="four" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem">
    <div class="field"><label>Buka Download Sebelum Ujian (jam)</label><input class="input" type="number" name="package_download_open_hours" value="{{ old('package_download_open_hours', $settings['package_download_open_hours']) }}"></div>
    <div class="field"><label>Slot Download Bersamaan</label><input class="input" type="number" name="package_download_concurrent_limit" value="{{ old('package_download_concurrent_limit', $settings['package_download_concurrent_limit']) }}"></div>
    <div class="field"><label>Masa Slot Download (menit)</label><input class="input" type="number" name="package_queue_slot_ttl_minutes" value="{{ old('package_queue_slot_ttl_minutes', $settings['package_queue_slot_ttl_minutes']) }}"></div>
    <div class="field"><label>Maks. Percobaan Download</label><input class="input" type="number" name="package_download_max_attempts" value="{{ old('package_download_max_attempts', $settings['package_download_max_attempts']) }}"></div>
</div>

<hr style="border:0;border-top:1px solid var(--line);margin:1.25rem 0">
<h2>Mode Ujian Offline & Kunci Aplikasi</h2>
<p class="muted small">Default ini akan dipakai saat membuat ujian baru. Ujian lama tetap memakai konfigurasi masing-masing.</p>
<div class="four" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem">
    <div class="field">
        <label>Mode Kunci Default</label>
        <select class="input" name="default_exam_lock_mode">
            <option value="standard" @selected(old('default_exam_lock_mode', $settings['default_exam_lock_mode'])==='standard')>Standar / deteksi pelanggaran</option>
            <option value="strict_kiosk" @selected(old('default_exam_lock_mode', $settings['default_exam_lock_mode'])==='strict_kiosk')>Ketat / coba kiosk lock</option>
        </select>
    </div>
    <div class="field">
        <label>Aturan Keluar Default</label>
        <select class="input" name="default_exam_exit_policy">
            <option value="proctor_code" @selected(old('default_exam_exit_policy', $settings['default_exam_exit_policy'])==='proctor_code')>Kode pengawas</option>
            <option value="after_submit" @selected(old('default_exam_exit_policy', $settings['default_exam_exit_policy'])==='after_submit')>Setelah submit</option>
            <option value="after_time_end" @selected(old('default_exam_exit_policy', $settings['default_exam_exit_policy'])==='after_time_end')>Setelah waktu habis</option>
        </select>
    </div>
    <div class="field"><label>Panjang Kode Keluar</label><input class="input" type="number" name="offline_exit_code_length" value="{{ old('offline_exit_code_length', $settings['offline_exit_code_length']) }}"></div>
    <div class="field"><label>Maks. Toleransi Pelanggaran Keluar</label><input class="input" type="number" name="exit_violation_max_allowed" value="{{ old('exit_violation_max_allowed', $settings['exit_violation_max_allowed']) }}"></div>
</div>
<div class="alert warning" style="margin-bottom:0">
    Untuk perangkat Android pribadi siswa, mode kunci penuh tidak bisa dijamin seperti iOS Guided Access/SEB. Produksi paling aman: mode offline, paket terenkripsi, kode keluar pengawas, dan log pelanggaran lokal yang dikirim bersama jawaban.
</div>

<div class="field"><label>Mode Password Awal Siswa</label><select class="input" name="default_student_password_mode"><option value="nis" @selected(old('default_student_password_mode', $settings['default_student_password_mode'])==='nis')>Gunakan NIS masing-masing</option><option value="custom" @selected(old('default_student_password_mode', $settings['default_student_password_mode'])==='custom')>Custom dari proses sinkron/import</option></select></div>
<div class="row"><button class="btn primary">Simpan Pengaturan</button></div>
</form>
</div>
@endsection
