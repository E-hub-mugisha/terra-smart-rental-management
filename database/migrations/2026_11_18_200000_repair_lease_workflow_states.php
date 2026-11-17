<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repairs leases left in impossible states by the old, inconsistent workflow
 * (see App\Services\LeaseLifecycle for the single flow now used everywhere):
 *
 *  - Drafts the tenant already accepted (stuck under the old manager flow)
 *    move on to pending_activation, where staff can invoice and activate them.
 *  - Units under an active lease that were still marked available/reserved
 *    (old admin approval activated leases without touching the unit) are
 *    marked occupied.
 *
 * Unaccepted drafts stay as drafts: staff now send them with "Send to tenant".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('leases')
            ->where('status', 'draft')
            ->whereNotNull('tenant_accepted_at')
            ->whereNull('deleted_at')
            ->update(['status' => 'pending_activation', 'updated_at' => now()]);

        $unitIds = DB::table('leases')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->pluck('unit_id');

        DB::table('units')
            ->whereIn('id', $unitIds)
            ->whereIn('status', ['available', 'reserved'])
            ->update(['status' => 'occupied', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Data repair only; the previous states were invalid, so there is nothing to restore.
    }
};
