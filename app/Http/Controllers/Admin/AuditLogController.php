<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with('user')->latest();

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($event = trim((string) $request->get('event'))) {
            $query->where('event', $event);
        }

        $logs = $query->paginate(50)->withQueryString();
        $events = AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event');

        return view('audit.index', compact('logs', 'events'));
    }
}
