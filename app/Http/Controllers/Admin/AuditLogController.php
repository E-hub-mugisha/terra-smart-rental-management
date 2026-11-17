<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/** Platform activity / audit trail for Terra administrators (brief §24, §27). */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with('user:id,name,role')
            ->when($request->filled('user'), fn ($q) => $q->where('user_id', (int) $request->query('user')))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->query('event')))
            ->when($request->filled('type'), fn ($q) => $q->where('auditable_type', 'like', '%\\' . $request->query('type')))
            ->when($request->filled('subject'), fn ($q) => $q->where('auditable_id', (int) $request->query('subject')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->query('to')))
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'events' => AuditLog::query()->distinct()->orderBy('event')->pluck('event'),
            'types' => AuditLog::query()->whereNotNull('auditable_type')->distinct()->pluck('auditable_type')->map(fn ($t) => class_basename($t))->unique()->sort()->values(),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'failedLogins24h' => AuditLog::where('event', 'login_failed')->where('created_at', '>=', now()->subDay())->count(),
        ]);
    }
}
