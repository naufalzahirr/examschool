@extends('layouts.app', ['title' => $item->exists ? 'Edit Bank Soal' : 'Buat Bank Soal'])

@section('content')
@php
    $oldQuestionJson = old('question_json');
    if ($oldQuestionJson) {
        $decoded = json_decode($oldQuestionJson, true);
        if (is_array($decoded)) {
            $initialQuestion = $decoded;
        }
    }
@endphp
<div class="between mb">
    <div>
        <h1>{{ $item->exists ? 'Edit Bank Soal' : 'Buat Bank Soal' }}</h1>
        <p class="muted">Bank soal sekarang memakai format builder yang sama dengan soal ujian, jadi guru tidak perlu mengisi format teks mentah.</p>
    </div>
    <a class="btn" href="{{ route('question-bank.index') }}">Kembali</a>
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
    <input type="hidden" name="question_json" id="questionJson">

    <div class="card mb">
        <div class="between mb">
            <div>
                <h3>Identitas Bank Soal</h3>
                <p class="muted small">Informasi ini dipakai untuk pencarian, filter, dan pengambilan soal ke ujian.</p>
            </div>
            @if($item->exists)
                <span class="badge info">{{ $item->question_code }}</span>
            @endif
        </div>
        <div class="three">
            <div class="field">
                <label>Mapel</label>
                <input class="input" name="subject" value="{{ old('subject', $item->subject) }}" placeholder="Contoh: Bahasa Indonesia">
            </div>
            <div class="field">
                <label>Tingkat/Kelas</label>
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
                    <option value="mudah" @selected(old('difficulty',$item->difficulty)==='mudah')>Mudah</option>
                    <option value="sedang" @selected(old('difficulty',$item->difficulty ?? 'sedang')==='sedang')>Sedang</option>
                    <option value="sulit" @selected(old('difficulty',$item->difficulty)==='sulit')>Sulit</option>
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

    <div class="card q-card active">
        <div class="between mb">
            <div>
                <span class="pill">Isi Soal</span>
                <p class="muted small mt-sm">Format ini sama dengan Builder Ujian.</p>
            </div>
            <select class="input q-type" style="max-width:320px"></select>
        </div>

        <div class="field">
            <label>Pertanyaan</label>
            <input class="input q-title" placeholder="Tulis pertanyaan" value="">
        </div>
        <div class="field">
            <label>Deskripsi / instruksi</label>
            <textarea class="input q-desc" rows="2" placeholder="Opsional, misalnya bacalah teks berikut terlebih dahulu"></textarea>
        </div>
        <div class="field" style="max-width:180px">
            <label>Poin</label>
            <input class="input q-points" type="number" min="0" step="0.5" value="1">
        </div>

        <div class="answer-area"></div>
    </div>

    <div class="row mt">
        <button class="btn primary">Simpan Bank Soal</button>
        <a class="btn" href="{{ route('question-bank.index') }}">Batal</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
const types = @json($typeLabels);
const initialQuestion = @json($initialQuestion);

