@extends('layouts.app')

@section('title', 'Agent dashboard')
@section('page_heading', 'Agent dashboard')
@section('page_subtitle', 'Market your assigned properties, follow applications and track commissions.')

@section('content')
<div class="row g-3 mb-4">
    @foreach([['Properties', $stats['properties'], 'bi-buildings'], ['Units available', $stats['available_units'], 'bi-door-open'], ['Open applications', $stats['open_applications'], 'bi-file-earmark-person'],
              ['Commission due', 'RWF ' . number_format($stats['commission_pending']), 'bi-hourglass-split'], ['Commission paid', 'RWF ' . number_format($stats['commission_paid']), 'bi-cash-coin']] as [$l, $v, $i])
        <div class="col-6 col-lg">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;"><div class="card-body">
                <div class="small text-secondary"><i class="bi {{ $i }} me-1" style="color:var(--terra-orange);"></i>{{ $l }}</div>
                <div class="fs-5 fw-bold" style="color:var(--terra-navy);">{{ $v }}</div>
            </div></div>
        </div>
    @endforeach
</div>

<h2 class="h6 fw-bold mb-3" style="color:var(--terra-navy);">Your properties</h2>
@include('agent._properties', ['properties' => $properties])

<div class="card border-0 shadow-sm mt-4" style="border-radius:14px;">
    <div class="card-header bg-white d-flex justify-content-between"><strong style="color:var(--terra-navy);">Recent applications</strong><a href="{{ route('agent.applications.index') }}" class="small">All</a></div>
    @include('agent._applications-table', ['applications' => $recentApplications])
</div>
@endsection
