@extends('layouts.app')

@section('title', $title)
@section('page_heading', $title)
@section('page_subtitle', 'Read-only finance view.')

@php $m = fn ($v, $c = 'RWF') => ($c ?: 'RWF') . ' ' . number_format((float) $v); @endphp

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('finance.dashboard') }}" class="btn btn-sm btn-light rounded-pill">← Finance overview</a>
    @if($kind === 'payments')
        @foreach(['' => 'All', 'confirmed' => 'Confirmed', 'pending' => 'Pending'] as $k => $l)
            <a href="{{ route('finance.payments.index', array_filter(['status' => $k])) }}" class="btn btn-sm rounded-pill {{ request('status', '') === $k ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $l }}</a>
        @endforeach
    @elseif($kind === 'invoices')
        @foreach(['' => 'All', 'outstanding' => 'Outstanding', 'overdue' => 'Overdue', 'paid' => 'Paid'] as $k => $l)
            <a href="{{ route('finance.rent-invoices.index', array_filter(['status' => $k])) }}" class="btn btn-sm rounded-pill {{ request('status', '') === $k ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $l }}</a>
        @endforeach
    @endif
</div>

<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light small">
                @if($kind === 'payments')
                    <tr><th>Date</th><th>Payment</th><th>Tenant</th><th>Property / unit</th><th>Method</th><th>Status</th><th class="text-end">Amount</th><th></th></tr>
                @elseif($kind === 'invoices')
                    <tr><th>Issued</th><th>Invoice</th><th>Tenant</th><th>Property / unit</th><th>Due</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Balance</th><th></th></tr>
                @else
                    <tr><th>Date</th><th>Expense</th><th>Property</th><th>Category</th><th>Vendor</th><th class="text-end">Amount</th></tr>
                @endif
            </thead>
            <tbody>
                @forelse($rows as $r)
                    @if($kind === 'payments')
                        <tr>
                            <td>{{ $r->payment_date?->format('d M Y') }}</td><td>{{ $r->payment_number }}</td><td>{{ $r->tenant?->full_name }}</td>
                            <td>{{ $r->unit?->building?->property?->name }} · {{ $r->unit?->unit_number }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', (string) $r->payment_method)) }}</td>
                            <td><span class="badge rounded-pill {{ $r->status === 'confirmed' ? 'text-bg-success' : 'text-bg-warning' }}">{{ ucfirst($r->status) }}</span></td>
                            <td class="text-end">{{ $m($r->amount, $r->currency) }}</td>
                            <td>@if($r->status === 'confirmed')<a href="{{ route('receipts.show', $r) }}" target="_blank" class="small">Receipt</a>@endif</td>
                        </tr>
                    @elseif($kind === 'invoices')
                        <tr>
                            <td>{{ $r->issue_date?->format('d M Y') }}</td><td>{{ $r->invoice_number }}</td><td>{{ $r->tenant?->full_name }}</td>
                            <td>{{ $r->unit?->building?->property?->name }} · {{ $r->unit?->unit_number }}</td>
                            <td class="{{ $r->balance_due > 0 && $r->due_date?->isPast() ? 'text-danger fw-semibold' : '' }}">{{ $r->due_date?->format('d M Y') }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', (string) $r->status)) }}</td>
                            <td class="text-end">{{ $m($r->total_amount, $r->currency) }}</td><td class="text-end">{{ $m($r->balance_due, $r->currency) }}</td>
                            <td><a href="{{ route('ledger.show', $r->lease_id) }}" class="small">Ledger</a></td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $r->expense_date?->format('d M Y') }}</td><td>{{ $r->title }}</td><td>{{ $r->property?->name }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', (string) $r->category)) }}</td><td>{{ $r->vendor_name }}</td><td class="text-end">{{ $m($r->amount, $r->currency) }}</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="9" class="text-center text-secondary py-5">Nothing to show.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="p-3 border-top">{{ $rows->links() }}</div>@endif
</div>
@endsection
