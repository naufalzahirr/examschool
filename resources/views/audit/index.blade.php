@extends('layouts.app', ['title' => 'Audit Log'])

@section('content')
<div class="between mb">
    <div>
        <h1>Audit Log</h1>
        <p class="muted">Riwayat aksi penting: login, publish ujian, reset device, import, dan perubahan akun.</p>
    </div>
</div>

<div class="card data-card">
    <div class="table-toolbar">
        <div class="table-title">
            <h2>Riwayat Aktivitas</h2>
            <p class="muted small mb0">{{ $logs->total() }} catatan tersimpan.</p>
        </div>
        <form class="table-tools" method="GET" action="{{ route('audit.index') }}">
            <div class="tool-field">
                <label>Event</label>
                <select class="input" name="event" onchange="this.form.submit()">
                    <option value="">Semua event</option>
                    @foreach($events as $event)
                        <option value="{{ $event }}" @selected(request('event') === $event)>{{ $event }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tool-field search">
                <label>Cari Cepat</label>
                <div class="live-search-wrap">
                    <input class="input" data-live-search="auditTable" name="q"
                           value="{{ request('q') }}" placeholder="Cari event, user, IP">
                </div>
            </div>
            <button class="btn primary">Cari</button>
            @if(request('q') || request('event'))
                <a class="btn" href="{{ route('audit.index') }}">Reset</a>
            @else
                <button class="btn" type="button" data-live-reset="auditTable">Clear</button>
            @endif
        </form>
    </div>

    <div class="table-wrap">
        <table class="table" id="auditTable">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Event</th>
                    <th>Pengguna</th>
                    <th>IP</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                @php
                    $props = $log->properties ?? [];
                    $eventColor = match(true) {
                        str_contains($log->event, 'delete') || str_contains($log->event, 'reset') => 'danger',
                        str_contains($log->event, 'publish') || str_contains($log->event, 'create') => 'published',
                        str_contains($log->event, 'login') => 'info',
                        default => 'draft',
                    };
                @endphp
                <tr>
                    <td class="small">
                        <b>{{ $log->created_at->format('d M Y') }}</b><br>
                        <span class="muted">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $eventColor }}">{{ $log->event }}</span>
                    </td>
                    <td>
                        <b>{{ $log->user?->name ?: 'System' }}</b><br>
                        <span class="muted small">{{ $log->user?->email ?: '-' }}</span>
                    </td>
                    <td class="small muted">{{ $log->ip_address ?: '-' }}</td>
                    <td>
                        @if(!empty($props))
                            <div style="display:flex;flex-wrap:wrap;gap:.3rem;max-width:380px">
                                @foreach($props as $key => $val)
                                    <span style="display:inline-flex;align-items:center;gap:.25rem;background:#f6f7f9;border-radius:.35rem;padding:.2rem .5rem;font-size:12px">
                                        <span class="muted">{{ $key }}:</span>
                                        <b>{{ is_array($val) ? implode(', ', array_slice($val, 0, 3)) : Str::limit((string) $val, 40) }}</b>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="muted small">–</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr data-empty-row>
                    <td colspan="5" class="muted">Belum ada audit log.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-meta between">
        <div class="small muted">
            Terlihat: <b data-live-count="auditTable">{{ $logs->count() }}</b> baris
        </div>
        <div>{{ $logs->links() }}</div>
    </div>
</div>
@endsection