function esc(value){return String(value ?? '').replace(/[&<>'"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}
function card(){return document.querySelector('.q-card');}
function typeEl(){return document.querySelector('.q-type');}

function setupTypeSelect(){
    typeEl().innerHTML = Object.entries(types).map(([k,v])=>`<option value="${k}">${v}</option>`).join('');
}

function hydrateQuestion(q){
    setupTypeSelect();
    typeEl().value = q.type || 'multiple_choice';
    document.querySelector('.q-title').value = q.title || '';
    document.querySelector('.q-desc').value = q.description || '';
    document.querySelector('.q-points').value = q.points ?? 1;
    renderAnswerArea(q);
}

function renderAnswerArea(q = null){
    const type = typeEl().value;
    const area = document.querySelector('.answer-area');
    const data = q || collectQuestion(false);

    if(type === 'multiple_choice' || type === 'multiple_choice_complex'){
        const inputType = type === 'multiple_choice' ? 'radio' : 'checkbox';
        const options = Array.isArray(data.options) && data.options.length ? data.options : [{label:'Opsi 1',is_correct:true},{label:'Opsi 2',is_correct:false}];
        const note = type === 'multiple_choice'
            ? 'Siswa memilih satu jawaban. Tandai tepat satu opsi sebagai kunci.'
            : 'Siswa bisa memilih lebih dari satu jawaban. Tandai semua opsi yang benar.';
        area.innerHTML = `<div class="type-note muted small">${note}</div><div class="options-list mt"></div><button type="button" class="btn soft" onclick="addChoiceOption()">+ Tambah opsi</button>`;
        const list = area.querySelector('.options-list');
        options.forEach((opt) => list.insertAdjacentHTML('beforeend', choiceOptionHtml(inputType, opt)));
    } else if(type === 'true_false') {
        const answer = data.answer_key?.answer ?? true;
        area.innerHTML = `
            <div class="type-note muted small">Siswa memilih Benar atau Salah. Kunci disimpan di server dan tidak ikut dikirim ke paket siswa.</div>
            <div class="tf-grid mt">
                <label class="tf-card row"><input type="radio" class="q-tf-answer" name="tf_bank" value="true" ${answer === true || answer === 'true' ? 'checked' : ''}> Kunci: Benar</label>
                <label class="tf-card row"><input type="radio" class="q-tf-answer" name="tf_bank" value="false" ${answer === false || answer === 'false' ? 'checked' : ''}> Kunci: Salah</label>
            </div>`;
    } else if(type === 'matching') {
        const rows = Array.isArray(data.options) && data.options.length ? data.options : [{label:'Item kiri 1',match:'Pasangan 1'},{label:'Item kiri 2',match:'Pasangan 2'}];
        area.innerHTML = `
            <div class="type-note muted small">Menjodohkan: isi item kiri dan pasangan benar di kanan. Di aplikasi siswa, pasangan kanan bisa diacak.</div>
            <div class="split-note mt"><b>Item / pernyataan</b><b>Pasangan benar</b></div>
            <div class="matching-list"></div>
            <button type="button" class="btn soft" onclick="addMatchingRow()">+ Tambah pasangan</button>`;
        const list = area.querySelector('.matching-list');
        rows.forEach((row) => list.insertAdjacentHTML('beforeend', matchingRowHtml(row)));
    } else if(type === 'short_answer') {
        let accepted = data.answer_key?.accepted || [];
        if(!Array.isArray(accepted)) accepted = [];
        const text = accepted.length ? accepted.join('; ') : (data.correct_text || '');
        area.innerHTML = `<div class="field"><label>Kunci jawaban singkat</label><input class="input q-accepted" placeholder="Contoh: SEB; safe exam browser" value="${esc(text)}"><p class="help">Pisahkan beberapa variasi jawaban benar dengan titik koma (;). Koreksi otomatis tidak membedakan huruf besar/kecil.</p></div>`;
    }
}

function choiceOptionHtml(inputType, opt = {}){
    return `<div class="option-row">
        <input class="opt-correct" name="correct_bank" type="${inputType}" ${opt.is_correct?'checked':''}>
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

function addChoiceOption(){
    const inputType = typeEl().value === 'multiple_choice' ? 'radio' : 'checkbox';
    document.querySelector('.options-list').insertAdjacentHTML('beforeend', choiceOptionHtml(inputType, {label:'',is_correct:false}));
}

function addMatchingRow(){
    document.querySelector('.matching-list').insertAdjacentHTML('beforeend', matchingRowHtml());
}

function collectChoiceOptions(){
    return [...document.querySelectorAll('.option-row')].map(row => ({
        label: row.querySelector('.opt-label').value,
        is_correct: row.querySelector('.opt-correct').checked
    })).filter(o => o.label.trim() !== '');
}

function collectMatchingOptions(){
    return [...document.querySelectorAll('.matching-row')].map(row => ({
        label: row.querySelector('.match-left').value,
        match: row.querySelector('.match-right').value
    })).filter(o => o.label.trim() !== '' && o.match.trim() !== '');
}

function collectAcceptedAnswers(){
    const value = document.querySelector('.q-accepted')?.value || '';
    return value.split(/[;\n]/).map(v => v.trim()).filter(Boolean);
}

function collectQuestion(includeAnswer = true){
    const type = typeEl().value;
    const q = {
        type,
        title: document.querySelector('.q-title').value,
        description: document.querySelector('.q-desc').value,
        points: parseFloat(document.querySelector('.q-points').value || '1'),
        correct_text: '',
        answer_key: {},
        options: []
    };

    if(type === 'multiple_choice' || type === 'multiple_choice_complex') q.options = collectChoiceOptions();
    if(type === 'matching') q.options = collectMatchingOptions();
    if(type === 'true_false') q.answer_key = {answer: (document.querySelector('.q-tf-answer:checked')?.value || 'true') === 'true'};
    if(type === 'short_answer') {
        const accepted = collectAcceptedAnswers();
        q.answer_key = {accepted};
        q.correct_text = accepted[0] || '';
    }
    return q;
}

function validateQuestion(q){
    if(!q.title.trim()) return 'Pertanyaan wajib diisi.';
    if(q.type === 'multiple_choice'){
        if(q.options.length < 2) return 'Pilihan ganda minimal punya 2 opsi.';
        if(q.options.filter(o=>o.is_correct).length !== 1) return 'Pilihan ganda harus punya tepat 1 kunci.';
    }
    if(q.type === 'multiple_choice_complex'){
        if(q.options.length < 2) return 'Pilihan ganda kompleks minimal punya 2 opsi.';
        if(q.options.filter(o=>o.is_correct).length < 1) return 'Pilihan ganda kompleks minimal punya 1 kunci.';
    }
    if(q.type === 'matching' && q.options.length < 2) return 'Menjodohkan minimal punya 2 pasangan lengkap.';
    if(q.type === 'short_answer' && (!q.answer_key.accepted || q.answer_key.accepted.length < 1)) return 'Jawaban singkat wajib punya minimal 1 kunci.';
    return null;
}

typeEl()?.addEventListener('change', () => renderAnswerArea());
hydrateQuestion(initialQuestion);

document.getElementById('bankQuestionForm').addEventListener('submit', function(e){
    const q = collectQuestion();
    const error = validateQuestion(q);
    if(error){ e.preventDefault(); alert(error); return; }
    document.getElementById('questionJson').value = JSON.stringify(q);
});
</script>
@endpush
