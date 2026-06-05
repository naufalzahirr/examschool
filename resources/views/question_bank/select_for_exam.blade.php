@extends('layouts.app', ['title' => 'Pilih Soal untuk ' . $exam->title])

@push('head')
<style>
.bank-card{border:2px solid var(--line);border-radius:var(--radius);padding:1.1rem 1.25rem;cursor:pointer;transition:all .18s;background:#fff;display:block;width:100%;text-align:left}
.bank-card:hover{border-color:#99f6e4;background:#f0fdfb}
.bank-card.selected{border-color:var(--primary);background:var(--primary-soft);box-shadow:0 0 0 3px rgba(20,184,166,.15)}
.bank-card.already-added{border-color:var(--line);background:#f8fafc;opacity:.75;cursor:not-allowed}
.bank-card-title{font-size:15px;font-weight:900;color:var(--heading);margin:0 0 .3rem}
.bank-card-meta{font-size:12px;color:var(--muted);margin-bottom:.55rem}
.bank-card-stats{display:flex;gap:.5rem;flex-wrap:wrap}
.selected-bar{position:sticky;bottom:0;background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-top:1px solid var(--line);padding:1rem 1.35rem;border-radius:0 0 var(--radius) var(--radius);display:flex;align-items:center;gap:1rem;z-index:5}
</style>
@endpush

@section('content')

{{-- ═══ HEADER ═══ --}}
<div class="hero mb">
    <div class="between">
        <div>
            <h1 style="margin:0">Pilih Soal untuk Ujian</h1>
            <p class="muted mb0">Ujian: <b>{{ $exam->title }}</b> · Sudah ada: <b>{{ $exam->questions_count ?? 0 }} soal</b></p>
        </div>
        <a class="btn ghost" href="{{ route('exams.show', $exam) }}">← Kembali ke Ujian</a>
    </div>
</div>

{{-- ═══ PENJELASAN KONSEP (penting untuk guru baru) ═══ --}}
<div class="card mb" style="background:linear-gradient(135deg,#f0fdf9,#fafbff);border-color:#99f6e4">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
        <div style="display:flex;gap:.75rem;align-items:flex-start">
            <div style="width:36px;height:36px;border-radius:10px;background:var(--primary-soft);display:grid;place-items:center;font-size:18px;flex-shrink:0">🧩</div>
            <div>
                <b style="font-size:13px;color:var(--heading)">Apa itu Paket Bank Soal?</b>
                <p class="muted small mb0" style="margin-top:.2rem;line-height:1.5">Soal-soal di bank yang dibuat dengan mapel, jenjang, dan topik yang sama dikelompokkan menjadi satu "paket". Satu paket = semua soal dengan label yang sama.</p>
            </div>
        </div>
        <div style="display:flex;gap:.75rem;align-items:flex-start">
            <div style="width:36px;height:36px;border-radius:10px;background:var(--accent-soft);display:grid;place-items:center;font-size:18px;flex-shrink:0">☑️</div>
            <div>
                <b style="font-size:13px;color:var(--heading)">Pilih satu paket, semua soal masuk</b>
                <p class="muted small mb0" style="margin-top:.2rem;line-height:1.5">Setelah memilih satu paket dan klik "Tambahkan ke Ujian", semua soal aktif di paket itu disalin ke ujian ini secara otomatis.</p>
            </div>
        </div>
        <div style="display:flex;gap:.75rem;align-items:flex-start">
            <div style="width:36px;height:36px;border-radius:10px;background:var(--warning-soft);display:grid;place-items:center;font-size:18px;flex-shrink:0">🔁</div>
            <div>
                <b style="font-size:13px;color:var(--heading)">Bisa pilih lebih dari satu paket</b>
                <p class="muted small mb0" style="margin-top:.2rem;line-height:1.5">Kembali ke halaman ini beberapa kali untuk menambah soal dari paket berbeda — misalnya paket Pilihan Ganda + paket Menjodohkan.</p>
            </div>
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
                <label>Jenis Soal</label>
                <select class="input" name="type" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['types'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Pembuat</label>
                <select class="input" name="owner" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['owners'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('owner') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field search">
                <label>Cari</label>
                <div class="live-search-wrap">
                    <input class="input" name="q" value="{{ request('q') }}" placeholder="Mapel, topik, pembuat...">
                </div>
            </div>
            <button class="btn primary" style="align-self:flex-end">Cari</button>
            <a class="btn ghost" href="{{ route('exams.question-bank.select', $exam) }}" style="align-self:flex-end">Reset</a>
        </div>
    </form>
</div>

{{-- ═══ DAFTAR PAKET SOAL (card-based, lebih visual) ═══ --}}
<form method="POST" action="{{ route('exams.question-bank.add', $exam) }}" id="selectBankForm">
    @csrf

    @if($bankGroups->isEmpty())
        <div class="card" style="text-align:center;padding:3rem">
            <div style="font-size:40px;margin-bottom:.75rem">🔍</div>
            <b style="font-size:16px;display:block;margin-bottom:.4rem;color:var(--heading)">Tidak ada paket yang cocok</b>
            <p class="muted small mb0">Coba ubah filter, atau buat soal baru di Bank Soal terlebih dahulu.</p>
            <div class="row" style="justify-content:center;margin-top:.85rem">
                <a class="btn primary" href="{{ route('question-bank.create') }}">+ Buat Soal Baru</a>
                <a class="btn ghost" href="{{ route('exams.question-bank.select', $exam) }}">Reset Filter</a>
            </div>
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem;margin-bottom:1.25rem">
            @foreach($bankGroups as $group)
                @php $isAdded = ($group['available_count'] ?? 0) < 1; @endphp
                <label class="bank-card {{ $isAdded ? 'already-added' : '' }}" id="card_{{ $loop->index }}">
                    <input type="radio" name="bank_key" value="{{ $group['key'] }}"
                           data-title="{{ $group['title'] }}"
                           data-count="{{ $group['questions_count'] }}"
                           class="bank-select" style="display:none"
                           @disabled($isAdded)>

                    <div class="between" style="align-items:flex-start;margin-bottom:.5rem">
                        <div class="bank-card-title">{{ $group['title'] }}</div>
                        @if($isAdded)
                            <span class="badge published" style="font-size:10px;flex-shrink:0">✓ Sudah masuk</span>
                        @endif
                    </div>

                    <div class="bank-card-meta">
                        {{ $group['subject'] ?: '–' }}
                        @if($group['grade_level']) · {{ $group['grade_level'] }} @endif
                        @if($group['topic']) · {{ $group['topic'] }} @endif
                    </div>

                    <div class="bank-card-stats">
                        <span class="badge" style="font-size:11px">{{ $group['questions_count'] }} soal</span>
                        @if(!$isAdded)
                            <span class="badge warning" style="font-size:11px">{{ $group['available_count'] }} belum masuk</span>
                        @endif
                        @if($group['types'])
                            <span class="badge info" style="font-size:11px">{{ $group['types'] }}</span>
                        @endif
                        @if(($group['visibility'] ?? '') === \App\Models\QuestionBankItem::VISIBILITY_SCHOOL)
                            <span class="badge published" style="font-size:11px">Bersama</span>
                        @else
                            <span class="badge archived" style="font-size:11px">Pribadi</span>
                        @endif
                    </div>

                    <div style="margin-top:.65rem;font-size:12px;color:var(--muted)">
                        Oleh: {{ $group['teacher_name'] ?: '–' }}
                        · Total: <b>{{ number_format((float)$group['total_points'], 0) }} poin</b>
                    </div>
                </label>
            @endforeach
        </div>

        <div style="margin-bottom:1rem">{{ $bankGroups->links() }}</div>
    @endif

    {{-- STICKY BAR PILIHAN --}}
    @if(!$bankGroups->isEmpty())
    <div class="selected-bar" id="selectedBar">
        <div style="flex:1">
            <div id="selectedInfo" style="font-weight:900;color:var(--muted)">Pilih satu paket dari daftar di atas</div>
            <div id="selectedSub" class="muted small"></div>
        </div>
        <a class="btn ghost" href="{{ route('question-bank.create') }}" style="font-size:13px">+ Buat Soal Baru</a>
        <button class="btn primary" id="submitBtn" disabled style="min-width:180px">Tambahkan ke Ujian</button>
    </div>
    @endif
</form>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const radios = [...document.querySelectorAll('.bank-select')];
    const info   = document.getElementById('selectedInfo');
    const sub    = document.getElementById('selectedSub');
    const btn    = document.getElementById('submitBtn');
    const cards  = [...document.querySelectorAll('.bank-card:not(.already-added)')];

    cards.forEach((card, idx) => {
        card.addEventListener('click', () => {
            const radio = card.querySelector('.bank-select');
            if (!radio || radio.disabled) return;
            radio.checked = true;
            cards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            if (info) info.innerHTML = `Paket terpilih: <b style="color:var(--heading)">${radio.dataset.title}</b>`;
            if (sub)  sub.textContent = `${radio.dataset.count} soal akan ditambahkan ke ujian ini`;
            if (btn)  { btn.disabled = false; btn.textContent = 'Tambahkan ke Ujian →'; }
        });
    });
});
</script>
@endpush
