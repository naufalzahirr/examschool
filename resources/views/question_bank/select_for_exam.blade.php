@extends('layouts.app', ['title' => 'Tambah dari Bank Soal'])

@section('content')
<div class="between mb">
    <div>
        <h1>Pilih Soal dari Bank Soal</h1>
        <p class="muted">Ujian: <b>{{ $exam->title }}</b>. Pilih satu paket Bank Soal, lalu semua soal aktif di dalam paket itu akan disalin ke ujian.</p>
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
            <div><b>Pilih satu paket</b><br><span class="muted small">Satu paket Bank Soal bisa berisi banyak soal.</span></div>
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
        <div class="table-title"><h2>Pilih Paket Bank Soal</h2><p class="muted small mb0">Ujian ini sudah punya <b>{{ $exam->questions_count ?? 0 }}</b> soal. Total paket dari filter ini: {{ $bankGroups->total() }}.</p></div>
        <div class="table-tools">
            <span class="badge info" id="selectedBankLabel">Belum memilih paket</span>
            <button class="btn primary">Gunakan Paket Ini</button>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table" id="selectBankTable">
            <thead><tr><th>Pilih</th><th>Paket Bank Soal</th><th>Jumlah Soal</th><th>Jenis</th><th>Level</th><th>Akses/Pembuat</th><th>Total Poin</th></tr></thead>
            <tbody>
                @forelse($bankGroups as $group)
                    <tr>
                        <td>
                            @if(($group['available_count'] ?? 0) < 1)
                                <span class="badge info">Sudah masuk</span>
                            @else
                                <label class="check-pill" style="justify-content:center">
                                    <input class="bank-select" type="radio" name="bank_key" value="{{ $group['key'] }}" data-title="{{ $group['title'] }}">
                                </label>
                            @endif
                        </td>
                        <td>
                            <b>{{ $group['title'] }}</b><br>
                            <span class="muted small">{{ $group['subject'] ?: '-' }} / {{ $group['grade_level'] ?: '-' }} / {{ $group['topic'] ?: '-' }}</span>
                        </td>
                        <td>
                            <b>{{ $group['questions_count'] }}</b> soal<br>
                            <span class="muted small">{{ $group['available_count'] }} belum masuk ujian ini</span>
                        </td>
                        <td>{{ $group['types'] ?: '-' }}</td>
                        <td>{{ $group['difficulties'] ?: '-' }}</td>
                        <td>
                            @if(($group['visibility'] ?? '') === \App\Models\QuestionBankItem::VISIBILITY_SCHOOL)
                                <span class="badge success">Bersama</span>
                            @else
                                <span class="badge draft">Pribadi</span>
                            @endif
                            <br><span class="muted small">{{ $group['teacher_name'] ?: '-' }}</span>
                        </td>
                        <td>{{ number_format((float) $group['total_points'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada paket Bank Soal yang cocok dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-meta between">
        <div class="small muted">Terlihat: <b data-live-count="selectBankTable">{{ $bankGroups->count() }}</b> paket</div>
        <div class="row"><button class="btn primary">Gunakan Paket Ini</button><div>{{ $bankGroups->links() }}</div></div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const radios = [...document.querySelectorAll('.bank-select')];
    const label = document.getElementById('selectedBankLabel');
    const updateCounter = () => {
        const selected = radios.find(item => item.checked);
        if(label) label.textContent = selected ? selected.dataset.title : 'Belum memilih paket';
    };

    radios.forEach(item => item.addEventListener('change', updateCounter));
    updateCounter();
});
</script>
@endpush
