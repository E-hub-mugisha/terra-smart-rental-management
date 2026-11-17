@extends('layouts.app')

@section('title', 'Commissions')
@section('page_heading', 'My commissions')
@section('page_subtitle', 'Commissions recorded by property managers for tenants you helped place.')

@section('content')
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light small"><tr><th>Date</th><th>Property</th><th>Description</th><th>Status</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @forelse($commissions as $c)
                    <tr>
                        <td class="small">{{ $c->created_at->format('d M Y') }}</td>
                        <td>{{ $c->property?->name }}</td>
                        <td class="small">{{ $c->description }}{{ $c->lease?->tenant ? ' · ' . $c->lease->tenant->full_name : '' }}</td>
                        <td><span class="badge rounded-pill {{ ['paid' => 'text-bg-success', 'approved' => 'text-bg-info', 'cancelled' => 'text-bg-secondary'][$c->status] ?? 'text-bg-warning' }}">{{ \App\Models\AgentCommission::STATUSES[$c->status] ?? $c->status }}</span>
                            @if($c->paid_at)<div class="small text-secondary">{{ $c->paid_at->format('d M Y') }} · {{ $c->payment_reference }}</div>@endif</td>
                        <td class="text-end fw-semibold">{{ $c->currency }} {{ number_format((float) $c->amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-5">No commissions recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($commissions->hasPages())<div class="p-3 border-top">{{ $commissions->links() }}</div>@endif
</div>
@endsection
