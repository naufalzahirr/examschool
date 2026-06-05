@extends('layouts.app', ['title' => 'Bank Soal'])

@section('content')
<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0">Bank Soal</h1>
            <p class="muted mb0">Kumpulan soal reusable. Satu paket bisa dipakai di banyak ujian.</p>
        </div>
        <div class="row">
            <a class="btn ghost" href="{{ route('question-bank.import') }}" style="font-size:13px">Import Massal</a>
            <a class="btn primary" href="{{ route('question-bank.create') }}">+ Tambah Soal</a>
        </div>
    </div>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2 style="font-size:15px">{{ $items->total() }} Soal di Bank</h2>
        </div>
        <form class="table-tools" method="GET" action="{{ route('question-bank.index') }}">
            <div class="tool-field">
                <label>Mapel</label>
                <select class="input" name="subject" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['subjects'] as $subject)
                        <option value="{{ $subject }}" @selected(request('subject') === $subject)>{{ $subject }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Jenjang</label>
                <select class="input" name="grade_level" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['grades'] as $grade)
                        <option value="{{ $grade }}" @selected(request('grade_level') === $grade)>{{ $grade }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Jenis</label>
                <select class="input" name="type" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['types'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Level</label>
                <select class="input" name="difficulty" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['difficulties'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('difficulty') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field search">
                <label>Cari</label>
                <div class="live-search-wrap">
                    <input class="input" data-live-search="bankTable" name="q" value="{{ request('q') }}" placeholder="Soal, kode, topik...">
                </div>
            </div>
            <button class="btn primary" style="align-self:flex-end">Cari</button>
            @if(request('q') || request('subject') || request('grade_level') || request('type') || request('difficulty') || request('visibility'))
                <a class="btn ghost" href="{{ route('question-bank.index') }}" style="align-self:flex-end">Reset</a>
            @else
                <button class="btn ghost" type="button" data-live-reset="bankTable" style="align-self:flex-end">Clear</button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="table" id="bankTable">
            <thead>
                <tr>
                    <th>Soal</th>
                    <th>Jenis</th>
                    <th>Kunci Jawaban</th>
                    <th>Mapel / Topik</th>
                    <th>Level</th>
                    <th>Akses</th>
                    <th>Poin</th>
                    <th>Pembuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <b style="font-size:13px;display:block;margin-bottom:.2rem">{{ Str::limit($item->title, 70) }}</b>
                        <span class="muted small">{{ $item->question_code }}</span>
                    </td>
                    <td>
                        <span class="badge info" style="font-size:11px">{{ $filters['types'][$item->type] ?? $item->type }}</span>
                    </td>
                    <td class="small">
                        <span class="muted">Kunci:</span> <b>{{ Str::limit($item->answerPreview(), 55) }}</b>
                    </td>
                    <td>
                        <span style="font-size:13px">{{ $item->subject ?: '–' }}</span><br>
                        <span class="muted small">{{ $item->topic ?: '–' }} · {{ $item->grade_level ?: '–' }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $item->difficulty === 'sulit' ? 'danger' : ($item->difficulty === 'mudah' ? 'published' : 'warning') }}" style="font-size:11px">{{ ucfirst($item->difficulty) }}</span>
                    </td>
                    <td>
                        @if($item->isSharedToSchool())
                            <span class="badge published" style="font-size:11px">Bersama</span>
                        @else
                            <span class="badge archived" style="font-size:11px">Pribadi</span>
                        @endif
                        @unless($item->is_active)
                            <span class="badge danger" style="font-size:10px;display:block;margin-top:.2rem">Nonaktif</span>
                        @endunless
                    </td>
                    <td><b>{{ $item->points }}</b></td>
                    <td class="small muted">{{ $item->teacher?->name ?: '–' }}</td>
                    <td>
                        @if($item->canBeManagedBy(auth()->user()))
                            <div class="row" style="gap:.3rem;flex-wrap:nowrap">
                                <a class="btn soft" href="{{ route('question-bank.edit', $item) }}" style="font-size:12px;padding:.35rem .65rem">Edit</a>
                                <form method="POST" action="{{ route('question-bank.destroy', $item) }}" onsubmit="return confirm('Hapus soal dari bank?')">
                                    @csrf @method('DELETE')
                                    <button class="btn danger" style="font-size:12px;padding:.35rem .65rem">Hapus</button>
                                </form>
                            </div>
                        @else
                            <span class="badge info" style="font-size:11px">Bisa dipakai</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr data-empty-row>
                    <td colspan="9" style="text-align:center;padding:3rem;color:var(--muted)">
                        <div style="font-size:40px;margin-bottom:.75rem">🧩</div>
                        <b style="display:block;color:var(--heading);margin-bottom:.35rem">Bank Soal masih kosong</b>
                        <p class="small mb0">Tambah soal pertama atau import dari file teks.</p>
                        <div class="row" style="justify-content:center;margin-top:.85rem">
                            <a class="btn primary" href="{{ route('question-bank.create') }}">+ Tambah Soal</a>
                            <a class="btn ghost" href="{{ route('question-bank.import') }}">Import Massal</a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-meta between">
        <div class="small muted">Terlihat: <b data-live-count="bankTable">{{ $items->count() }}</b> baris</div>
        <div>{{ $items->links() }}</div>
    </div>
</div>
@endsection
