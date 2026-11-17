@extends('layouts.app')

@section('title', $report['title'])
@section('page_heading', $report['title'])
@section('page_subtitle', $report['description'])

@php
    $isMoney = fn ($key) => preg_match('/(rent|billed|collected|expenses|income|outstanding|balance|cost)/', $key) && ! str_contains($key, 'rate');
    $cell = fn ($key, $value) => is_float($value) || (is_int($value) && $isMoney($key)) ? number_format((float) $value) : ($value ?? '—');
@endphp

@push('styles')
<style>@media print { .terra-sidebar, .terra-topbar, .no-print { display: none !important; } .terra-main, main { margin: 0 !important; padding: 0 !important; } }</style>
@endpush

@section('content')
<div class="card border-0 shadow-sm mb-3 no-print" style="border-radius:14px;">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small mb-1" for="rProp">Property</label>
                <select name="property" id="rProp" class="form-select form-select-sm">
                    <option value="">All my properties</option>
                    @foreach($properties as $p)<option value="{{ $p->id }}" @selected((int) request('property') === $p->id)>{{ $p->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-sm-3 col-lg-2">
                <label class="form-label small mb-1" for="rFrom">From</label>
                <input type="date" name="from" id="rFrom" class="form-control form-control-sm" value="{{ $from->toDateString() }}">
            </div>
            <div class="col-sm-3 col-lg-2">
                <label class="form-label small mb-1" for="rTo">To</label>
                <input type="date" name="to" id="rTo" class="form-control form-control-sm" value="{{ $to->toDateString() }}">
            </div>
            <div class="col-lg-2 d-grid"><button class="btn btn-sm btn-dark">Update</button></div>
            <div class="col-lg-3 d-flex gap-2 justify-content-lg-end">
                <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn btn-sm btn-outline-success rounded-pill"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
            </div>
        </form>
        <div class="small text-secondary mt-2"><a href="{{ route('reports.index') }}">← All reports</a> · Date range applies to time-based reports.</div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="d-none d-print-block p-3"><h1 class="h5">{{ $report['title'] }}</h1><div class="small">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }} · generated {{ now()->format('d M Y H:i') }}</div></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light small"><tr>
                @foreach($report['columns'] as $key => $label)<th class="{{ $isMoney($key) ? 'text-end' : '' }}">{{ $label }}</th>@endforeach
            </tr></thead>
            <tbody>
                @forelse($report['rows'] as $row)
                    <tr>@foreach($report['columns'] as $key => $label)<td class="{{ $isMoney($key) ? 'text-end' : '' }}">{{ $cell($key, $row[$key] ?? null) }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($report['columns']) }}" class="text-center text-secondary py-5">No data for this selection.</td></tr>
                @endforelse
            </tbody>
            @if($report['totals'] && count($report['rows']))
                <tfoot class="fw-bold table-light"><tr>
                    @foreach($report['columns'] as $key => $label)<td class="{{ $isMoney($key) ? 'text-end' : '' }}">{{ isset($report['totals'][$key]) ? $cell($key, $report['totals'][$key]) : '' }}</td>@endforeach
                </tr></tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
