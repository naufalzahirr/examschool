@extends('layouts.app', ['title' => 'Tambah dari Bank Soal'])

@section('content')
<div class="between mb">
    <div>
        <h1>Pilih Soal dari Bank Soal</h1>
        <p class="muted">Ujian: <b>{{ $exam->title }}</b>. Soal yang dipilih akan disalin ke ujian, jadi aman walaupun Bank Soal diedit lagi nanti.</p>
    </div>
    <a class="btn" href="{{ route('exams.show', $exam) }}">Kembali ke Ujian</a>
</div>

<div class="card mb">
    <div class="step-list">
        <div class="step">
            <span class="step-no">1</span>
            <div><b>Cari soal</b><br><span class="muted small">Filter berdasarkan mapel, jenjang, jenis, level, atau pembuat.</span></div>
        </div>
        <div class="step">
            <span class="step-no">2</span>
            <div><b>Centang soal</b><br><span class="muted small">Pilih satu atau banyak soal. Soal yang sudah masuk akan ditandai.</span></div>
        </div>
        <div class="step">
            <span class="step-no">3</span>
            <div><b>Tambahkan ke ujian</b><br><span class="muted small">Setelah ditambahkan, cek jumlah soal lalu publish ujian.</span></div>
        </div>
    </div>
</div>

<div class="card data-card mb">
    <form class="table-toolbar" method="GET" action="{{ route('exams.question-bank.select', $exam) }}">
        <div class="table-title">
            <h2>Cari Bank Soal</h2>
            <p class="muted small mb0">Guru bisa memakai soal miliknya sendiri dan soal bersama yang dibagikan guru lain.</p>
        </div>
        <div class="table-tools">
            <div class="tool-field">
                <label>Mapel</label>
                <select class="input" name="subject" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['subjects'] as $subject)
                        <option value="{{ $subject }}" @selected(request('subject')===$subject)>{{ $subject }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Kelas</label>
                <select class="input" name="grade_level" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['grades'] as $grade)
                        <option value="{{ $grade }}" @selected(request('grade_level')===$grade)>{{ $grade }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Jenis</label>
                <select class="input" name="type" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['types'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type')===$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Level</label>
                <select class="input" name="difficulty" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['difficulties'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('difficulty')===$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field">
                <label>Pembuat</label>
                <select class="input" name="owner" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($filters['owners'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('owner')===$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field search">
                <label>Cari Cepat</label>
                <div class="live-search-wrap"><input class="input" data-live-search="selectBankTable" name="q" value="{{ request('q') }}" placeholder="Cari soal, kode, mapel, topik"></div>
            </div>
            <button class="btn primary">Cari</button>
            <a class="btn" href="{{ route('exams.question-bank.select', $exam) }}">Reset</a>
        </div>
    </form>
</div>

<form method="POST" action="{{ route('exams.question-bank.add', $exam) }}" class="card data-card">
    @csrf
    <div class="table-toolbar">
        <div class="table-title"><h2>Pilih Soal</h2><p class="muted small mb0">Ujian ini sudah punya <b>{{ $exam->questions_count ?? 0 }}</b> soal. Total tersedia dari filter ini: {{ $items->total() }} soal.</p></div>
        <div class="table-tools">
            <span class="badge info"><b id="selectedBankCount">0</b> dipilih</span>
            <button class="btn soft" type="button" id="selectVisibleQuestions">Pilih semua terlihat</button>
            <button class="btn primary">Tambahkan yang Dipilih</button>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table" id="selectBankTable">
            <thead><tr><th>Pilih</th><th>Soal</th><th>Jenis</th><th>Opsi/Kunci</th><th>Mapel/Topik</th><th>Akses/Pembuat</th><th>Poin</th></tr></thead>
            <tbody>
                @forelse($items as $item)
                    @php($alreadyAdded = in_array($item->id, $existingBankItemIds, true))
                    <tr>
                        <td>
                            @if($alreadyAdded)
                                <span class="badge info">Sudah masuk</span>
                            @else
                                <label class="check-pill" style="justify-content:center"><input class="bank-select" type="checkbox" name="question_bank_ids[]" value="{{ $item->id }}"></label>
                            @endif
                        </td>
                        <td><b>{{ $item->title }}</b><br><span class="muted small">{{ $item->question_code }}</span></td>
                        <td>{{ \App\Models\QuestionBankItem::typeLabels()[$item->type] ?? $item->type }}</td>
                        <td><span class="muted small">Opsi:</span> {{ Str::limit($item->optionsPreview() ?: '-', 90) }}<br><span class="muted small">Kunci:</span> <b>{{ Str::limit($item->answerPreview(), 90) }}</b></td>
                        <td>{{ $item->subject ?: '-' }}<br><span class="muted small">{{ $item->topic ?: '-' }} - {{ $item->grade_level ?: '-' }}</span></td>
                        <td>
                            @if($item->isSharedToSchool())
                                <span class="badge success">Bersama</span>
                            @else
                                <span class="badge draft">Pribadi</span>
                            @endif
                            <br><span class="muted small">{{ $item->teacher?->name ?: '-' }}</span>
                        </td>
                        <td>{{ $item->points }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada soal di bank.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-meta between">
        <div class="small muted">Terlihat: <b data-live-count="selectBankTable">{{ $items->count() }}</b> baris</div>
        <div class="row"><button class="btn primary">Tambahkan yang Dipilih</button><div>{{ $items->links() }}</div></div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = [...document.querySelectorAll('.bank-select')];
    const counter = document.getElementById('selectedBankCount');
    const updateCounter = () => {
        if(counter) counter.textContent = checkboxes.filter(item => item.checked).length;
    };

    checkboxes.forEach(item => item.addEventListener('change', updateCounter));
    document.getElementById('selectVisibleQuestions')?.addEventListener('click', () => {
        const visible = checkboxes.filter(item => item.closest('tr')?.style.display !== 'none');
        const shouldCheck = visible.some(item => !item.checked);
        visible.forEach(item => item.checked = shouldCheck);
        updateCounter();
    });
    updateCounter();
});
</script>
@endpush
