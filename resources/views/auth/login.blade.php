@extends('layouts.app', ['title' => 'Login'])

@push('head')
<style>
.login-brand{display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:2rem}
.login-brand-logo{
    width:54px;height:54px;border-radius:16px;
    background:linear-gradient(135deg,var(--primary),var(--accent));
    display:grid;place-items:center;color:#fff;font-weight:950;font-size:22px;
    box-shadow:0 12px 28px rgba(20,184,166,.3);
}
.login-brand-text{font-size:22px;font-weight:950;color:var(--heading)}
.login-card{
    background:#fff;
    border:1px solid var(--line);
    border-radius:16px;
    padding:2.25rem 2rem;
    box-shadow:0 20px 50px rgba(15,23,42,.10);
}
.login-title{font-size:24px;font-weight:950;color:var(--heading);margin:0 0 .35rem;text-align:center}
.login-sub{color:var(--muted);font-size:13px;text-align:center;margin-bottom:1.75rem}
.login-input{
    width:100%;border:1.5px solid var(--line-strong);border-radius:12px;
    padding:.82rem 1rem;background:#fff;color:var(--text);
    font:inherit;font-size:15px;transition:.18s ease;
}
.login-input:focus{outline:0;border-color:var(--primary);box-shadow:0 0 0 4px rgba(20,184,166,.14)}
.login-label{display:block;font-weight:900;color:var(--heading);font-size:13px;margin-bottom:.4rem}
.login-remember{display:flex;align-items:center;gap:.5rem;font-size:13px;color:var(--muted);cursor:pointer}
.login-remember input{width:auto}
.login-btn{
    width:100%;border:0;border-radius:12px;padding:.9rem;
    background:linear-gradient(135deg,var(--primary),#10b981);
    color:#fff;font-weight:950;font-size:15px;cursor:pointer;
    box-shadow:0 8px 20px rgba(20,184,166,.3);
    transition:transform .18s,box-shadow .18s;
}
.login-btn:hover{transform:translateY(-1px);box-shadow:0 14px 28px rgba(20,184,166,.38)}
.login-btn:active{transform:translateY(0)}
.login-hint{background:linear-gradient(135deg,#f0fdf9,#fff7f9);border:1px solid var(--line);border-radius:10px;padding:.75rem 1rem;margin-top:1.35rem}
.login-hint p{margin:0;font-size:12px;color:var(--muted);line-height:1.5}
.login-hint b{color:var(--heading)}
</style>
@endpush

@section('content')

<div class="login-brand">
    <div class="login-brand-logo">US</div>
    <div>
        <div class="login-brand-text">Ujian Sekolah</div>
        <div style="font-size:12px;color:var(--muted);font-weight:700">Sistem Ujian Semi-Online</div>
    </div>
</div>

<div class="login-card">
    <h1 class="login-title">Selamat Datang 👋</h1>
    <p class="login-sub">Masuk ke ruang kendali ujian sekolah</p>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div style="margin-bottom:1rem">
            <label class="login-label">Email / Username / NIP</label>
            <input class="login-input" name="login" value="{{ old('login') }}"
                   placeholder="admin@sekolah.id · username · NIP"
                   autocomplete="username" required autofocus>
        </div>
        <div style="margin-bottom:1.25rem">
            <label class="login-label">Password</label>
            <div style="position:relative">
                <input class="login-input" type="password" id="loginPassword" name="password"
                       placeholder="••••••••" autocomplete="current-password"
                       required style="padding-right:4.5rem">
                <button type="button" id="togglePwd" onclick="togglePassword()"
                        style="position:absolute;right:.55rem;top:50%;transform:translateY(-50%);
                               border:0;border-radius:8px;cursor:pointer;
                               background:var(--primary-soft);color:var(--primary-strong);
                               font-size:12px;font-weight:900;padding:.4rem .6rem;line-height:1">
                    Lihat
                </button>
            </div>
        </div>
        <div style="margin-bottom:1.35rem">
            <label class="login-remember">
                <input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini
            </label>
        </div>
        <button class="login-btn" type="submit">Masuk ke Dashboard</button>
    </form>

    <div class="login-hint">
        <p>
            <b>Admin</b>: gunakan email yang didaftarkan saat setup. &nbsp;
            <b>Guru/Pengawas</b>: gunakan username atau NIP yang dibuat admin.
        </p>
    </div>
</div>

@endsection

@push('scripts')
<script>
function togglePassword(){
    const input = document.getElementById('loginPassword');
    const btn   = document.getElementById('togglePwd');
    const isHidden = input.type === 'password';
    input.type  = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? 'Sembunyi' : 'Lihat';
}
</script>
@endpush
