@extends('layouts.app')

@section('title', 'My jobs')
@section('page_heading', 'My jobs')
@section('page_subtitle', $provider->company_name . ' · maintenance requests assigned to you')

@php $prio = ['emergency' => 'text-bg-danger', 'high' => 'text-bg-warning', 'normal' => 'text-bg-light border', 'low' => 'text-bg-light border']; @endphp

@section('content')
<div class="row g-3 mb-4">
    @foreach([['Open jobs', $stats['open'], 'bi-tools'], ['Completed', $stats['done'], 'bi-check2-circle'], ['Average rating', $stats['rating'] ? number_format($stats['rating'], 1) . ' / 5' : '—', 'bi-star']] as [$l, $v, $i])
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius:14px;">
                <i class="bi {{ $i }} fs-4" style="color:var(--terra-orange);"></i>
                <div class="fs-5 fw-bold">{{ $v }}</div><div class="small text-secondary">{{ $l }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex gap-2 mb-3">
    @foreach(['open' => 'Open', 'done' => 'Completed', 'all' => 'All'] as $k => $l)
        <a href="{{ route('service-provider.maintenance.index', ['filter' => $k]) }}" class="btn btn-sm rounded-pill {{ $filter === $k ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $l }}</a>
    @endforeach
</div>

<div class="d-grid gap-2">
    @forelse($jobs as $job)
        <a href="{{ route('service-provider.maintenance.show', $job) }}" class="card border-0 shadow-sm text-decoration-none text-reset" style="border-radius:14px;">
            <div class="card-body d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <div class="fw-bold" style="color:var(--terra-navy);">{{ $job->title }}</div>
                    <div class="small text-secondary">{{ $job->request_number }} · Unit {{ $job->unit?->unit_number }}, {{ $job->unit?->building?->property?->name }}
                        @if($job->scheduled_at) · <i class="bi bi-calendar-event"></i> {{ $job->scheduled_at->format('d M, H:i') }}@endif</div>
                </div>
                <div class="d-flex gap-1 align-items-start">
                    <span class="badge rounded-pill {{ $prio[$job->priority] ?? 'text-bg-light' }}">{{ ucfirst($job->priority) }}</span>
                    <span class="badge rounded-pill text-bg-secondary">{{ ucwords(str_replace('_', ' ', $job->status)) }}</span>
                </div>
            </div>
        </a>
    @empty
        <div class="text-center text-secondary py-5"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>No jobs here.</div>
    @endforelse
</div>
<div class="mt-3">{{ $jobs->links() }}</div>
@endsection
