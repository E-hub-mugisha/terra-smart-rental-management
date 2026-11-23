@extends('layouts.app')

@section('title', 'Subscription plans')
@section('page_heading', 'Subscription plans')
@section('page_subtitle', 'Packages offered to property owners and managers. Contracts can use a plan or a custom fee.')

@use('App\Support\Money')
@include('partials.sx-styles')

@section('content')
@php
    $cycles = ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annual' => 'Annual'];
    $currencies = ['RWF', 'USD', 'EUR', 'KES', 'UGX', 'TZS'];
@endphp
<div class="row g-3">
    <div class="col-lg-8">
        <div class="row g-3">
            @forelse($plans as $plan)
                <div class="col-md-6">
                    <div class="sx-card h-100">
                        <div class="sx-card-head">
                            <h2>{{ $plan->name }}</h2>
                            <span class="sx-pill {{ $plan->is_active ? 'success' : 'secondary' }}">{{ $plan->is_active ? 'Offered' : 'Retired' }}</span>
                        </div>
                        <div class="sx-card-body">
                            <div class="sx-value" style="font-size:1.4rem;font-weight:700;color:var(--terra-navy,#19265d)">{{ Money::format($plan->price, $plan->currency) }} <span class="sx-muted small fw-normal">/ {{ strtolower($cycles[$plan->billing_cycle] ?? $plan->billing_cycle) }}</span></div>
                            <div class="small sx-muted mb-2">Up to {{ $plan->max_properties ?? 'unlimited' }} properties · {{ $plan->max_units ?? 'unlimited' }} units · {{ $plan->active_contracts_count }} active {{ \Illuminate\Support\Str::plural('contract', $plan->active_contracts_count) }}</div>
                            @if($plan->description)<p class="small mb-2">{{ $plan->description }}</p>@endif
                            <details>
                                <summary class="small" style="cursor:pointer">Edit plan</summary>
                                <form method="POST" action="{{ route('admin.subscriptions.plans.update', $plan) }}" class="row g-2 mt-1">
                                    @csrf @method('PUT')
                                    @include('admin.subscriptions._plan-fields', ['plan' => $plan, 'prefix' => 'p' . $plan->id])
                                    <div class="col-12 d-grid"><button class="sx-btn sx-btn-primary justify-content-center">Save</button></div>
                                </form>
                                <form method="POST" action="{{ route('admin.subscriptions.plans.destroy', $plan) }}" class="d-grid mt-2"
                                      onsubmit="return confirm('Delete this plan? Plans used by contracts cannot be deleted; retire them instead.');">
                                    @csrf @method('DELETE')
                                    <button class="sx-btn sx-btn-outline text-danger justify-content-center"><i class="bi bi-trash3"></i> Delete plan</button>
                                </form>
                            </details>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="sx-card"><div class="sx-empty"><i class="bi bi-stack"></i>No plans yet. Create the first one.</div></div></div>
            @endforelse
        </div>
    </div>
    <div class="col-lg-4">
        <div class="sx-card">
            <div class="sx-card-head"><h2>New plan</h2></div>
            <form method="POST" action="{{ route('admin.subscriptions.plans.store') }}" class="sx-card-body row g-2">
                @csrf
                @include('admin.subscriptions._plan-fields', ['plan' => new \App\Models\SubscriptionPlan(['is_active' => true, 'currency' => 'RWF', 'billing_cycle' => 'monthly']), 'prefix' => 'new'])
                <div class="col-12 d-grid"><button class="sx-btn sx-btn-primary justify-content-center"><i class="bi bi-plus-lg"></i> Create plan</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
