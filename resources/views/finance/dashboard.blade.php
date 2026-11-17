@extends('layouts.app')

@section('title', 'Finance')
@section('page_heading', 'Finance overview')
@section('page_subtitle', 'This month across the properties you can see. Read-only.')

@php $m = fn ($v) => 'RWF ' . number_format((float) $v); @endphp

@section('content')
<div class="row g-3 mb-4">
    @foreach([
        ['Billed this month', $m($kpis['billed']), 'bi-receipt', '#19265d'],
        ['Collected this month', $m($kpis['collected']), 'bi-cash-coin', '#147847'],
        ['Collection rate', $kpis['rate'] !== null ? $kpis['rate'] . '%' : '—', 'bi-percent', '#116a9c'],
        ['Outstanding rent', $m($kpis['outstanding']), 'bi-hourglass-split', '#8f5a08'],
        ['Overdue', $m($kpis['overdue']), 'bi-exclamation-triangle', '#b42318'],
        ['Expenses this month', $m($kpis['expenses']), 'bi-wallet2', '#5b3aa8'],
        ['Net income this month', $m($kpis['net']), 'bi-graph-up-arrow', $kpis['net'] >= 0 ? '#147847' : '#b42318'],
        ['Payments to verify', $kpis['pending_payments'], 'bi-shield-check', '#D05208'],
    ] as [$label, $value, $icon, $color])
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-body">
                    <div class="small text-secondary"><i class="bi {{ $icon }} me-1" style="color:{{ $color }};"></i>{{ $label }}</div>
                    <div class="fs-5 fw-bold mt-1" style="color:{{ $color }};">{{ $value }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('finance.payments.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">All payments</a>
    <a href="{{ route('finance.rent-invoices.index', ['status' => 'outstanding']) }}" class="btn btn-sm btn-outline-secondary rounded-pill">Outstanding invoices</a>
    <a href="{{ route('finance.expenses.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">Expenses</a>
    <a href="{{ route('reports.index') }}" class="btn btn-sm rounded-pill text-white" style="background:var(--terra-orange);"><i class="bi bi-bar-chart-line me-1"></i> Financial reports</a>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white fw-bold" style="color:var(--terra-navy);">Recent payments</div>
            <div class="list-group list-group-flush">
                @forelse($recentPayments as $p)
                    <a href="{{ route('receipts.show', $p) }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between small">
                        <span>{{ $p->tenant?->full_name }}<br><span class="text-secondary">{{ $p->unit?->building?->property?->name }} · Unit {{ $p->unit?->unit_number }} · {{ $p->payment_date?->format('d M') }}</span></span>
                        <strong class="text-success">{{ $m($p->amount) }}</strong>
                    </a>
                @empty
                    <div class="list-group-item text-secondary small">No payments yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white fw-bold" style="color:var(--terra-navy);">Overdue invoices</div>
            <div class="list-group list-group-flush">
                @forelse($overdueInvoices as $i)
                    <a href="{{ route('ledger.show', $i->lease_id) }}" class="list-group-item list-group-item-action d-flex justify-content-between small">
                        <span>{{ $i->tenant?->full_name }}<br><span class="text-secondary">{{ $i->invoice_number }} · due {{ $i->due_date?->format('d M') }} ({{ (int) $i->due_date?->diffInDays(now()) }} days)</span></span>
                        <strong class="text-danger">{{ $m($i->balance_due) }}</strong>
                    </a>
                @empty
                    <div class="list-group-item text-secondary small">Nothing overdue. 🎉</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
