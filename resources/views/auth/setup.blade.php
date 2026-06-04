@extends('layouts.app', ['title' => 'Setup Admin'])

@section('content')
<div class="card" style="max-width:520px;margin:40px auto;">
    <h1>Setup Admin Pertama</h1>
    <p class="muted">Buat akun admin untuk mengelola ujian sekolah.</p>
    <form method="POST" action="{{ route('setup.store') }}" class="form">
        @csrf
        <div class="field"><label>Nama</label><input class="input" name="name" value="{{ old('name') }}" required></div>
        <div class="field"><label>Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required></div>
        <div class="field"><label>Password</label><input class="input" type="password" name="password" minlength="8" autocomplete="new-password" required><p class="help">Minimal 8 karakter. Pakai password berbeda dari akun lain.</p></div>
        <div class="field"><label>Konfirmasi Password</label><input class="input" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></div>
        <button class="btn primary" style="width:100%;">Buat Admin</button>
    </form>
</div>
@endsection
