@extends('layouts.app')

@section('title', 'Rent ledger · ' . $lease->lease_number)
@section('page_heading', 'Rent ledger')
@section('page_subtitle', ($lease->tenant?->full_name ?? 'Tenant') . ' · Unit ' . $lease->unit?->unit_number . ' · ' . $lease->unit?->building?->property?->name)

@php $cur = $lease->currency ?: 'RWF'; $fmt = fn ($v) => number_format((float) $v, $cur === 'RWF' ? 0 : 2); @endphp

@section('content')
<div class="row g-3 mb-4">
    @foreach([['Total billed', $totals['debits'], 'bi-receipt', '#19265d'], ['Total paid', $totals['credits'], 'bi-cash-coin', '#147847'],
              [$totals['balance'] > 0 ? 'Balance owed' : ($totals['balance'] < 0 ? 'Credit' : 'Balance'), abs($totals['balance']), 'bi-wallet2', $totals['balance'] > 0 ? '#b42318' : '#147847']] as [$label, $value, $icon, $color])
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="d-grid rounded-3" style="width:46px;height:46px;place-items:center;background:#f2f4f9;color:{{ $color }};"><i class="bi {{ $icon }} fs-4"></i></span>
                    <div><div class="small text-secondary">{{ $label }}</div><div class="fs-4 fw-bold" style="color:{{ $color }};">{{ $cur }} {{ $fmt($value) }}</div></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($payable->isNotEmpty())
    <div class="alert d-flex flex-wrap align-items-center justify-content-between gap-2" style="background:#fff3e8;border:1px solid #f4d3bf;">
        <span><i class="bi bi-info-circle me-1"></i> You have {{ $payable->count() }} unpaid {{ \Illuminate\Support\Str::plural('invoice', $payable->count()) }}.</span>
        <a href="{{ route('payments.online.create', $payable->first()) }}" class="btn btn-sm rounded-pill text-white px-3" style="background:var(--terra-orange);">
            Pay {{ $payable->first()->invoice_number }} ({{ $cur }} {{ $fmt($payable->first()->balance_due) }})
        </a>
    </div>
@endif

<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 p-3">
        <strong style="color:var(--terra-navy);">Lease {{ $lease->lease_number }} · {{ $entries->count() }} entries</strong>
        <div class="d-flex gap-2">
            <a href="{{ route('ledger.show', [$lease, 'format' => 'csv']) }}" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-filetype-csv me-1"></i> Excel / CSV</a>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print / PDF</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr><th>Date</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr>
            </thead>
            <tbody>
                @forelse($entries as $e)
                    <tr>
                        <td class="text-nowrap">{{ $e['date']->format('d M Y') }}</td>
                        <td>
                            {{ $e['description'] }}
                            @if($e['kind'] === 'payment')
                                <a href="{{ route('receipts.show', $e['id']) }}" class="small ms-1" target="_blank">Receipt</a>
                            @endif
                        </td>
                        <td class="text-end">{{ $e['debit'] ? $fmt($e['debit']) : '–' }}</td>
                        <td class="text-end text-success">{{ $e['credit'] ? $fmt($e['credit']) : '–' }}</td>
                        <td class="text-end fw-semibold {{ $e['balance'] > 0 ? 'text-danger' : '' }}">{{ $fmt($e['balance']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-5">No invoices or payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
