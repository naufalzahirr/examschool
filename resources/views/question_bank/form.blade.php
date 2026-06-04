@extends('layouts.app', ['title' => $item->exists ? 'Edit Soal Bank' : 'Tambah Soal ke Bank'])

@section('content')
@php
    $isEdit = $item->exists;
    $oldSingle = old('question_json');
    $oldMany = old('questions_json');

    if ($isEdit && $oldSingle) {
        $decoded = json_decode($oldSingle, true);
        if (is_array($decoded)) {
            $initialQuestion = $decoded;
        }
    }

    if (! $isEdit && $oldMany) {
        $decoded = json_decode($oldMany, true);
        if (is_array($decoded)) {
            $initialQuestions = $decoded;
        }
    }

    $initialQuestions = $isEdit ? [$initialQuestion] : ($initialQuestions ?? [$initialQuestion]);
@endphp

<div class="between mb">
    <div>
        <h1>{{ $item->exists ? 'Edit Soal Bank' : 'Tambah Soal ke Bank' }}</h1>
        <p class="muted">{{ $item->exists ? 'Perbaiki satu soal yang sudah tersimpan di Bank Soal.' : 'Tulis satu atau banyak soal sekaligus. Setiap kartu akan tersimpan sebagai satu soal yang bisa dipilih ke ujian.' }}</p>
    </div>
    <a class="btn" href="{{ route('question-bank.index') }}">Kembali</a>
</div>

<div class="card mb">
    <div class="step-list">
        <div class="step">
            <span class="step-no">1</span>
            <div><b>Isi keterangan</b><br><span class="muted small">Mapel, jenjang, topik, level, dan akses pemakaian.</span></div>
        </div>
        <div class="step">
            <span class="step-no">2</span>
            <div><b>Tulis soal</b><br><span class="muted small">Tambah kartu soal sebanyak yang dibutuhkan.</span></div>
        </div>
        <div class="step">
            <span class="step-no">3</span>
            <div><b>Pakai ke ujian</b><br><span class="muted small">Buka menu Ujian, lalu pilih soal dari Bank Soal.</span></div>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert danger mb">
        <b>Belum bisa disimpan.</b>
        <ul class="mt-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form id="bankQuestionForm" class="form" method="POST" action="{{ $item->exists ? route('question-bank.update', $item) : route('question-bank.store') }}">
    @csrf
    @if($item->exists) @method('PUT') @endif
    <input type="hidden" name="{{ $isEdit ? 'question_json' : 'questions_json' }}" id="questionPayloadJson">

    <div class="card mb">
        <div class="between mb">
            <div>
                <h2 class="mb0">Keterangan Soal</h2>
                <p class="muted small mb0">{{ $item->exists ? 'Keterangan ini berlaku untuk soal yang sedang diedit.' : 'Keterangan ini akan dipakai untuk semua kartu soal di bawah. Saat membuat ujian, paket dengan keterangan yang sama dipilih satu kali.' }}</p>
            </div>
            @if($item->exists)
                <span class="badge info">{{ $item->question_code }}</span>
            @else
                <span class="badge info">Bisa banyak soal</span>
            @endif
        </div>
        <div class="three">
            <div class="field">
                <label>Mapel</label>
                <input class="input" name="subject" value="{{ old('subject', $item->subject) }}" placeholder="Contoh: Bahasa Indonesia">
            </div>
            <div class="field">
                <label>Jenjang/Kelas</label>
                <input class="input" name="grade_level" value="{{ old('grade_level', $item->grade_level) }}" placeholder="Contoh: XII / XI PPLG">
            </div>
            <div class="field">
                <label>Topik/Tag</label>
                <input class="input" name="topic" value="{{ old('topic', $item->topic) }}" placeholder="Contoh: Teks Eksplanasi">
            </div>
        </div>
        <div class="three">
            <div class="field">
                <label>Tingkat Kesulitan</label>
                <select class="input" name="difficulty">
                    <option value="mudah" @selected(old('difficulty', $item->difficulty) === 'mudah')>Mudah</option>
                    <option value="sedang" @selected(old('difficulty', $item->difficulty ?? 'sedang') === 'sedang')>Sedang</option>
                    <option value="sulit" @selected(old('difficulty', $item->difficulty) === 'sulit')>Sulit</option>
                </select>
            </div>
            <div class="field">
                <label>Akses Pemakaian</label>
                <select class="input" name="visibility">
                    @foreach($visibilityLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('visibility', $item->visibility ?? \App\Models\QuestionBankItem::VISIBILITY_SCHOOL) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="help">Dibagikan berarti guru lain bisa memakai soal ini ke ujian, tetapi tidak bisa mengedit atau menghapusnya.</p>
            </div>
            <div class="field">
                <label>Status</label>
                <label class="check-pill" style="height:42px;align-items:center"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))> Aktif dan bisa dipakai</label>
                <p class="help">Nonaktifkan jika soal belum layak dipakai ke ujian.</p>
            </div>
        </div>
    </div>

    <div class="card mb">
        <div class="between">
            <div>
                <h2 class="mb0">Daftar Soal</h2>
                <p class="muted small mb0"><b id="bankQuestionCount">0</b> kartu soal siap disimpan.</p>
            </div>
            @unless($isEdit)
                <button class="btn soft js-add-question" type="button">+ Tambah Soal</button>
            @endunless
        </div>
    </div>

    <div id="questionCards"></div>

    <div class="row mt">
        @unless($isEdit)
            <button class="btn soft js-add-question" type="button">+ Tambah Soal Lagi</button>
        @endunless
        <button class="btn primary">{{ $isEdit ? 'Simpan Perubahan Soal' : 'Simpan Semua Soal' }}</button>
        <a class="btn" href="{{ route('question-bank.index') }}">Batal</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
