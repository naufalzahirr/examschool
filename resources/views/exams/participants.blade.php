@extends('layouts.app', ['title' => 'Peserta — ' . $exam->title])

@section('content')
@include('exams._workspace', ['tab' => 'peserta'])

{{-- Kelas + sinkron --}}
<div class="card mb">
    <div class="between">
        <div>
            <h2 class="mb0" style="font-size:15px">Kelas Peserta</h2>
            <p class="muted small mb0">Peserta diambil otomatis dari siswa aktif di kelas ini. Klik Sinkron setelah menambah kelas baru.</p>
        </div>
        <div class="row">
            <a class="btn ghost" href="{{ route('exams.participants.import', $exam) }}" style="font-size:13px">Import Peserta</a>
            <form method="POST" action="{{ route('exams.participants.syncClassrooms', $exam) }}">
                @csrf
                <button class="btn soft" style="font-size:13px">↻ Sinkron dari Kelas</button>
            </form>
        </div>
    </div>
    <div class="row" style="margin-top:.85rem;flex-wrap:wrap;gap:.35rem">
        @forelse($exam->classrooms as $classroom)
            <span class="badge">{{ $classroom->nama_kelas }}</span>
        @empty
            <span class="muted small">Belum ada kelas. <a href="{{ route('exams.edit', $exam) }}" style="color:var(--primary)">Pilih kelas →</a></span>
        @endforelse
    </div>
</div>

{{-- Tabel --}}
<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2 style="font-size:15px">{{ $participants->total() }} Peserta Terdaftar</h2>
        </div>
        <form class="table-tools" method="GET" action="{{ route('exams.participants', $exam) }}">
            <div class="tool-field">
                <label>Status</label>
                <select class="input" name="status" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    <option value="assigned"       @selected(request('status') === 'assigned')>Belum login</option>
                    <option value="download_ready" @selected(request('status') === 'download_ready')>Siap download</option>
                    <option value="downloaded"     @selected(request('status') === 'downloaded')>Paket terunduh</option>
                    <option value="unlocked"       @selected(request('status') === 'unlocked')>Soal terbuka</option>
                    <option value="in_progress"    @selected(request('status') === 'in_progress')>Mengerjakan</option>
                    <option value="locked"         @selected(request('status') === 'locked')>Terkunci</option>
                    <option value="synced"         @selected(request('status') === 'synced')>Tersinkron</option>
                    <option value="submitted"      @selected(request('status') === 'submitted')>Sudah submit</option>
                </select>
            </div>
            <div class="tool-field">
                <label>Kelas</label>
                <select class="input" name="classroom_id" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($exam->classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field search">
                <label>Cari</label>
                <div class="live-search-wrap">
                    <input class="input" data-live-search="participantsTable" name="q" value="{{ request('q') }}" placeholder="NIS, nama, kelas...">
                </div>
            </div>
            <button class="btn primary" style="align-self:flex-end">Cari</button>
            @if(request('q') || request('status') || request('classroom_id'))
                <a class="btn ghost" href="{{ route('exams.participants', $exam) }}" style="align-self:flex-end">Reset</a>
            @else
                <button class="btn ghost" type="button" data-live-reset="participantsTable" style="align-self:flex-end">Clear</button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="table" id="participantsTable">
            <thead>
                <tr>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Nilai</th>
                    <th>Submit</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($participants as $p)
                @php
                $statusLabel = match($p->status) {
                    'assigned'=>'Belum login','download_ready'=>'Siap download','downloading'=>'Mengunduh...',
                    'downloaded'=>'Paket terunduh','unlocked'=>'Soal terbuka','in_progress'=>'Mengerjakan',
                    'synced'=>'Tersinkron','submitted'=>'Sudah submit',default=>$p->status,
                };
                @endphp
                <tr>
                    <td><span class="badge" style="font-size:11px">{{ $p->student?->nis ?: '–' }}</span></td>
                    <td><b style="font-size:13px">{{ $p->student?->name ?: 'Siswa dihapus' }}</b></td>
                    <td class="small muted">{{ $p->student?->classroom?->nama_kelas ?: ($p->student?->class_name ?: '–') }}</td>
                    <td><span class="badge {{ $p->status }}" style="font-size:11px">{{ $statusLabel }}</span></td>
                    <td>
                        @if($p->score !== null)
                            <b style="color:{{ $p->score >= 75 ? 'var(--success)' : ($p->score >= 60 ? 'var(--warning)' : 'var(--danger)') }}">{{ number_format((float)$p->score, 1) }}</b>
                        @else
                            <span class="muted">–</span>
                        @endif
                    </td>
                    <td class="small muted">{{ optional($p->submitted_at)->format('d M Y H:i') ?: '–' }}</td>
                    <td>
                        <div class="row" style="gap:.35rem;flex-wrap:nowrap">
                            <form method="POST" action="{{ route('exams.participants.resetDevice', [$exam, $p]) }}"
                                  onsubmit="return confirm('Reset perangkat?\n\nHanya menghapus kunci HP siswa.\nJawaban dan nilai TIDAK dihapus.')">
                                @csrf
                                <button class="btn warning" style="font-size:11px;padding:.3rem .6rem" title="Pakai jika siswa ganti HP">Ganti HP</button>
                            </form>
                            <form method="POST" action="{{ route('exams.participants.resetAttempt', [$exam, $p]) }}"
                                  onsubmit="return confirm('Reset ujian?\n\nMENGHAPUS jawaban, nilai, status, dan kunci perangkat.\nSiswa harus mulai dari awal.')">
                                @csrf
                                <button class="btn danger" style="font-size:11px;padding:.3rem .6rem">Ulangi</button>
                            </form>
                            @if(in_array($p->status, ['assigned','download_ready']))
                                <form method="POST" action="{{ route('exams.participants.remove', [$exam, $p]) }}"
                                      onsubmit="return confirm('Hapus {{ $p->student?->name ?? 'siswa ini' }} dari ujian?\n\nSiswa dikeluarkan dari daftar peserta.')">
                                    @csrf @method('DELETE')
                                    <button class="btn ghost" style="font-size:11px;padding:.3rem .6rem" title="Hapus dari peserta">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr data-empty-row>
                    <td colspan="7" style="text-align:center;padding:2.5rem;color:var(--muted)">Belum ada peserta.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-meta between">
        <div class="small muted">
            Terlihat: <b data-live-count="participantsTable">{{ $participants->count() }}</b> baris
            &nbsp;·&nbsp;
            <span class="help"><b>Ganti HP</b>: hapus kunci perangkat saja &nbsp;·&nbsp; <b>Ulangi</b>: hapus semua jawaban &amp; nilai &nbsp;·&nbsp; <b>Hapus</b>: keluarkan dari peserta (hanya jika belum ada aktivitas)</span>
        </div>
        <div>{{ $participants->links() }}</div>
    </div>
</div>
@endsection
