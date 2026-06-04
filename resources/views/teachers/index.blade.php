@extends('layouts.app', ['title' => 'Data Guru'])

@section('content')
<div class="between mb">
    <div>
        <h1>Data Guru</h1>
        <p class="muted">Data guru mengikuti format SILAP. Search sekarang menyatu dengan tabel seperti live table.</p>
    </div>
    <div class="row"><a class="btn soft" href="{{ route('silap.index') }}">Sinkron dari SILAP</a><a class="btn primary" href="{{ route('accounts.index') }}">Kelola Akun Guru</a></div>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Daftar Guru</h2>
            <p class="muted small mb0">Menampilkan {{ $teachers->firstItem() ?? 0 }}-{{ $teachers->lastItem() ?? 0 }} dari {{ $teachers->total() }} guru.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('teachers.index') }}">
            <div class="tool-field search">
                <label>Live Search</label>
                <div class="live-search-wrap">
                    <input class="input" data-live-search="teachersTable" name="q" value="{{ request('q') }}" placeholder="Cari NIP, nama, atau kontak">
                </div>
            </div>
            <button class="btn primary">Cari</button>
            @if(request('q'))
                <a class="btn" href="{{ route('teachers.index') }}">Reset</a>
            @else
                <button class="btn" type="button" data-live-reset="teachersTable">Clear</button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="table" id="teachersTable">
            <thead><tr><th>NIP/NRPTK</th><th>Nama</th><th>JK</th><th>Kontak</th><th>Akun Login</th><th>SILAP</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($teachers as $teacher)
                <tr>
                    <td><span class="badge">{{ $teacher->nip ?: '-' }}</span></td>
                    <td><b>{{ $teacher->name }}</b></td>
                    <td>{{ $teacher->jenis_kelamin ?: '-' }}</td>
                    <td>{{ $teacher->kontak ?: '-' }}</td>
                    <td>@if($teacher->user)<span class="badge active">{{ $teacher->user->username ?: $teacher->user->email }}</span>@else<span class="badge warning">Belum ada akun</span>@endif</td>
                    <td class="small">{{ $teacher->silap_id ? 'ID '.$teacher->silap_id : 'manual' }}</td>
                    <td><span class="badge {{ $teacher->is_active ? 'active' : 'inactive' }}">{{ $teacher->is_active ? 'aktif' : 'nonaktif' }}</span></td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="7">Belum ada data guru.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-meta between">
        <div class="small muted">Terlihat di halaman ini: <b data-live-count="teachersTable">{{ $teachers->count() }}</b> baris</div>
        <div>{{ $teachers->links() }}</div>
    </div>
</div>
@endsection
