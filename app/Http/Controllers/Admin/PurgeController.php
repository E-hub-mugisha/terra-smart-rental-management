<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DataPurge;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** "Delete permanently": removes demo/test records and everything linked to them. */
class PurgeController extends Controller
{
    /** Where to land after the record is gone. */
    private const INDEX_ROUTES = [
        'owner' => 'admin.owners.index',
        'manager' => 'admin.managers.index',
        'property' => 'admin.properties.index',
        'tenant' => 'admin.tenants.index',
        'application' => 'admin.tenant-applications.index',
        'lease' => 'admin.leases.index',
        'invoice' => 'admin.rent-invoices.index',
        'payment' => 'admin.payments.index',
        'expense' => 'admin.property-expenses.index',
        'contract' => 'admin.subscriptions.contracts.index',
    ];

    public function destroy(Request $request, string $type, int $id, DataPurge $purge): RedirectResponse
    {
        $class = DataPurge::TYPES[$type] ?? abort(404);

        $request->validate(
            ['confirm' => ['required', 'in:DELETE']],
            ['confirm.*' => 'Type DELETE to confirm a permanent delete.']
        );

        $query = in_array(SoftDeletes::class, class_uses_recursive($class), true) ? $class::withTrashed() : $class::query();
        $record = $query->findOrFail($id);

        $purge->purge($record, $request->user()->id);

        return redirect()
            ->route(self::INDEX_ROUTES[$type])
            ->with('success', 'The record and all of its linked data were permanently deleted.');
    }
}
