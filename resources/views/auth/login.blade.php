@extends('layouts.app', ['title' => 'Login'])

@section('content')
<div class="auth-logo"><span class="brand-logo">US</span><span>Backend Ujian</span></div>
<div class="card">
    <h1>Masuk</h1>
    <p class="muted">Admin bisa memakai email. Guru/pengawas bisa memakai username/NIP yang dibuat admin.</p>
    <form method="POST" action="{{ route('login.store') }}" class="form">
        @csrf
        <div class="field"><label>Email / Username / NIP</label><input class="input" name="login" value="{{ old('login') }}" placeholder="admin@sekolah.test / 198xxxxx" autocomplete="username" required autofocus></div>
        <div class="field"><label>Password</label><input class="input" type="password" name="password" placeholder="Password" autocomplete="current-password" required></div>
        <label class="row small"><input type="checkbox" name="remember" value="1"> Ingat perangkat ini</label>
        <button class="btn primary">Login</button>
    </form>
</div>
@endsection
