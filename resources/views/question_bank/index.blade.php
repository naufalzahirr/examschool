@extends('layouts.app', ['title' => 'Bank Soal'])

@push('head')
<style>
.qb-toolbar{display:flex;flex-direction:column;gap:.85rem;padding:1.15rem 1.35rem;border-bottom:1px solid var(--line);background:linear-gradient(135deg,#fff 0%,#f7fffd 100%)}
.qb-filter-row{display:flex;gap:.65rem;flex-wrap:wrap;align-items:flex-end}
.qb-filter-row .tool-field{min-width:0;flex:1 1 150px}
.qb-action-row{display:flex;gap:.65rem;align-items:center}
.qb-action-row .live-search-wrap{flex:1}
.qb-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(3px);z-index:100;display:none;align-items:center;justify-content:center;padding:1.5rem}
.qb-modal-overlay.open{display:flex}
.qb-modal{background:#fff;border-radius:14px;max-width:640px;width:100%;max-height:85vh;overflow:auto;box-shadow:0 30px 70px rgba(15,23,42,.30)}
.qb-modal-head{padding:1.25rem 1.5rem;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;position:sticky;top:0;background:#fff}
.qb-modal-body{padding:1.25rem 1.5rem}
.qb-opt{display:flex;align-items:center;gap:.6rem;padding:.6rem .8rem;border:1px solid var(--line);border-radius:10px;margin-bottom:.45rem}
.qb-opt.correct{background:var(--success-soft);border-color:#bbf7d0}
.qb-opt-mark{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;font-size:11px;font-weight:900;flex-shrink:0;color:#fff}
</style>
@endpush

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
    <form class="qb-toolbar" method="GET" action="{{ route('question-bank.index') }}">
        {{-- Baris 1: filter dropdown --}}
        <div class="qb-filter-row">
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
        </div>
        {{-- Baris 2: search + tombol --}}
        <div class="qb-action-row">
            <div class="live-search-wrap">
                <input class="input" data-live-search="bankTable" name="q" value="{{ request('q') }}" placeholder="Cari soal, kode, topik...">
            </div>
            <button class="btn primary">Cari</button>
            @if(request('q') || request('subject') || request('grade_level') || request('type') || request('difficulty') || request('visibility'))
                <a class="btn ghost" href="{{ route('question-bank.index') }}">Reset</a>
            @else
                <button class="btn ghost" type="button" data-live-reset="bankTable">Clear</button>
            @endif
        </div>
    </form>

    <div class="table-wrap">
        <table class="table" id="bankTable">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Jenis</th>
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
                <tr data-title="{{ $item->title }}">
                    <td><span class="muted small">{{ $item->question_code }}</span></td>
                    <td><span class="badge info" style="font-size:11px">{{ $filters['types'][$item->type] ?? $item->type }}</span></td>
                    <td>
                        <span style="font-size:13px">{{ $item->subject ?: '-' }}</span><br>
                        <span class="muted small">{{ $item->topic ?: '-' }} | {{ $item->grade_level ?: '-' }}</span>
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
                    <td class="small muted">{{ $item->teacher?->name ?: '-' }}</td>
                    <td>
                        <div class="row" style="gap:.3rem;flex-wrap:nowrap">
                            <button type="button" class="btn ghost" style="font-size:12px;padding:.35rem .65rem"
                                    onclick="viewQuestion('{{ route('question-bank.detail', $item) }}')">Lihat</button>
                            @if($item->canBeManagedBy(auth()->user()))
                                <a class="btn soft" href="{{ route('question-bank.edit', $item) }}" style="font-size:12px;padding:.35rem .65rem">Edit</a>
                                <form method="POST" action="{{ route('question-bank.destroy', $item) }}" onsubmit="return confirm('Hapus soal dari bank?')">
                                    @csrf @method('DELETE')
                                    <button class="btn danger" style="font-size:12px;padding:.35rem .65rem">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr data-empty-row>
                    <td colspan="8" style="text-align:center;padding:3rem;color:var(--muted)">
                        <div style="font-size:32px;margin-bottom:.75rem;font-weight:900;color:var(--primary)">BS</div>
                        <b style="display:block;color:var(--heading);margin-bottom:.35rem">Bank Soal masih kosong</b>
                        <p class="small mb0">Tambah soal pertama atau import dari file.</p>
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
        <div class="small muted">Total: <b>{{ $items->total() }}</b> soal | Terlihat: <b data-live-count="bankTable">{{ $items->count() }}</b></div>
        <div>{{ $items->links() }}</div>
    </div>
</div>

{{-- Modal detail soal --}}
<div class="qb-modal-overlay" id="qbModal" onclick="if(event.target===this)closeQbModal()">
    <div class="qb-modal">
        <div class="qb-modal-head">
            <div>
                <div class="muted small" id="qbModalType">Memuat...</div>
                <h2 class="mb0" id="qbModalTitle" style="font-size:18px;margin-top:.25rem">-</h2>
            </div>
            <button type="button" class="btn ghost" style="padding:.35rem .6rem" onclick="closeQbModal()">X</button>
        </div>
        <div class="qb-modal-body" id="qbModalBody">
            <div style="text-align:center;padding:2rem;color:var(--muted)">Memuat detail soal...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const qbModal = document.getElementById('qbModal');

function closeQbModal(){ qbModal.classList.remove('open'); }

async function viewQuestion(url){
    qbModal.classList.add('open');
    const body = document.getElementById('qbModalBody');
    body.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--muted)">Memuat detail soal...</div>';
            document.getElementById('qbModalTitle').textContent = '-';
    document.getElementById('qbModalType').textContent = 'Memuat...';
    try {
        const res = await fetch(url, {headers:{'Accept':'application/json'}});
        if(!res.ok) throw new Error('Gagal memuat');
        const d = await res.json();
        document.getElementById('qbModalTitle').textContent = d.title;
        document.getElementById('qbModalType').textContent = `${d.type_label} | ${d.points} poin | Kode ${d.code}`;

        let html = '';
        if(d.description){
            html += `<p class="muted" style="font-size:13px;margin-bottom:1rem;padding:.6rem .85rem;background:#f8fafc;border-radius:8px">${escapeHtml(d.description)}</p>`;
        }
        html += `<div class="row" style="gap:.4rem;margin-bottom:1rem">
            <span class="badge" style="font-size:11px">${escapeHtml(d.subject||'Tanpa mapel')}</span>
            ${d.grade_level?`<span class="badge archived" style="font-size:11px">${escapeHtml(d.grade_level)}</span>`:''}
            ${d.topic?`<span class="badge info" style="font-size:11px">${escapeHtml(d.topic)}</span>`:''}
        </div>`;

        if(d.options && d.options.length){
            html += '<div style="font-size:12px;font-weight:900;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem">Pilihan Jawaban</div>';
            d.options.forEach((o, i) => {
                if(o.match !== null && o.match !== undefined){
                    html += `<div class="qb-opt"><b>${escapeHtml(o.label)}</b><span class="muted small">pasangan disembunyikan</span></div>`;
                } else {
                    html += `<div class="qb-opt">
                        <span class="qb-opt-mark" style="background:#cbd5e1">${String.fromCharCode(65+i)}</span>
                        <span>${escapeHtml(o.label)}</span>
                    </div>`;
                }
            });
        }
        if(d.edit_url){
            html += `<div style="margin-top:1rem;text-align:right"><a class="btn soft" href="${d.edit_url}">Edit Soal Ini</a></div>`;
        }
        body.innerHTML = html;
    } catch(e){
        body.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--danger)">Gagal memuat detail soal.</div>';
    }
}

function escapeHtml(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeQbModal();});
</script>
@endpush
