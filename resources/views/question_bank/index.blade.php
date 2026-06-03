@extends('layouts.app', ['title' => 'Bank Soal'])

@section('content')
<div class="between mb">
    <div><h1>Bank Soal</h1><p class="muted">Kumpulan soal reusable. Soal bisa dibuat pribadi atau dibagikan sebagai bank soal sekolah untuk dipakai guru lain.</p></div>
    <div class="row"><a class="btn" href="{{ route('question-bank.import') }}">Import</a><a class="btn primary" href="{{ route('question-bank.create') }}">+ Buat Soal</a></div>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title"><h2>Live Table Bank Soal</h2><p class="muted small mb0">Menampilkan {{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} soal.</p></div>
        <form class="table-tools" method="GET" action="{{ route('question-bank.index') }}">
            <div class="tool-field"><label>Jenis</label><select class="input" name="type" onchange="this.form.submit()"><option value="">Semua</option>@foreach($filters['types'] as $value=>$label)<option value="{{ $value }}" @selected(request('type')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="tool-field"><label>Level</label><select class="input" name="difficulty" onchange="this.form.submit()"><option value="">Semua</option>@foreach($filters['difficulties'] as $value=>$label)<option value="{{ $value }}" @selected(request('difficulty')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="tool-field"><label>Akses</label><select class="input" name="visibility" onchange="this.form.submit()"><option value="">Semua</option>@foreach($filters['visibilities'] as $value=>$label)<option value="{{ $value }}" @selected(request('visibility')===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="tool-field search"><label>Live Search</label><div class="live-search-wrap"><input class="input" data-live-search="bankTable" name="q" value="{{ request('q') }}" placeholder="Cari soal, kode, mapel, topik"></div></div>
            <button class="btn primary">Cari</button><button class="btn" type="button" data-live-reset="bankTable">Clear</button>
        </form>
    </div>
    <div class="table-wrap"><table class="table" id="bankTable">
        <thead><tr><th>Soal</th><th>Jenis</th><th>Opsi/Kunci</th><th>Mapel/Topik</th><th>Level</th><th>Akses</th><th>Poin</th><th>Pembuat</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td><b>{{ $item->title }}</b><br><span class="muted small">{{ $item->question_code }}</span></td>
                <td><span class="badge info">{{ $filters['types'][$item->type] ?? $item->type }}</span></td>
                <td><span class="muted small">Opsi:</span> {{ Str::limit($item->optionsPreview() ?: '-', 90) }}<br><span class="muted small">Kunci:</span> <b>{{ Str::limit($item->answerPreview(), 90) }}</b></td>
                <td>{{ $item->subject ?: '-' }}<br><span class="muted small">{{ $item->topic ?: '-' }} · {{ $item->grade_level ?: '-' }}</span></td>
                <td><span class="badge {{ $item->difficulty === 'sulit' ? 'warning' : 'draft' }}">{{ ucfirst($item->difficulty) }}</span></td>
                <td>
                    @if($item->isSharedToSchool())
                        <span class="badge success">Bersama</span>
                    @else
                        <span class="badge draft">Pribadi</span>
                    @endif
                    @unless($item->is_active)
                        <br><span class="badge danger mt-sm">Nonaktif</span>
                    @endunless
                </td>
                <td>{{ $item->points }}</td>
                <td>{{ $item->teacher?->name ?: '-' }}</td>
                <td class="row">
                    @if($item->canBeManagedBy(auth()->user()))
                        <a class="btn soft" href="{{ route('question-bank.edit', $item) }}">Edit</a>
                        <form method="POST" action="{{ route('question-bank.destroy', $item) }}" onsubmit="return confirm('Hapus soal dari bank?')">@csrf @method('DELETE')<button class="btn danger">Hapus</button></form>
                    @else
                        <span class="badge info">Bisa dipakai</span>
                    @endif
                </td>
            </tr>
        @empty<tr data-empty-row><td colspan="9">Belum ada bank soal.</td></tr>@endforelse
        </tbody>
    </table></div>
    <div class="table-meta between"><div class="small muted">Terlihat: <b data-live-count="bankTable">{{ $items->count() }}</b> baris</div><div>{{ $items->links() }}</div></div>
</div>
@endsection
