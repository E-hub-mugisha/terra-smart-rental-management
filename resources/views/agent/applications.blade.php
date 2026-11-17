@extends('layouts.app')

@section('title', 'Applications')
@section('page_heading', 'Applications')
@section('page_subtitle', 'Rental applications for the properties you are assigned to. The property manager reviews and decides.')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach(['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l)
        <a href="{{ route('agent.applications.index', array_filter(['status' => $k])) }}" class="btn btn-sm rounded-pill {{ request('status', '') === $k ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $l }}</a>
    @endforeach
</div>
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    @include('agent._applications-table', ['applications' => $applications])
    @if($applications->hasPages())<div class="p-3 border-top">{{ $applications->links() }}</div>@endif
</div>
@endsection
