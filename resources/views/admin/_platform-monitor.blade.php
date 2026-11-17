{{-- Platform monitoring for Terra administrators (brief §27): expiring leases, activity and system alerts. --}}
@php
    use App\Models\{Lease, MaintenanceRequest, PaymentIntent, RentPayment, Document, AuditLog};
    use Illuminate\Support\Facades\{DB, Schema};

    $expiring = Lease::where('status', 'active')->whereBetween('end_date', [now()->startOfDay(), now()->addDays(60)])->with(['tenant', 'unit.building.property'])->orderBy('end_date')->get();
    $monitor = [
        'active_leases' => Lease::where('status', 'active')->count(),
        'expiring' => $expiring->count(),
        'open_maintenance' => MaintenanceRequest::whereNotIn('status', ['closed', 'rejected', 'resolved'])->count(),
        'emergency' => MaintenanceRequest::whereNotIn('status', ['closed', 'rejected', 'resolved'])->where('priority', 'emergency')->count(),
        'online_ok' => PaymentIntent::where('status', 'successful')->whereDate('created_at', today())->count(),
        'online_failed' => PaymentIntent::whereIn('status', ['failed', 'expired'])->whereDate('created_at', today())->count(),
        'to_verify' => RentPayment::where('status', 'pending')->count(),
        'kyc_pending' => Document::where('status', 'pending')->count(),
        'failed_logins' => AuditLog::whereIn('event', ['login_failed', 'api_login_failed', 'two_factor_failed'])->where('created_at', '>=', now()->subDay())->count(),
    ];

    // System alerts derived from live state.
    $alerts = [];
    if (Schema::hasTable('jobs') && DB::table('jobs')->where('created_at', '<', now()->subMinutes(15)->timestamp)->exists()) {
        $alerts[] = ['danger', 'Queued emails and SMS are waiting for more than 15 minutes — check that `php artisan queue:work` is running.'];
    }
    if (Schema::hasTable('failed_jobs') && ($failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count())) {
        $alerts[] = ['warning', $failed . ' background job(s) failed in the last 24 hours (often email delivery). Run `php artisan queue:failed` to inspect.'];
    }
    if ($monitor['failed_logins'] >= 10) {
        $alerts[] = ['warning', $monitor['failed_logins'] . ' failed sign-in attempts in 24 hours.'];
    }
    if (config('payments.default') === 'sandbox') {
        $alerts[] = ['info', 'Online payments are in test mode (sandbox). Set PAYMENT_PROVIDER and the MoMo keys in .env to take real payments.'];
    } elseif (config('payments.default') === 'mtn_momo' && ! config('payments.providers.mtn_momo.subscription_key')) {
        $alerts[] = ['danger', 'MTN MoMo is selected but not configured — tenants cannot pay online.'];
    }
    if ($monitor['emergency']) {
        $alerts[] = ['danger', $monitor['emergency'] . ' emergency maintenance request(s) are open.'];
    }
    $activity = AuditLog::with('user:id,name')->latest('id')->limit(8)->get();
@endphp

<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong style="color:#19265d;"><i class="bi bi-activity me-1"></i> Platform monitor</strong>
        <a href="{{ route('admin.audit-logs.index') }}" class="small">Full activity log</a>
    </div>
    <div class="card-body">
        @foreach($alerts as [$tone, $text])
            <div class="alert alert-{{ $tone }} py-2 small mb-2"><i class="bi bi-{{ $tone === 'info' ? 'info-circle' : 'exclamation-triangle' }} me-1"></i>{!! preg_replace('/`([^`]+)`/', '<code>$1</code>', e($text)) !!}</div>
        @endforeach

        <div class="row g-2 mb-3 mt-1">
            @foreach([
                ['Active leases', $monitor['active_leases'], route('admin.leases.index')],
                ['Expiring in 60 days', $monitor['expiring'], route('reports.show', 'expiring_leases')],
                ['Open maintenance', $monitor['open_maintenance'], route('reports.show', 'maintenance_requests')],
                ['Online payments today', $monitor['online_ok'] . ' ✓ / ' . $monitor['online_failed'] . ' ✕', route('finance.payments.index')],
                ['Payments to verify', $monitor['to_verify'], route('admin.payments.index')],
                ['Documents to verify', $monitor['kyc_pending'], route('documents.index', ['status' => 'pending'])],
            ] as [$label, $value, $url])
                <div class="col-6 col-lg-2">
                    <a href="{{ $url }}" class="d-block p-2 rounded-3 text-decoration-none h-100" style="background:#f6f7fb;">
                        <div class="small text-secondary">{{ $label }}</div><div class="fw-bold fs-5" style="color:#19265d;">{{ $value }}</div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="small fw-bold text-secondary text-uppercase mb-2">Leases expiring soon</div>
                @forelse($expiring->take(6) as $l)
                    <a href="{{ route('admin.leases.show', $l) }}" class="d-flex justify-content-between small py-1 border-bottom text-decoration-none text-reset">
                        <span>{{ $l->tenant?->full_name }} · {{ $l->unit?->building?->property?->name }} {{ $l->unit?->unit_number }}</span>
                        <span class="{{ $l->end_date->diffInDays(now()) < 15 ? 'text-danger fw-semibold' : 'text-secondary' }}">{{ $l->end_date->format('d M') }}</span>
                    </a>
                @empty
                    <div class="small text-secondary">None in the next 60 days.</div>
                @endforelse
            </div>
            <div class="col-lg-6">
                <div class="small fw-bold text-secondary text-uppercase mb-2">Recent platform activity</div>
                @forelse($activity as $a)
                    <div class="d-flex justify-content-between small py-1 border-bottom">
                        <span class="text-truncate me-2">{{ $a->user?->name ?? 'System' }} · {{ str_replace('_', ' ', $a->event) }} {{ $a->auditable_type ? class_basename($a->auditable_type) . ' #' . $a->auditable_id : '' }}</span>
                        <span class="text-secondary text-nowrap">{{ $a->created_at?->diffForHumans(null, true) }}</span>
                    </div>
                @empty
                    <div class="small text-secondary">No activity yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
