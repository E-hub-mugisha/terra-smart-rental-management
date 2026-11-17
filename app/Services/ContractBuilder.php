<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\LeaseContract;
use App\Models\LeaseTemplate;
use App\Models\Tenant;
use App\Models\User;

/** Builds lease contract text from a template and the lease record (brief §8). */
class ContractBuilder
{
    public function variables(Lease $lease, array $extra = []): array
    {
        $lease->loadMissing(['tenant', 'unit.building.property.owner', 'unit.building.property.activeManagers.user']);
        $property = $lease->unit?->building?->property;
        $cur = $lease->currency ?: 'RWF';
        $money = fn ($v) => $cur . ' ' . number_format((float) $v, $cur === 'RWF' ? 0 : 2);
        $tenant = $lease->tenant;

        return [
            'landlord_name' => $property?->owner?->company_name ?: ($property?->owner?->name ?? '________'),
            'manager_name' => $property?->activeManagers->first()?->user?->name ?? '________',
            'tenant_name' => $tenant?->full_name ?? '________',
            'tenant_id_type' => $tenant?->id_type ? (Tenant::ID_TYPES[$tenant->id_type] ?? $tenant->id_type) : 'ID',
            'tenant_id_number' => $tenant?->id_number ?: '________',
            'tenant_phone' => $tenant?->phone ?? '',
            'occupants' => (string) ($tenant?->occupants ?: 1),
            'property_name' => $property?->name ?? '',
            'property_address' => collect([$property?->address, $property?->cell, $property?->sector, $property?->district])->filter()->implode(', ') ?: 'Rwanda',
            'unit_number' => (string) $lease->unit?->unit_number,
            'lease_number' => $lease->lease_number,
            'start_date' => $lease->start_date?->format('d F Y') ?? '',
            'end_date' => $lease->end_date?->format('d F Y') ?? '',
            'monthly_rent' => $money($lease->monthly_rent),
            'deposit_amount' => $money($lease->deposit_amount),
            'rent_due_day' => (string) ($lease->rent_due_day ?: 1),
            'payment_frequency' => ucfirst((string) ($lease->payment_frequency ?: 'monthly')),
            'notice_period_days' => (string) ($extra['notice_period_days'] ?? 30),
            'special_conditions' => trim((string) ($extra['special_conditions'] ?? '')) ?: 'None.',
            'today' => now()->format('d F Y'),
        ];
    }

    public function render(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', fn ($m) => $vars[$m[1]] ?? $m[0], $text);
    }

    public function generate(Lease $lease, LeaseTemplate $template, User $by, array $extra = []): LeaseContract
    {
        $vars = $this->variables($lease, $extra);
        $clauses = collect($template->clauses)->map(fn ($c) => [
            'title' => $this->render((string) ($c['title'] ?? ''), $vars),
            'body' => $this->render((string) ($c['body'] ?? ''), $vars),
        ])->values()->all();

        return LeaseContract::updateOrCreate(
            ['lease_id' => $lease->id],
            [
                'lease_template_id' => $template->id,
                'clauses' => $clauses,
                'variables' => $vars,
                'status' => 'draft',
                'generated_by' => $by->id,
                'finalized_at' => null,
            ]
        );
    }
}