const types = @json($typeLabels);
const initialQuestions = @json($initialQuestions);
const isEdit = @json($isEdit);
let qSeq = 0;

function esc(value){return String(value ?? '').replace(/[&<>'"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}
function defaultQuestion(){return {type:'multiple_choice', title:'', description:'', points:1, correct_text:'', answer_key:{}, options:[{label:'Opsi 1',is_correct:true},{label:'Opsi 2',is_correct:false}]};}
function typeOptions(selected){return Object.entries(types).map(([k,v])=>`<option value="${k}" ${selected===k?'selected':''}>${v}</option>`).join('');}

function addQuestion(question = null){
    const q = {...defaultQuestion(), ...(question || {})};
    const uid = `q${++qSeq}`;
    const card = document.createElement('div');
    card.className = 'card q-card active mb';
    card.dataset.qid = uid;
    card.innerHTML = `
        <div class="between mb">
            <div>
                <span class="pill q-number">Soal</span>
                <p class="muted small mt-sm">Satu kartu akan menjadi satu item Bank Soal.</p>
            </div>
            <div class="row">
                <select class="input q-type" style="max-width:320px">${typeOptions(q.type || 'multiple_choice')}</select>
                ${isEdit ? '' : '<button type="button" class="btn soft q-duplicate">Duplikat</button><button type="button" class="btn danger q-remove">Hapus</button>'}
            </div>
        </div>
        <div class="field">
            <label>Pertanyaan</label>
            <input class="input q-title" placeholder="Tulis pertanyaan" value="${esc(q.title)}">
        </div>
        <div class="field">
            <label>Deskripsi / instruksi</label>
            <textarea class="input q-desc" rows="2" placeholder="Opsional, misalnya bacalah teks berikut terlebih dahulu">${esc(q.description)}</textarea>
        </div>
        <div class="field" style="max-width:180px">
            <label>Poin</label>
            <input class="input q-points" type="number" min="0" step="0.5" value="${esc(q.points ?? 1)}">
        </div>
        <div class="answer-area"></div>
    `;
    document.getElementById('questionCards').appendChild(card);
    renderAnswerArea(card, q);
    renumberCards();
}

function renumberCards(){
    document.querySelectorAll('.q-card').forEach((card, index) => {
        card.querySelector('.q-number').textContent = `Soal ${index + 1}`;
    });
    const counter = document.getElementById('bankQuestionCount');
    if(counter) counter.textContent = document.querySelectorAll('.q-card').length;
}

function renderAnswerArea(card, question = null){
    const type = card.querySelector('.q-type').value;
    const area = card.querySelector('.answer-area');
    const data = question || collectQuestion(card, false);
    const uid = card.dataset.qid;

    if(type === 'multiple_choice' || type === 'multiple_choice_complex'){
        const inputType = type === 'multiple_choice' ? 'radio' : 'checkbox';
        const options = Array.isArray(data.options) && data.options.length ? data.options : defaultQuestion().options;
        const note = type === 'multiple_choice'
            ? 'Siswa memilih satu jawaban. Tandai tepat satu opsi sebagai kunci.'
            : 'Siswa bisa memilih lebih dari satu jawaban. Tandai semua opsi yang benar.';
        area.innerHTML = `<div class="type-note muted small">${note}</div><div class="options-list mt"></div><button type="button" class="btn soft add-choice">+ Tambah opsi</button>`;
        const list = area.querySelector('.options-list');
        options.forEach((opt) => list.insertAdjacentHTML('beforeend', choiceOptionHtml(uid, inputType, opt)));
    } else if(type === 'true_false') {
        const answer = data.answer_key?.answer ?? true;
        area.innerHTML = `
            <div class="type-note muted small">Siswa memilih Benar atau Salah. Kunci disimpan di server dan tidak ikut dikirim ke paket siswa.</div>
            <div class="tf-grid mt">
                <label class="tf-card row"><input type="radio" class="q-tf-answer" name="tf_${uid}" value="true" ${answer === true || answer === 'true' ? 'checked' : ''}> Kunci: Benar</label>
                <label class="tf-card row"><input type="radio" class="q-tf-answer" name="tf_${uid}" value="false" ${answer === false || answer === 'false' ? 'checked' : ''}> Kunci: Salah</label>
            </div>`;
    } else if(type === 'matching') {
        const rows = Array.isArray(data.options) && data.options.length ? data.options : [{label:'Item kiri 1',match:'Pasangan 1'},{label:'Item kiri 2',match:'Pasangan 2'}];
        area.innerHTML = `
            <div class="type-note muted small">Menjodohkan: isi item kiri dan pasangan benar di kanan. Di aplikasi siswa, pasangan kanan bisa diacak.</div>
            <div class="split-note mt"><b>Item / pernyataan</b><b>Pasangan benar</b></div>
            <div class="matching-list"></div>
            <button type="button" class="btn soft add-match">+ Tambah pasangan</button>`;
        const list = area.querySelector('.matching-list');
        rows.forEach((row) => list.insertAdjacentHTML('beforeend', matchingRowHtml(row)));
    } else if(type === 'short_answer') {
        let accepted = data.answer_key?.accepted || [];
        if(!Array.isArray(accepted)) accepted = [];
        const text = accepted.length ? accepted.join('; ') : (data.correct_text || '');
        area.innerHTML = `<div class="field"><label>Kunci jawaban singkat</label><input class="input q-accepted" placeholder="Contoh: SEB; safe exam browser" value="${esc(text)}"><p class="help">Pisahkan beberapa variasi jawaban benar dengan titik koma (;). Koreksi otomatis tidak membedakan huruf besar/kecil.</p></div>`;
    }
}

function choiceOptionHtml(uid, inputType, opt = {}){
    return `<div class="option-row">
        <input class="opt-correct" name="correct_${uid}" type="${inputType}" ${opt.is_correct?'checked':''}>
        <input class="input opt-label" placeholder="Opsi jawaban" value="${esc(opt.label)}">
        <span class="muted small">Kunci</span>
        <button type="button" class="btn danger remove-row">Hapus</button>
    </div>`;
}

function matchingRowHtml(row = {}){
    return `<div class="matching-row">
        <input class="input match-left" placeholder="Item kiri" value="${esc(row.label)}">
        <input class="input match-right" placeholder="Pasangan benar" value="${esc(row.match || row.meta?.match || '')}">
        <button type="button" class="btn danger remove-row">Hapus</button>
    </div>`;
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

function collectQuestion(card){
    const type = card.querySelector('.q-type').value;
    const q = {
        type,
        title: card.querySelector('.q-title').value,
        description: card.querySelector('.q-desc').value,
        points: parseFloat(card.querySelector('.q-points').value || '1'),
        correct_text: '',
        answer_key: {},
        options: []
    };

    if(type === 'multiple_choice' || type === 'multiple_choice_complex') q.options = collectChoiceOptions(card);
    if(type === 'matching') q.options = collectMatchingOptions(card);
    if(type === 'true_false') q.answer_key = {answer: (card.querySelector('.q-tf-answer:checked')?.value || 'true') === 'true'};
    if(type === 'short_answer') {
        const accepted = collectAcceptedAnswers(card);
        q.answer_key = {accepted};
        q.correct_text = accepted[0] || '';
    }

    return q;
}

function validateQuestion(q, index){
    const prefix = `Soal ${index + 1}: `;
    if(!q.title.trim()) return prefix + 'pertanyaan wajib diisi.';
    if(q.type === 'multiple_choice'){
        if(q.options.length < 2) return prefix + 'pilihan ganda minimal punya 2 opsi.';
        if(q.options.filter(o=>o.is_correct).length !== 1) return prefix + 'pilihan ganda harus punya tepat 1 kunci.';
    }
    if(q.type === 'multiple_choice_complex'){
        if(q.options.length < 2) return prefix + 'pilihan ganda kompleks minimal punya 2 opsi.';
        if(q.options.filter(o=>o.is_correct).length < 1) return prefix + 'pilihan ganda kompleks minimal punya 1 kunci.';
    }
    if(q.type === 'matching' && q.options.length < 2) return prefix + 'menjodohkan minimal punya 2 pasangan lengkap.';
    if(q.type === 'short_answer' && (!q.answer_key.accepted || q.answer_key.accepted.length < 1)) return prefix + 'jawaban singkat wajib punya minimal 1 kunci.';
    return null;
}

document.getElementById('questionCards').addEventListener('change', function(e){
    if(e.target.classList.contains('q-type')){
        const card = e.target.closest('.q-card');
        renderAnswerArea(card);
    }
});

document.getElementById('questionCards').addEventListener('click', function(e){
    const card = e.target.closest('.q-card');
    if(e.target.classList.contains('add-choice')){
        const inputType = card.querySelector('.q-type').value === 'multiple_choice' ? 'radio' : 'checkbox';
        card.querySelector('.options-list').insertAdjacentHTML('beforeend', choiceOptionHtml(card.dataset.qid, inputType, {label:'',is_correct:false}));
    }
    if(e.target.classList.contains('add-match')){
        card.querySelector('.matching-list').insertAdjacentHTML('beforeend', matchingRowHtml());
    }
    if(e.target.classList.contains('remove-row')){
        e.target.closest('.option-row, .matching-row').remove();
    }
    if(e.target.classList.contains('q-duplicate')){
        addQuestion(collectQuestion(card));
    }
    if(e.target.classList.contains('q-remove')){
        card.remove();
        renumberCards();
    }
});

document.querySelectorAll('.js-add-question').forEach(btn => btn.addEventListener('click', () => addQuestion()));

initialQuestions.forEach((q) => addQuestion(q));

document.getElementById('bankQuestionForm').addEventListener('submit', function(e){
    const cards = [...document.querySelectorAll('.q-card')];
    if(cards.length < 1){
        e.preventDefault();
        alert('Minimal buat 1 soal.');
        return;
    }

    const questions = cards.map(card => collectQuestion(card));
    for(let i = 0; i < questions.length; i++){
        const error = validateQuestion(questions[i], i);
        if(error){
            e.preventDefault();
            alert(error);
            return;
        }
    }

    document.getElementById('questionPayloadJson').value = JSON.stringify(isEdit ? questions[0] : questions);
});
</script>
@endpush
