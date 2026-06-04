@extends('layouts.app', ['title' => 'Akun Pengguna'])

@section('content')
<div class="between mb">
    <div><h1>Akun Pengguna</h1><p class="muted">Kelola akun admin, guru, dan pengawas. Guru dapat dibuat otomatis dari data guru SILAP.</p></div>
    <a class="btn primary" href="{{ route('accounts.create') }}">Buat Akun</a>
</div>

<div class="two mb">
    <div class="card">
        <h2>Generate Akun Guru</h2>
        <p class="muted">Username guru memakai NIP. Password awal bisa diganti guru setelah login.</p>
        <form class="form" method="POST" action="{{ route('accounts.generateTeacherAccounts') }}">
            @csrf
            <div class="field"><label>Password Awal Guru</label><input class="input" name="default_password" value="Guru@123"></div>
            <label class="row small"><input type="checkbox" name="only_without_account" value="1" checked> Hanya guru yang belum punya akun</label>
            <button class="btn primary">Generate Akun Guru</button>
        </form>
    </div>
    <div class="card">
        <h2>Role Produksi</h2>
        <div class="check-list">
            <div class="check-pill"><span class="badge danger">Admin</span> Data master, akun, pengaturan, semua ujian</div>
            <div class="check-pill"><span class="badge info">Guru</span> Bank soal, ujian miliknya, hasil ujian miliknya</div>
            <div class="check-pill"><span class="badge warning">Pengawas</span> Monitor pelaksanaan ujian</div>
        </div>
    </div>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title"><h2>Live Table Akun</h2><p class="muted small mb0">Menampilkan {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} akun.</p></div>
        <form class="table-tools" method="GET" action="{{ route('accounts.index') }}">
            <div class="tool-field"><label>Role</label><select class="input" name="role" onchange="this.form.submit()"><option value="">Semua</option>@foreach(\App\Models\User::ROLES as $value => $label)<option value="{{ $value }}" @selected(request('role')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="tool-field search"><label>Live Search</label><div class="live-search-wrap"><input class="input" data-live-search="accountsTable" name="q" value="{{ request('q') }}" placeholder="Cari nama, username, email"></div></div>
            <button class="btn primary">Cari</button>
            <button class="btn" type="button" data-live-reset="accountsTable">Clear</button>
        </form>
    </div>
    <div class="table-wrap"><table class="table" id="accountsTable">
        <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse($users as $user)
            <tr>
                <td><b>{{ $user->name }}</b><br><span class="muted small">{{ $user->phone ?: '-' }}</span></td>
                <td>{{ $user->username ?: '-' }}</td>
                <td>{{ $user->email }}</td>
                <td><span class="badge info">{{ \App\Models\User::ROLES[$user->role] ?? $user->role }}</span></td>
                <td><span class="badge {{ $user->is_active ? 'active' : 'inactive' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>@if($user->must_change_password)<br><span class="badge warning">Wajib ganti password</span>@endif</td>
                <td>{{ optional($user->last_login_at)->format('d M Y H:i') ?: '-' }}</td>
                <td class="row"><a class="btn soft" href="{{ route('accounts.edit', $user) }}">Edit</a>@if($user->id !== auth()->id())<form method="POST" action="{{ route('accounts.destroy', $user) }}" onsubmit="return confirm('Hapus akun ini?')">@csrf @method('DELETE')<button class="btn danger">Hapus</button></form>@endif</td>
            </tr>
        @empty
            <tr data-empty-row><td colspan="7">Belum ada akun.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="table-meta between"><div class="small muted">Terlihat: <b data-live-count="accountsTable">{{ $users->count() }}</b> baris</div><div>{{ $users->links() }}</div></div>
</div>
@endsection
