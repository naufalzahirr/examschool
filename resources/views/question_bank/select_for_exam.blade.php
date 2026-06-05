@extends('layouts.app', ['title' => 'Pilih Soal untuk ' . $exam->title])

@push('head')
<style>
.bank-card{border:2px solid var(--line);border-radius:var(--radius);padding:1.1rem 1.25rem;cursor:pointer;transition:all .18s;background:#fff;display:block;width:100%;text-align:left;user-select:none}
.bank-card:hover{border-color:#99f6e4;background:#f0fdfb}
.bank-card.selected{border-color:var(--primary);background:var(--primary-soft);box-shadow:0 0 0 3px rgba(20,184,166,.15)}
.bank-card.already-added{border-color:var(--line);background:#f8fafc;opacity:.7;cursor:not-allowed}
.bank-card *{cursor:pointer}
.bank-card.already-added *{cursor:not-allowed}
.bank-card-title{font-size:15px;font-weight:900;color:var(--heading);margin:0 0 .3rem}
.bank-card-meta{font-size:12px;color:var(--muted);margin-bottom:.55rem}
.bank-card input{pointer-events:none}

/* List view */
#bankList.list-mode{display:flex;flex-direction:column;gap:.5rem}
#bankList.list-mode .bank-card{display:flex;align-items:center;gap:1rem;padding:.85rem 1.1rem}
#bankList.list-mode .bank-card .lv-main{flex:1;min-width:0}
#bankList.list-mode .bank-card-title{margin:0;font-size:14px}
#bankList.list-mode .bank-card-meta{margin:0}
#bankList.list-mode .lv-stats{display:flex;gap:.4rem;flex-shrink:0}

.view-toggle{display:inline-flex;border:1px solid var(--line-strong);border-radius:10px;overflow:hidden}
.view-toggle button{border:0;background:#fff;padding:.5rem .85rem;font-weight:800;font-size:13px;cursor:pointer;color:var(--muted)}
.view-toggle button.active{background:var(--primary);color:#fff}

.selected-bar{position:sticky;bottom:0;background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-top:1px solid var(--line);padding:1rem 1.35rem;border-radius:0 0 var(--radius) var(--radius);display:flex;align-items:center;gap:1rem;z-index:5}

.info-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(3px);z-index:100;display:none;align-items:center;justify-content:center;padding:1.5rem}
.info-modal-overlay.open{display:flex}
.info-modal{background:#fff;border-radius:14px;max-width:560px;width:100%;box-shadow:0 30px 70px rgba(15,23,42,.30);padding:1.5rem}
.info-step{display:flex;gap:.85rem;align-items:flex-start;margin-bottom:1rem}
.info-step:last-child{margin-bottom:0}
.info-step-ico{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;font-size:19px;flex-shrink:0}
</style>
@endpush

@section('content')

<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0">Pilih Soal untuk Ujian</h1>
            <p class="muted mb0">Ujian: <b>{{ $exam->title }}</b> | Sudah ada: <b>{{ $exam->questions_count ?? 0 }} soal</b></p>
        </div>
        <div class="row">
            <button type="button" class="btn ghost" onclick="document.getElementById('infoModal').classList.add('open')" style="font-size:13px">Cara Kerja</button>
            <a class="btn ghost" href="{{ route('exams.show', $exam) }}">Kembali</a>
        </div>
    </div>
</div>

{{-- ═══ FILTER ═══ --}}
<div class="card data-card mb">
    <form class="table-toolbar" method="GET" action="{{ route('exams.question-bank.select', $exam) }}">
        <div class="table-title">
            <h2 style="font-size:15px">Filter Bank Soal</h2>
            <p class="muted small mb0">{{ $bankGroups->total() }} paket ditemukan</p>
        </div>
        <div class="table-tools">
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
                <label>Kelas</label>
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
            <div class="tool-field search">
                <label>Cari</label>
                <div class="live-search-wrap">
                    <input class="input" name="q" value="{{ request('q') }}" placeholder="Mapel, topik...">
                </div>
            </div>
            <button class="btn primary" style="align-self:flex-end">Cari</button>
            <a class="btn ghost" href="{{ route('exams.question-bank.select', $exam) }}" style="align-self:flex-end">Reset</a>
        </div>
    </form>
</div>

{{-- ═══ TOGGLE VIEW ═══ --}}
@if(!$bankGroups->isEmpty())
<div class="between mb">
    <span class="muted small">Klik satu paket untuk memilih, lalu tekan "Tambahkan ke Ujian"</span>
    <div class="view-toggle">
        <button type="button" id="btnCard" class="active" onclick="setView('card')">Card View</button>
        <button type="button" id="btnList" onclick="setView('list')">List View</button>
    </div>
</div>
@endif

{{-- ═══ DAFTAR PAKET ═══ --}}
<form method="POST" action="{{ route('exams.question-bank.add', $exam) }}" id="selectBankForm">
    @csrf

    @if($bankGroups->isEmpty())
        <div class="card" style="text-align:center;padding:3rem">
            <div style="font-size:32px;margin-bottom:.75rem;font-weight:900;color:var(--primary)">0</div>
            <b style="font-size:16px;display:block;margin-bottom:.4rem;color:var(--heading)">Tidak ada paket yang cocok</b>
            <p class="muted small mb0">Coba ubah filter, atau buat soal baru di Bank Soal.</p>
            <div class="row" style="justify-content:center;margin-top:.85rem">
                <a class="btn primary" href="{{ route('question-bank.create') }}">Buat Soal Baru</a>
                <a class="btn ghost" href="{{ route('exams.question-bank.select', $exam) }}">Reset Filter</a>
            </div>
        </div>
    @else
        <div id="bankList" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem;margin-bottom:1.25rem">
            @foreach($bankGroups as $group)
                @php $isAdded = ($group['available_count'] ?? 0) < 1; @endphp
                <label class="bank-card {{ $isAdded ? 'already-added' : '' }}">
                    <input type="radio" name="bank_key" value="{{ $group['key'] }}"
                           data-title="{{ $group['title'] }}" data-count="{{ $group['available_count'] }}"
                           class="bank-select" style="display:none" @disabled($isAdded)>
                    <div class="lv-main">
                        <div class="between" style="align-items:flex-start;margin-bottom:.5rem">
                            <div class="bank-card-title">{{ $group['title'] }}</div>
                            @if($isAdded)
                                <span class="badge published" style="font-size:10px;flex-shrink:0">Sudah masuk</span>
                            @endif
                        </div>
                        <div class="bank-card-meta">
                            {{ $group['subject'] ?: '-' }}@if($group['grade_level']) | {{ $group['grade_level'] }}@endif@if($group['topic']) | {{ $group['topic'] }}@endif
                        </div>
                    </div>
                    <div class="lv-stats" style="display:flex;gap:.4rem;flex-wrap:wrap">
                        <span class="badge" style="font-size:11px">{{ $group['questions_count'] }} soal</span>
                        @if(!$isAdded && $group['available_count'] < $group['questions_count'])
                            <span class="badge warning" style="font-size:11px" title="Soal yang belum masuk ke ujian ini">{{ $group['available_count'] }} bisa ditambahkan</span>
                        @endif
                        @if(($group['visibility'] ?? '') === \App\Models\QuestionBankItem::VISIBILITY_SCHOOL)
                            <span class="badge published" style="font-size:11px">Bersama</span>
                        @else
                            <span class="badge archived" style="font-size:11px">Pribadi</span>
                        @endif
                    </div>
                </label>
            @endforeach
        </div>
        <div style="margin-bottom:1rem">{{ $bankGroups->links() }}</div>
    @endif

    @if(!$bankGroups->isEmpty())
    <div class="selected-bar" id="selectedBar">
        <div style="flex:1">
            <div id="selectedInfo" style="font-weight:900;color:var(--muted)">Belum ada paket dipilih</div>
            <div id="selectedSub" class="muted small"></div>
        </div>
        <a class="btn ghost" href="{{ route('question-bank.create') }}" style="font-size:13px">Buat Soal Baru</a>
        <button class="btn primary" id="submitBtn" disabled style="min-width:180px">Tambahkan ke Ujian</button>
    </div>
    @endif
</form>

{{-- ═══ INFO MODAL ═══ --}}
<div class="info-modal-overlay" id="infoModal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="info-modal">
        <div class="between" style="margin-bottom:1.25rem">
            <h2 class="mb0" style="font-size:18px">Cara Memilih Soal</h2>
            <button type="button" class="btn ghost" style="padding:.35rem .6rem" onclick="document.getElementById('infoModal').classList.remove('open')">Tutup</button>
        </div>
        <div class="info-step">
            <div class="info-step-ico" style="background:var(--primary-soft)">1</div>
            <div>
                <b style="font-size:14px;color:var(--heading)">Apa itu Paket Bank Soal?</b>
                <p class="muted small mb0" style="margin-top:.2rem;line-height:1.5">Soal yang punya mapel, jenjang, dan topik sama dikelompokkan jadi satu paket. Satu paket = sekumpulan soal dengan label yang sama.</p>
            </div>
        </div>
        <div class="info-step">
            <div class="info-step-ico" style="background:var(--accent-soft)">2</div>
            <div>
                <b style="font-size:14px;color:var(--heading)">Pilih satu, semua soal masuk</b>
                <p class="muted small mb0" style="margin-top:.2rem;line-height:1.5">Klik satu paket lalu "Tambahkan ke Ujian". Semua soal aktif di paket itu disalin otomatis ke ujian.</p>
            </div>
        </div>
        <div class="info-step">
            <div class="info-step-ico" style="background:var(--warning-soft)">3</div>
            <div>
                <b style="font-size:14px;color:var(--heading)">Badge "bisa ditambahkan" artinya apa?</b>
                <p class="muted small mb0" style="margin-top:.2rem;line-height:1.5">Itu jumlah soal di paket tersebut yang belum masuk ke ujian ini. Kalau semua sudah masuk, badge berubah menjadi "Sudah masuk".</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = [...document.querySelectorAll('.bank-card:not(.already-added)')];
    const info  = document.getElementById('selectedInfo');
    const sub   = document.getElementById('selectedSub');
    const btn   = document.getElementById('submitBtn');

    cards.forEach(card => {
        card.addEventListener('click', () => {
            const radio = card.querySelector('.bank-select');
            if(!radio || radio.disabled) return;
            radio.checked = true;
            document.querySelectorAll('.bank-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            if(info) info.innerHTML = `Paket terpilih: <b style="color:var(--heading)">${radio.dataset.title}</b>`;
            if(sub)  sub.textContent = `${radio.dataset.count} soal akan ditambahkan ke ujian ini`;
            if(btn){ btn.disabled = false; btn.textContent = 'Tambahkan ke Ujian'; }
        });
    });
});

function setView(mode){
    const list = document.getElementById('bankList');
    const bC = document.getElementById('btnCard'), bL = document.getElementById('btnList');
    if(mode === 'list'){
        list.classList.add('list-mode');
        list.style.gridTemplateColumns = '';
        bL.classList.add('active'); bC.classList.remove('active');
        localStorage.setItem('bankView','list');
    } else {
        list.classList.remove('list-mode');
        list.style.gridTemplateColumns = 'repeat(auto-fill,minmax(320px,1fr))';
        bC.classList.add('active'); bL.classList.remove('active');
        localStorage.setItem('bankView','card');
    }
}
if(localStorage.getItem('bankView') === 'list') setView('list');
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.getElementById('infoModal')?.classList.remove('open');});
</script>
@endpush
