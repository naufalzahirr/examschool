@extends('layouts.app', ['title' => 'Login'])

@section('content')
<div class="auth-logo"><span class="brand-logo">US</span><span>Backend Ujian</span></div>
<div class="card">
    <h1>Masuk</h1>
    <p class="muted">Admin bisa memakai email. Guru/pengawas bisa memakai username/NIP yang dibuat admin.</p>
    <form method="POST" action="{{ route('login.store') }}" class="form">
        @csrf
        <div class="field"><label>Email / Username / NIP</label><input class="input" name="login" value="{{ old('login') }}" placeholder="admin@sekolah.test / 198xxxxx" autocomplete="username" required autofocus></div>
        <div class="field">
            <label>Password</label>
            <div style="position:relative">
                <input class="input" type="password" id="loginPassword" name="password" placeholder="Password" autocomplete="current-password" required style="padding-right:3rem">
                <button type="button" id="togglePwd" onclick="togglePassword()" style="position:absolute;right:.5rem;top:50%;transform:translateY(-50%);border:0;border-radius:8px;cursor:pointer;color:var(--primary-strong);background:var(--primary-soft);font-size:12px;font-weight:900;line-height:1;padding:.45rem .55rem">Lihat</button>
            </div>
        </div>
        <label class="row small"><input type="checkbox" name="remember" value="1"> Ingat perangkat ini</label>
        <button class="btn primary">Login</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
function togglePassword(){
    const input = document.getElementById('loginPassword');
    const btn = document.getElementById('togglePwd');
    if(input.type === 'password'){
        input.type = 'text';
        btn.textContent = 'Sembunyi';
    } else {
        input.type = 'password';
        btn.textContent = 'Lihat';
    }
}
</script>
@endpush
