<?php

namespace App\Http\Controllers\TenantPortal;

use App\Http\Controllers\Controller;
use App\Models\RentPayment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RentPaymentController extends Controller
{
    /**
     * Resolve the authenticated user's tenant record.
     *
     * Assumes tenants.user_id references users.id.
     */
    private function tenant(): Tenant
    {
        return Tenant::query()
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    /**
     * Base query scoped to the authenticated tenant.
     */
    private function tenantPayments(): Builder
    {
        return RentPayment::query()
            ->where('tenant_id', $this->tenant()->id)
            ->with([
                'lease',
                'unit',
                'allocations.invoice',
            ]);
    }

    /**
     * List the tenant's payments.
     */
    public function index(Request $request)
    {
        $query = $this->tenantPayments();

        if ($request->filled('status')) {
            $allowedStatuses = [
                'confirmed',
                'pending',
                'failed',
                'rejected',
                'cancelled',
            ];

            if (in_array($request->status, $allowedStatuses, true)) {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('method')) {
            $allowedMethods = [
                'cash',
                'bank_transfer',
                'mobile_money',
                'card',
                'payment_gateway',
                'other',
            ];

            if (in_array($request->method, $allowedMethods, true)) {
                $query->where('payment_method', $request->method);
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('unit', function (Builder $unitQuery) use ($search) {
                        $unitQuery->where(
                            'unit_number',
                            'like',
                            "%{$search}%"
                        );
                    });
            });
        }

        $payments = $query
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $tenantId = $this->tenant()->id;

        $summaryQuery = RentPayment::query()
            ->where('tenant_id', $tenantId);

        $summary = [
            'total' => (clone $summaryQuery)->count(),

            'confirmed_amount' => (float) (clone $summaryQuery)
                ->where('status', 'confirmed')
                ->sum('amount'),

            'confirmed_count' => (clone $summaryQuery)
                ->where('status', 'confirmed')
                ->count(),

            'pending_count' => (clone $summaryQuery)
                ->where('status', 'pending')
                ->count(),
        ];

        return view('tenant-portal.payments.index', compact(
            'payments',
            'summary'
        ));
    }

    /**
     * Display a single payment owned by the authenticated tenant.
     */
    public function show(int $payment)
    {
        $rentPayment = $this->tenantPayments()
            ->whereKey($payment)
            ->firstOrFail();

        return view('tenant-portal.payments.show', compact(
            'rentPayment'
        ));
    }
}
