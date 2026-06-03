@extends('layouts.app', ['title' => 'Ganti Password'])

@section('content')
<div class="between mb"><div><h1>Ganti Password</h1><p class="muted">Untuk keamanan produksi, akun dengan password awal wajib mengganti password sebelum memakai sistem.</p></div></div>
<div class="card" style="max-width:640px">
<form class="form" method="POST" action="{{ route('profile.password.update') }}">
    @csrf
    <div class="field"><label>Password Lama</label><input class="input" type="password" name="current_password" required></div>
    <div class="field"><label>Password Baru</label><input class="input" type="password" name="password" required></div>
    <div class="field"><label>Konfirmasi Password Baru</label><input class="input" type="password" name="password_confirmation" required></div>
    <button class="btn primary">Simpan Password Baru</button>
</form>
</div>
@endsection
