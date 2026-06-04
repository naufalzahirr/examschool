@extends('layouts.app', ['title' => $student->exists ? 'Edit Siswa' : 'Tambah Siswa'])

@section('content')
<div class="between mb">
    <div>
        <h1>{{ $student->exists ? 'Edit Akun Siswa' : 'Tambah Akun Siswa' }}</h1>
        <p class="muted">Password ini dipakai siswa untuk login di aplikasi ujian Android. Kelas diambil dari data classrooms SILAP.</p>
    </div>
    <a class="btn" href="{{ route('students.index') }}">Kembali</a>
</div>

<form class="card form" method="POST" action="{{ $student->exists ? route('students.update', $student) : route('students.store') }}">
    @csrf
    @if($student->exists) @method('PUT') @endif

    <div class="two">
        <div class="field"><label>NIS</label><input class="input" name="nis" value="{{ old('nis', $student->nis) }}" placeholder="Contoh: 4728" required></div>
        <div class="field">
            <label>Kelas</label>
            <select class="input" name="classroom_id">
                <option value="">Pilih kelas</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected((string) old('classroom_id', $student->classroom_id) === (string) $classroom->id)>{{ $classroom->nama_kelas }} · ID {{ $classroom->id }}</option>
                @endforeach
            </select>
            @if($classrooms->isEmpty())<p class="help">Belum ada data kelas. Isi menu Kelas dulu.</p>@endif
        </div>
    </div>

    <div class="field">
        <label>Nama Lengkap</label>
        <input class="input" name="name" value="{{ old('name', $student->name) }}" required>
    </div>

    <div class="three">
        <div class="field"><label>Jenis Kelamin</label><select class="input" name="jenis_kelamin"><option value="">-</option><option value="L" @selected(old('jenis_kelamin', $student->jenis_kelamin)==='L')>Laki-laki</option><option value="P" @selected(old('jenis_kelamin', $student->jenis_kelamin)==='P')>Perempuan</option></select></div>
        <div class="field"><label>Kontak</label><input class="input" name="kontak" value="{{ old('kontak', $student->kontak) }}"></div>
        <div class="field"><label>Status</label><label class="row" style="padding-top:.6rem"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $student->exists ? $student->is_active : true))> Akun aktif</label></div>
    </div>

    <div class="field"><label>Alamat</label><textarea name="alamat" rows="3">{{ old('alamat', $student->alamat) }}</textarea></div>

    <div class="two">
        <div class="field"><label>{{ $student->exists ? 'Password Baru (kosongkan jika tidak diganti)' : 'Password' }}</label><input class="input" type="password" name="password" minlength="8" autocomplete="new-password" {{ $student->exists ? '' : 'required' }}><p class="help">Minimal 8 karakter. Hindari memakai NIS sebagai password produksi.</p></div>
        <div class="field"><label>Konfirmasi Password</label><input class="input" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" {{ $student->exists ? '' : 'required' }}></div>
    </div>

    @if($student->silap_id)
        <div class="alert info" style="margin-bottom:0">Data ini tersinkron dari SILAP ID {{ $student->silap_id }}. Perubahan manual bisa tertimpa saat sinkron berikutnya.</div>
    @endif

    <button class="btn primary">Simpan Akun</button>
</form>
@endsection
