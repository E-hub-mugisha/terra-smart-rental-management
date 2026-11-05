<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display the administrator dashboard.
     */
    public function index()
    {
        $tables = [
            'users' => 'users',
            'owners' => 'property_owners',
            'managers' => 'property_managers',
            'tenants' => 'tenants',
            'properties' => 'properties',
            'buildings' => 'buildings',
            'units' => 'units',
            'applications' => 'tenant_applications',
            'leases' => 'leases',
            'payments' => $this->firstExistingTable(['rent_payments', 'payments']),
            'invoices' => 'rent_invoices',
            'expenses' => 'property_expenses',
            'maintenance' => 'maintenance_requests',
        ];

        // General platform statistics
        $stats = [
            'users' => $this->tableCount($tables['users']),
            'owners' => $this->tableCount($tables['owners']),
            'managers' => $this->tableCount($tables['managers']),
            'tenants' => $this->tableCount($tables['tenants']),
            'properties' => $this->tableCount($tables['properties']),
            'buildings' => $this->tableCount($tables['buildings']),
            'units' => $this->tableCount($tables['units']),
            'applications' => $this->tableCount($tables['applications']),
            'leases' => $this->tableCount($tables['leases']),
            'payments' => $this->tableCount($tables['payments']),
            'invoices' => $this->tableCount($tables['invoices']),
            'expenses' => $this->tableCount($tables['expenses']),
            'maintenance' => $this->tableCount($tables['maintenance']),
        ];

        // Pending and active records
        $stats['pending_applications'] = $this->countByStatus(
            $tables['applications'],
            ['pending']
        );

        $stats['active_leases'] = $this->countByStatus(
            $tables['leases'],
            ['active']
        );

        $stats['pending_invoices'] = $this->countByStatus(
            $tables['invoices'],
            ['pending', 'unpaid', 'partially_paid']
        );

        $stats['overdue_invoices'] = $this->countByStatus(
            $tables['invoices'],
            ['overdue']
        );

        $stats['open_maintenance'] = $this->countByStatus(
            $tables['maintenance'],
            ['open', 'in_progress', 'scheduled']
        );

        $stats['pending_properties'] = $this->countByStatus(
            $tables['properties'],
            ['pending', 'pending_approval']
        );

        // Financial totals, kept separate by currency
        $collectionsByCurrency = $this->sumByCurrency($tables['payments']);

        $expensesByCurrency = $this->sumByCurrency($tables['expenses']);

        $outstandingByCurrency = $this->sumByCurrency(
            $tables['invoices'],
            ['balance', 'balance_due', 'outstanding_amount', 'amount_due']
        );

        // Monthly collection chart
        $monthlyCollections = $this->monthlyCollections($tables['payments']);

        // Recent activity
        $recentUsers = $this->recentRecords(
            $tables['users'],
            ['name', 'email', 'role', 'created_at']
        );

        $recentProperties = $this->recentRecords(
            $tables['properties'],
            ['name', 'property_code', 'status', 'created_at']
        );

        $recentPayments = $this->recentRecords(
            $tables['payments'],
            ['amount', 'payment_amount', 'amount_paid', 'currency', 'status', 'created_at']
        );

        return view('admin.dashboard', compact(
            'stats',
            'collectionsByCurrency',
            'expensesByCurrency',
            'outstandingByCurrency',
            'monthlyCollections',
            'recentUsers',
            'recentProperties',
            'recentPayments'
        ));
    }

    /**
     * Check whether a table exists and return its record count.
     */
    private function tableCount(?string $table): int
    {
        if (!$table || !Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)->count();
    }

    /**
     * Find the first existing table from a list of candidates.
     */
    private function firstExistingTable(array $candidates): ?string
    {
        foreach ($candidates as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    /**
     * Count records matching one of the supplied statuses.
     */
    private function countByStatus(?string $table, array $statuses): int
    {
        if (
            !$table ||
            !Schema::hasTable($table) ||
            !Schema::hasColumn($table, 'status')
        ) {
            return 0;
        }

        return DB::table($table)
            ->whereIn('status', $statuses)
            ->count();
    }

    /**
     * Find the first available column from a list of candidates.
     */
    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        if (!Schema::hasTable($table)) {
            return null;
        }

        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Calculate financial totals grouped by currency.
     *
     * Amounts in different currencies are never combined.
     */
    private function sumByCurrency(
        ?string $table,
        array $amountCandidates = ['amount', 'payment_amount', 'amount_paid', 'total_amount']
    ): array {
        if (!$table || !Schema::hasTable($table)) {
            return [];
        }

        $amountColumn = $this->firstExistingColumn($table, $amountCandidates);

        if (!$amountColumn) {
            return [];
        }

        $currencyColumn = $this->firstExistingColumn($table, ['currency']);

        if (!$currencyColumn) {
            return [
                'RWF' => (float) DB::table($table)->sum($amountColumn),
            ];
        }

        return DB::table($table)
            ->selectRaw("currency, SUM(`{$amountColumn}`) as total")
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(fn ($amount) => (float) $amount)
            ->toArray();
    }

    /**
     * Build a six-month rent collection chart.
     */
    private function monthlyCollections(?string $table): array
    {
        $months = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);

            $months->put($date->format('Y-m'), [
                'label' => $date->format('M Y'),
                'RWF' => 0,
                'USD' => 0,
            ]);
        }

        if (
            !$table ||
            !Schema::hasTable($table) ||
            !Schema::hasColumn($table, 'created_at')
        ) {
            return [
                'labels' => $months->pluck('label')->values()->all(),
                'RWF' => $months->pluck('RWF')->values()->all(),
                'USD' => $months->pluck('USD')->values()->all(),
            ];
        }

        $amountColumn = $this->firstExistingColumn(
            $table,
            ['amount', 'payment_amount', 'amount_paid', 'total_amount']
        );

        if (!$amountColumn) {
            return [
                'labels' => $months->pluck('label')->values()->all(),
                'RWF' => $months->pluck('RWF')->values()->all(),
                'USD' => $months->pluck('USD')->values()->all(),
            ];
        }

        $currencyColumn = $this->firstExistingColumn($table, ['currency']);

        $records = DB::table($table)
            ->whereBetween('created_at', [
                now()->startOfMonth()->subMonths(5),
                now()->endOfMonth(),
            ])
            ->get();

        foreach ($records as $record) {
            $date = Carbon::parse($record->created_at);
            $key = $date->format('Y-m');

            if (!$months->has($key)) {
                continue;
            }

            $currency = $currencyColumn
                ? strtoupper($record->{$currencyColumn} ?? 'RWF')
                : 'RWF';

            if (!in_array($currency, ['RWF', 'USD'], true)) {
                continue;
            }

            $month = $months->get($key);
            $month[$currency] += (float) ($record->{$amountColumn} ?? 0);

            $months->put($key, $month);
        }

        return [
            'labels' => $months->pluck('label')->values()->all(),
            'RWF' => $months->pluck('RWF')->values()->all(),
            'USD' => $months->pluck('USD')->values()->all(),
        ];
    }

    /**
     * Get recent records while selecting only columns that exist.
     */
    private function recentRecords(?string $table, array $preferredColumns)
    {
        if (
            !$table ||
            !Schema::hasTable($table) ||
            !Schema::hasColumn($table, 'created_at')
        ) {
            return collect();
        }

        $columns = array_values(array_filter(
            $preferredColumns,
            fn ($column) => Schema::hasColumn($table, $column)
        ));

        if (Schema::hasColumn($table, 'id') && !in_array('id', $columns)) {
            $columns[] = 'id';
        }

        if (!in_array('created_at', $columns)) {
            $columns[] = 'created_at';
        }

        return DB::table($table)
            ->select($columns)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }
}