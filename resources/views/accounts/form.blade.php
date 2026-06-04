@extends('layouts.app', ['title' => $account->exists ? 'Edit Akun' : 'Buat Akun'])

@section('content')
<div class="between mb"><div><h1>{{ $account->exists ? 'Edit Akun' : 'Buat Akun' }}</h1><p class="muted">Gunakan untuk admin tambahan, guru manual, atau pengawas ujian.</p></div><a class="btn" href="{{ route('accounts.index') }}">Kembali</a></div>
<div class="card">
    <form class="form" method="POST" action="{{ $account->exists ? route('accounts.update', $account) : route('accounts.store') }}">
        @csrf @if($account->exists) @method('PUT') @endif
        <div class="two"><div class="field"><label>Nama</label><input class="input" name="name" value="{{ old('name', $account->name) }}" required></div><div class="field"><label>Role</label><select class="input" name="role">@foreach(\App\Models\User::ROLES as $value => $label)<option value="{{ $value }}" @selected(old('role', $account->role)===$value)>{{ $label }}</option>@endforeach</select></div></div>
        <div class="two"><div class="field"><label>Email</label><input class="input" type="email" name="email" value="{{ old('email', $account->email) }}" required></div><div class="field"><label>Username / NIP</label><input class="input" name="username" value="{{ old('username', $account->username) }}" placeholder="boleh kosong untuk admin email"></div></div>
        <div class="two"><div class="field"><label>No HP</label><input class="input" name="phone" value="{{ old('phone', $account->phone) }}"></div><div class="field"><label>Password {{ $account->exists ? '(kosongkan jika tidak diubah)' : '' }}</label><input class="input" type="password" name="password" minlength="8" autocomplete="new-password" {{ $account->exists ? '' : 'required' }}><p class="help">Minimal 8 karakter. Centang wajib ganti password untuk akun awal.</p></div></div>
        <div class="row"><label class="check-pill"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $account->is_active ?? true))> Akun aktif</label><label class="check-pill"><input type="checkbox" name="must_change_password" value="1" @checked(old('must_change_password', $account->must_change_password))> Wajib ganti password</label></div>
        <div class="row"><button class="btn primary">Simpan</button><a class="btn" href="{{ route('accounts.index') }}">Batal</a></div>
    </form>
</div>
@endsection
