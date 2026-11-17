@extends('layouts.app')

@section('title', 'Reports')
@section('page_heading', 'Reports')
@section('page_subtitle', 'Property, financial, lease and maintenance reports. Each one exports to Excel (CSV) or PDF.')

@php $icons = ['Property' => 'bi-buildings', 'Financial' => 'bi-cash-stack', 'Lease' => 'bi-file-earmark-text', 'Maintenance' => 'bi-tools']; @endphp

@section('content')
<div class="row g-4">
    @foreach($groups as $group => $reports)
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3" style="color:var(--terra-navy);"><i class="bi {{ $icons[$group] }} me-1" style="color:var(--terra-orange);"></i> {{ $group }} reports</h2>
                    <div class="d-grid gap-1">
                        @foreach($reports as $key => $label)
                            <a href="{{ route('reports.show', $key) }}" class="d-flex justify-content-between align-items-center py-2 px-2 rounded-3 text-decoration-none text-reset" style="background:#f7f8fb;">
                                <span>{{ $label }}</span><i class="bi bi-chevron-right small text-secondary"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
