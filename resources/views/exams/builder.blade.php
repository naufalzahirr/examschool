@extends('layouts.app', ['title' => 'Builder Soal'])

@push('head')
<style>
    .builder-wrap{max-width:980px;margin:0 auto}.form-head{background:linear-gradient(135deg,#fff,#eef2ff);border-top:10px solid var(--primary)}.q-card{position:relative;margin-bottom:16px;border-left:6px solid transparent}.q-card.active{border-left-color:var(--primary)}.q-tools{display:flex;gap:8px;justify-content:flex-end;margin-top:14px;border-top:1px solid var(--line);padding-top:14px}.option-row{display:grid;grid-template-columns:32px 1fr 86px 42px;gap:10px;align-items:center;margin:8px 0}.option-row input[type="radio"],.option-row input[type="checkbox"]{width:18px;height:18px}.matching-row{display:grid;grid-template-columns:1fr 1fr 42px;gap:10px;align-items:center;margin:8px 0}.pill{border:1px solid var(--line);border-radius:999px;padding:8px 12px;background:#fff;font-weight:900}.floating-add{position:sticky;bottom:18px;text-align:center}.add-btn{box-shadow:0 18px 40px rgba(91,103,241,.32)}.type-note{border:1px dashed var(--line);border-radius:18px;padding:12px;background:#fafafa}.answer-area{margin-top:12px}.mini-title{font-weight:900;margin:12px 0 6px}.split-note{display:grid;grid-template-columns:1fr 1fr;gap:10px}.tf-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.tf-card{border:1px solid var(--line);border-radius:18px;padding:14px;background:#fff}
    @media(max-width:700px){.option-row,.matching-row,.split-note,.tf-grid{grid-template-columns:1fr}.option-row{border:1px solid var(--line);border-radius:18px;padding:12px}}
</style>
@endpush

@section('content')
<div class="builder-wrap">
    <div class="between mb">
        <div>
            <h1>Builder Soal</h1>
            <p class="muted">Soal ujian diambil dari Bank Soal. Guru membuat soal di Bank Soal terlebih dahulu, lalu memilih soal yang sesuai untuk ujian ini.</p>
        </div>
        <a class="btn" href="{{ route('exams.show', $exam) }}">Kembali</a>
    </div>

    <div class="card form-head mb">
        <h2 style="margin-bottom:0">{{ $exam->title }}</h2>
        <p class="muted">{{ $exam->description ?: 'Tambahkan instruksi ujian di konfigurasi.' }}</p>
        <div class="row small"><span class="badge">Kode: {{ $exam->access_code }}</span><span class="pill">Versi Paket: {{ $exam->package_version }}</span><span class="badge {{ $exam->status }}">{{ $exam->status }}</span></div>
    </div>

    @unless($canEdit)
        <div class="alert warning">
            <b>Soal terkunci.</b> Untuk menjaga konsistensi paket soal dan penilaian, soal hanya bisa diedit saat status ujian masih <b>draft/siap publish</b> dan belum ada siswa yang login/mulai mengerjakan.
            @if(!in_array($exam->status, ['draft','ready'], true) && ! $exam->hasStartedWork())
                Jika perlu revisi, kembalikan ujian ke draft dari halaman detail ujian.
            @endif
        </div>
    @endunless

    <form id="builderForm" method="POST" action="{{ route('exams.questions.bulkSave', $exam) }}">
        @csrf
        <input type="hidden" name="questions_json" id="questionsJson">
        <div id="questions"></div>
        @if($canEdit && $exam->questions->isEmpty())
            <div class="card mt">
                <h3>Belum ada soal di ujian ini</h3>
                <p class="muted">Ambil soal dari Bank Soal sekolah atau buat soal baru di Bank Soal terlebih dahulu. Setelah ditambahkan, soal menjadi salinan ujian dan bisa direview di halaman ini.</p>
                <div class="row mt">
                    <a class="btn primary" href="{{ route('exams.question-bank.select', $exam) }}">Ambil dari Bank Soal</a>
                    <a class="btn soft" href="{{ route('question-bank.create') }}">Buat Soal di Bank Soal</a>
                </div>
            </div>
        @endif
        @if($canEdit)
            @if($exam->questions->isNotEmpty())
                <div class="floating-add row" style="justify-content:center"><a class="btn primary add-btn" href="{{ route('exams.question-bank.select', $exam) }}">Ambil dari Bank Soal</a><a class="btn soft" href="{{ route('question-bank.create') }}">Buat Soal di Bank Soal</a></div>
                <div class="card mt between">
                    <div><b>Simpan perubahan</b><br><span class="muted small">Perubahan pada salinan soal ujian akan menaikkan versi paket soal yang di-download siswa.</span></div>
                    <button class="btn primary">Simpan Soal</button>
                </div>
            @endif
        @else
            <div class="card mt">
                <b>Mode lihat saja</b><br>
                <span class="muted small">Builder ini hanya untuk review soal karena ujian sudah dikunci.</span>
            </div>
        @endif
    </form>
</div>
@endsection

@php
    $initialQuestions = $exam->questions->map(function ($q) {
        return [
            'question_code' => $q->question_code,
            'type' => $q->type,
            'title' => $q->title,
            'description' => $q->description,
            'required' => (bool) $q->required,
            'points' => (float) $q->points,
            'correct_text' => $q->correct_text,
            'answer_key' => $q->answer_key ?? [],
            'options' => $q->options->map(function ($o) {
                return [
                    'label' => $o->label,
                    'is_correct' => (bool) $o->is_correct,
                    'match' => $o->meta['match'] ?? '',
                ];
            })->values()->toArray(),
        ];
    })->values()->toArray();
@endphp

@push('scripts')
<script>
const initialQuestions = @json($initialQuestions);
const builderCanEdit = @json($canEdit);

const types = {
    multiple_choice: 'Pilihan ganda',
    multiple_choice_complex: 'Pilihan ganda kompleks',
    true_false: 'Benar / Salah',
    matching: 'Menjodohkan',
    short_answer: 'Uraian jawaban singkat'
};

function esc(value){return String(value ?? '').replace(/[&<>'"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}
function questionsEl(){return document.getElementById('questions');}
function uid(){return 'q_' + Math.random().toString(36).slice(2);}

function defaultQuestion(){
    return {question_code:null,type:'multiple_choice',title:'',description:'',required:true,points:1,correct_text:'',answer_key:{},options:[{label:'Opsi 1',is_correct:true},{label:'Opsi 2',is_correct:false}]};
}

function addQuestion(data = null){
    const q = Object.assign(defaultQuestion(), data || {});
    const card = document.createElement('div');
    card.className = 'card q-card';
    card.dataset.uid = uid();
    card.innerHTML = renderQuestion(q);
    questionsEl().appendChild(card);
    bindCard(card, q);
    refreshQuestionNumbers();
}

function renderQuestion(q){
    const typeOptions = Object.entries(types).map(([k,v])=>`<option value="${k}" ${q.type===k?'selected':''}>${v}</option>`).join('');
    return `
        <div class="between mb">
            <span class="pill q-number">Pertanyaan</span><span class="badge info">Kode soal: ${esc(q.question_code || 'otomatis setelah simpan')}</span>
            <select class="input q-type" style="max-width:300px">${typeOptions}</select>
        </div>
        <input type="hidden" class="q-code" value="${esc(q.question_code || '')}"><div class="field"><input class="input q-title" placeholder="Pertanyaan" value="${esc(q.title)}"></div>
        <div class="field"><textarea class="q-desc" rows="2" placeholder="Deskripsi atau instruksi opsional">${esc(q.description)}</textarea></div>
        <div class="answer-area"></div>
        <div class="row mt">
            <label class="row"><input class="q-required" type="checkbox" ${q.required?'checked':''}> Wajib diisi</label>
            <label class="row">Poin <input class="input q-points" type="number" min="0" step="0.5" value="${esc(q.points ?? 1)}" style="width:110px"></label>
        </div>
        <div class="q-tools">
            <button type="button" class="btn ghost" onclick="moveCard(this,-1)">↑</button>
            <button type="button" class="btn ghost" onclick="moveCard(this,1)">↓</button>
            <button type="button" class="btn danger" onclick="deleteCard(this)">Hapus</button>
        </div>`;
}

function bindCard(card, q){
    card.querySelector('.q-type').addEventListener('change', () => renderAnswerArea(card));
    card.addEventListener('click', () => {document.querySelectorAll('.q-card').forEach(c=>c.classList.remove('active')); card.classList.add('active');});
    renderAnswerArea(card, q);
}

function renderAnswerArea(card, q = null){
    const type = card.querySelector('.q-type').value;
    const area = card.querySelector('.answer-area');
    const data = q || {};

    if(type === 'multiple_choice' || type === 'multiple_choice_complex'){
        const inputType = type === 'multiple_choice' ? 'radio' : 'checkbox';
        const options = Array.isArray(data.options) && data.options.length ? data.options : [{label:'Opsi 1',is_correct:true},{label:'Opsi 2',is_correct:false}];
        const note = type === 'multiple_choice'
            ? 'Siswa memilih satu jawaban. Tandai satu opsi sebagai kunci.'
            : 'Siswa bisa memilih lebih dari satu jawaban. Nilai otomatis benar jika seluruh pilihan sama persis dengan kunci.';
        area.innerHTML = `<div class="type-note muted small">${note}</div><div class="options-list mt"></div><button type="button" class="btn soft" onclick="addChoiceOption(this)">+ Tambah opsi</button>`;
        const list = area.querySelector('.options-list');
        options.forEach((opt) => list.insertAdjacentHTML('beforeend', choiceOptionHtml(card.dataset.uid, inputType, opt)));
    } else if(type === 'true_false') {
        const answer = data.answer_key?.answer ?? true;
        area.innerHTML = `
            <div class="type-note muted small">Siswa memilih Benar atau Salah. Kunci jawaban disimpan di server dan tidak ikut dikirim ke paket soal.</div>
            <div class="tf-grid mt">
                <label class="tf-card row"><input type="radio" class="q-tf-answer" name="tf_${card.dataset.uid}" value="true" ${answer === true || answer === 'true' ? 'checked' : ''}> Kunci: Benar</label>
                <label class="tf-card row"><input type="radio" class="q-tf-answer" name="tf_${card.dataset.uid}" value="false" ${answer === false || answer === 'false' ? 'checked' : ''}> Kunci: Salah</label>
            </div>`;
    } else if(type === 'matching') {
        const rows = Array.isArray(data.options) && data.options.length ? data.options : [{label:'Kolom kiri 1',match:'Pasangan 1'},{label:'Kolom kiri 2',match:'Pasangan 2'}];
        area.innerHTML = `
            <div class="type-note muted small">Menjodohkan: isi kolom kiri dan pasangan benar di kanan. Di aplikasi siswa, jawaban kanan akan menjadi bank pilihan.</div>
            <div class="split-note mt"><b>Item / pernyataan</b><b>Pasangan benar</b></div>
            <div class="matching-list"></div>
            <button type="button" class="btn soft" onclick="addMatchingRow(this)">+ Tambah pasangan</button>`;
        const list = area.querySelector('.matching-list');
        rows.forEach((row) => list.insertAdjacentHTML('beforeend', matchingRowHtml(row)));
    } else if(type === 'short_answer') {
        let accepted = data.answer_key?.accepted || [];
        if(!Array.isArray(accepted)) accepted = [];
        const text = accepted.length ? accepted.join('; ') : (data.correct_text || '');
        area.innerHTML = `<div class="field"><label>Kunci jawaban singkat</label><input class="input q-accepted" placeholder="Contoh: SEB; safe exam browser" value="${esc(text)}"><p class="help">Pisahkan beberapa variasi jawaban benar dengan titik koma (;). Koreksi otomatis tidak membedakan huruf besar/kecil.</p></div>`;
    }
}

function choiceOptionHtml(cardUid, inputType, opt = {}){
    return `<div class="option-row">
        <input class="opt-correct" name="correct_${cardUid}" type="${inputType}" ${opt.is_correct?'checked':''}>
        <input class="input opt-label" placeholder="Opsi jawaban" value="${esc(opt.label)}">
        <span class="muted small">Kunci</span>
        <button type="button" class="btn danger" onclick="this.closest('.option-row').remove()">×</button>
    </div>`;
}

function matchingRowHtml(row = {}){
    return `<div class="matching-row">
        <input class="input match-left" placeholder="Item kiri" value="${esc(row.label)}">
        <input class="input match-right" placeholder="Pasangan benar" value="${esc(row.match || row.meta?.match || '')}">
        <button type="button" class="btn danger" onclick="this.closest('.matching-row').remove()">×</button>
    </div>`;
}

function addChoiceOption(btn){
    const card = btn.closest('.q-card');
    const type = card.querySelector('.q-type').value;
    const inputType = type === 'multiple_choice' ? 'radio' : 'checkbox';
    card.querySelector('.options-list').insertAdjacentHTML('beforeend', choiceOptionHtml(card.dataset.uid, inputType, {label:'',is_correct:false}));
}

function addMatchingRow(btn){
    btn.closest('.answer-area').querySelector('.matching-list').insertAdjacentHTML('beforeend', matchingRowHtml());
}

function collectChoiceOptions(card){
    return [...card.querySelectorAll('.option-row')].map(row => ({
        label: row.querySelector('.opt-label').value,
        is_correct: row.querySelector('.opt-correct').checked
    })).filter(o => o.label.trim() !== '');
}

function collectMatchingOptions(card){
    return [...card.querySelectorAll('.matching-row')].map(row => ({
        label: row.querySelector('.match-left').value,
        match: row.querySelector('.match-right').value
    })).filter(o => o.label.trim() !== '' && o.match.trim() !== '');
}

function collectAcceptedAnswers(card){
    const value = card.querySelector('.q-accepted')?.value || '';
    return value.split(/[;\n]/).map(v => v.trim()).filter(Boolean);
}

function moveCard(btn, dir){
    const card = btn.closest('.q-card');
    if(dir < 0 && card.previousElementSibling) questionsEl().insertBefore(card, card.previousElementSibling);
    if(dir > 0 && card.nextElementSibling) questionsEl().insertBefore(card.nextElementSibling, card);
    refreshQuestionNumbers();
}
function deleteCard(btn){btn.closest('.q-card').remove(); refreshQuestionNumbers();}
function refreshQuestionNumbers(){document.querySelectorAll('.q-number').forEach((el,i)=>el.textContent='Pertanyaan ' + (i+1));}

function collectQuestions(){
    return [...document.querySelectorAll('.q-card')].map(card => {
        const type = card.querySelector('.q-type').value;
        const base = {
            question_code: card.querySelector('.q-code')?.value || null,
            type,
            title: card.querySelector('.q-title').value,
            description: card.querySelector('.q-desc').value,
            required: card.querySelector('.q-required').checked,
            points: parseFloat(card.querySelector('.q-points').value || '0'),
            correct_text: '',
            answer_key: {},
            options: []
        };

        if(type === 'multiple_choice' || type === 'multiple_choice_complex'){
            base.options = collectChoiceOptions(card);
        }
        if(type === 'true_false'){
            base.answer_key = {answer: (card.querySelector('.q-tf-answer:checked')?.value || 'true') === 'true'};
        }
        if(type === 'matching'){
            base.options = collectMatchingOptions(card);
        }
        if(type === 'short_answer'){
            const accepted = collectAcceptedAnswers(card);
            base.correct_text = accepted[0] || '';
            base.answer_key = {accepted};
        }
        return base;
    });
}

function validateQuestions(questions){
    for(const [i,q] of questions.entries()){
        const no = i + 1;
        if(!q.title.trim()) return `Pertanyaan ${no} belum punya judul.`;
        if((q.type === 'multiple_choice' || q.type === 'multiple_choice_complex') && q.options.length < 2) return `Pertanyaan ${no} minimal punya 2 opsi.`;
        if(q.type === 'multiple_choice' && q.options.filter(o => o.is_correct).length !== 1) return `Pertanyaan ${no} pilihan ganda harus punya tepat 1 kunci.`;
        if(q.type === 'multiple_choice_complex' && q.options.filter(o => o.is_correct).length < 1) return `Pertanyaan ${no} pilihan ganda kompleks minimal punya 1 kunci.`;
        if(q.type === 'matching' && q.options.length < 2) return `Pertanyaan ${no} menjodohkan minimal punya 2 pasangan.`;
        if(q.type === 'short_answer' && (!q.answer_key.accepted || q.answer_key.accepted.length < 1)) return `Pertanyaan ${no} jawaban singkat belum punya kunci.`;
    }
    return null;
}

function lockBuilderIfNeeded(){
    if(builderCanEdit) return;
    document.querySelectorAll('#builderForm input, #builderForm textarea, #builderForm select, #builderForm button').forEach(el => {
        el.disabled = true;
    });
}

document.getElementById('builderForm').addEventListener('submit', function(e){
    if(!builderCanEdit){ e.preventDefault(); alert('Soal sudah terkunci dan tidak bisa disimpan.'); return; }
    const questions = collectQuestions();
    if(questions.length < 1){ e.preventDefault(); alert('Minimal buat 1 pertanyaan.'); return; }
    const error = validateQuestions(questions);
    if(error){ e.preventDefault(); alert(error); return; }
    document.getElementById('questionsJson').value = JSON.stringify(questions);
});

if(initialQuestions.length){ initialQuestions.forEach(q => addQuestion(q)); }
lockBuilderIfNeeded();
</script>
@endpush
