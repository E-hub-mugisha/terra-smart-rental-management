<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Services\LeaseLifecycle;
use App\Models\RentInvoice;
use App\Models\RentPaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeaseLifecycleController extends Controller
{
    /**
     * Admin lease dashboard.
     */
    public function index(Request $request)
    {
        $query = Lease::query()
            ->with(['tenant', 'unit.building'])
            ->withCount('invoices');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('lease_number', 'like', "%{$search}%")
                    ->orWhereHas('tenant', function ($tenantQuery) use ($search) {
                        $tenantQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('tenant_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('unit', function ($unitQuery) use ($search) {
                        $unitQuery->where('unit_number', 'like', "%{$search}%");
                    });
            });
        }

        $leases = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'all' => Lease::count(),
            'draft' => Lease::where('status', 'draft')->count(),
            'awaiting_acceptance' => Lease::where(
                'status',
                'pending_tenant_acceptance'
            )->count(),
            'pending_activation' => Lease::where(
                'status',
                'pending_activation'
            )->count(),
            'active' => Lease::where('status', 'active')->count(),
        ];

        return view('admin.leases.index', compact('leases', 'counts'));
    }

    /**
     * Display lease, invoices, and payment allocation history.
     */
    public function show(Lease $lease)
    {
        $lease->load([
            'tenant',
            'unit.building',
            'application',
            'invoices.allocations.payment',
        ]);

        $invoices = $lease->invoices;

        $invoiceSummary = [
            'count' => $invoices->count(),
            'total' => (float) $invoices->sum('total_amount'),
            'paid' => (float) $invoices->sum(function ($invoice) {
                return $this->confirmedAllocatedAmount($invoice);
            }),
            'balance' => (float) $invoices->sum(function ($invoice) {
                return max(
                    0,
                    (float) $invoice->total_amount
                        - $this->confirmedAllocatedAmount($invoice)
                );
            }),
        ];

        return view(
            'admin.leases.show',
            compact('lease', 'invoices', 'invoiceSummary')
        );
    }

    /**
     * Send the draft to the tenant (draft → pending_tenant_acceptance).
     */
    public function sendToTenant(Lease $lease)
    {
        app(LeaseLifecycle::class)->sendToTenant($lease);

        return back()->with('success', 'Lease sent. The tenant can now review and accept it in their portal.');
    }

    /**
     * Take an unaccepted lease back to draft.
     */
    public function recall(Lease $lease)
    {
        app(LeaseLifecycle::class)->recall($lease);

        return back()->with('success', 'Lease recalled to draft.');
    }

    /**
     * Generate the deposit and initial rent invoices.
     */
    public function generateRequiredInvoices(Lease $lease)
    {
        app(LeaseLifecycle::class)->generateRequiredInvoices($lease);

        return back()->with('success', 'Required invoices have been generated or already exist.');
    }
    /**
     * Record move-in confirmation.
     */
    public function confirmMoveIn(Lease $lease)
    {
        app(LeaseLifecycle::class)->confirmMoveIn($lease);

        return back()->with('success', 'Move-in has been confirmed.');
    }
    /**
     * Activate after verifying acceptance, required payments, move-in and availability.
     */
    public function activate(Lease $lease)
    {
        app(LeaseLifecycle::class)->activate($lease);

        return back()->with('success', 'Lease activated successfully. The unit is now marked as occupied.');
    }

    /**
     * Delete a lease that never took effect, with its unpaid invoices.
     */
    public function destroy(Lease $lease)
    {
        app(LeaseLifecycle::class)->delete($lease);

        return redirect()
            ->route('admin.leases.index')
            ->with('success', 'Lease deleted successfully.');
    }
    /**
     * Sum allocations belonging to confirmed payments only.
     */
    private function confirmedAllocatedAmount(RentInvoice $invoice): float
    {
        return (float) RentPaymentAllocation::query()
            ->where('rent_invoice_id', $invoice->id)
            ->whereHas('payment', function ($query) {
                $query->where('status', 'confirmed');
            })
            ->sum('amount');
    }

}