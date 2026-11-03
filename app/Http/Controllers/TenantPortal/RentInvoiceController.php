<?php

namespace App\Http\Controllers\TenantPortal;

use App\Http\Controllers\Controller;
use App\Models\RentInvoice;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class RentInvoiceController extends Controller
{
    /**
     * Find the tenant account associated with the signed-in user.
     *
     * Assumption: tenants.user_id references users.id.
     */
    private function tenant()
    {
        return Tenant::query()
            ->where('user_id', auth()->id())
            ->firstOrFail();
    }

    /**
     * Only return invoices owned by the authenticated tenant.
     */
    private function tenantInvoices(): Builder
    {
        $tenant = $this->tenant();

        return RentInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->with(['lease', 'unit']);
    }

    /**
     * Display the tenant's invoice list.
     */
    public function index(Request $request)
    {
        $query = $this->tenantInvoices();

        if ($request->filled('status')) {
            $status = $request->input('status');

            if (in_array($status, ['unpaid', 'partial', 'paid', 'overdue'], true)) {
                if ($status === 'overdue') {
                    $query->where('balance_due', '>', 0)
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now()->toDateString());
                } elseif ($status === 'unpaid') {
                    $query->where('balance_due', '>', 0)
                        ->where(function (Builder $builder) {
                            $builder->whereNull('amount_paid')
                                ->orWhere('amount_paid', '<=', 0);
                        })
                        ->where(function (Builder $builder) {
                            $builder->whereNull('due_date')
                                ->orWhereDate('due_date', '>=', now()->toDateString());
                        });
                } elseif ($status === 'partial') {
                    $query->where('amount_paid', '>', 0)
                        ->where('balance_due', '>', 0)
                        ->where(function (Builder $builder) {
                            $builder->whereNull('due_date')
                                ->orWhereDate('due_date', '>=', now()->toDateString());
                        });
                } elseif ($status === 'paid') {
                    $query->where('balance_due', '<=', 0);
                }
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function (Builder $builder) use ($search) {
                $builder->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('unit', function (Builder $unitQuery) use ($search) {
                        $unitQuery->where('unit_number', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query
            ->orderByRaw("
                CASE
                    WHEN balance_due > 0
                        AND due_date < CURDATE() THEN 0
                    WHEN balance_due > 0 THEN 1
                    ELSE 2
                END
            ")
            ->orderByDesc('issue_date')
            ->paginate(10)
            ->withQueryString();

        $summaryQuery = $this->tenantInvoices();

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'outstanding' => (float) (clone $summaryQuery)
                ->where('balance_due', '>', 0)
                ->sum('balance_due'),
            'paid' => (float) (clone $summaryQuery)
                ->sum('amount_paid'),
            'overdue' => (clone $summaryQuery)
                ->where('balance_due', '>', 0)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
        ];

        return view('tenant-portal.rent-invoices.index', compact(
            'invoices',
            'summary'
        ));
    }

    /**
     * Show one invoice belonging to the signed-in tenant.
     */
    public function show(int $invoice)
    {
        $rentInvoice = $this->tenantInvoices()
            ->with(['allocations'])
            ->whereKey($invoice)
            ->firstOrFail();

        return view('tenant-portal.rent-invoices.show', compact(
            'rentInvoice'
        ));
    }
}
