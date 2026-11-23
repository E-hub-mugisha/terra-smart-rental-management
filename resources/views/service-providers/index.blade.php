@extends('layouts.app')

@section('title', 'Service providers')
@section('page_heading', 'Service provider network')
@section('page_subtitle', 'Plumbers, electricians, cleaners and other trusted providers you can assign maintenance jobs to.')

@php $cats = \App\Models\ServiceProvider::CATEGORIES; @endphp

@section('content')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-header bg-white p-3">
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="max-width:240px" placeholder="Search name or district" aria-label="Search">
                    <select name="category" class="form-select form-select-sm" style="max-width:220px" onchange="this.form.submit()" aria-label="Category">
                        <option value="">All services</option>
                        @foreach($cats as $k => [$label])<option value="{{ $k }}" @selected(request('category') === $k)>{{ $label }}</option>@endforeach
                    </select>
                </form>
            </div>
            @forelse($providers as $p)
                <div class="p-3 border-bottom">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <span class="fw-bold" style="color:var(--terra-navy);">{{ $p->company_name }}</span>
                            @if($p->isVerified())<span class="badge rounded-pill text-bg-success ms-1"><i class="bi bi-patch-check-fill"></i> Verified</span>
                            @else<span class="badge rounded-pill text-bg-light border ms-1">Not verified</span>@endif
                            @if($p->status !== 'active')<span class="badge rounded-pill text-bg-danger ms-1">Suspended</span>@endif
                            @if($p->user_id)<span class="badge rounded-pill text-bg-info ms-1">Has login</span>@endif
                            <div class="small text-secondary">
                                {{ $p->contact_name ? $p->contact_name . ' · ' : '' }}<a href="tel:{{ $p->phone }}">{{ $p->phone }}</a>{{ $p->email ? ' · ' . $p->email : '' }}{{ $p->district ? ' · ' . $p->district : '' }}
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @foreach($p->categories ?? [] as $c)<span class="badge rounded-pill text-bg-light border"><i class="bi {{ $cats[$c][1] ?? 'bi-tools' }}"></i> {{ $cats[$c][0] ?? $c }}</span>@endforeach
                            </div>
                        </div>
                        <div class="text-end small">
                            <div><strong>{{ $p->open_jobs_count }}</strong> open · <strong>{{ $p->done_jobs_count }}</strong> done</div>
                            <div class="text-warning">@if($p->rating_avg)<i class="bi bi-star-fill"></i> {{ number_format($p->rating_avg, 1) }}/5 @else <span class="text-secondary">No ratings yet</span> @endif</div>
                            <div class="d-flex gap-1 justify-content-end mt-1">
                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#edit{{ $p->id }}">Edit</button>
                                @if($isAdmin)
                                    <form method="POST" action="{{ route('service-providers.verify', $p) }}">@csrf
                                        <input type="hidden" name="action" value="{{ $p->isVerified() ? 'unverify' : 'verify' }}">
                                        <button class="btn btn-sm {{ $p->isVerified() ? 'btn-light' : 'btn-success' }}">{{ $p->isVerified() ? 'Unverify' : 'Verify' }}</button></form>
                                    <form method="POST" action="{{ route('service-providers.verify', $p) }}">@csrf
                                        <input type="hidden" name="action" value="{{ $p->status === 'active' ? 'suspend' : 'activate' }}">
                                        <button class="btn btn-sm btn-light text-{{ $p->status === 'active' ? 'danger' : 'success' }}">{{ $p->status === 'active' ? 'Suspend' : 'Reactivate' }}</button></form>
                                    <form method="POST" action="{{ route('service-providers.destroy', $p) }}"
                                          onsubmit="return confirm('Delete this service provider permanently? Their technician login is removed too. Their jobs stay on record, unassigned.');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Delete provider" aria-label="Delete {{ $p->company_name }}"><i class="bi bi-trash3"></i></button></form>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="collapse mt-3" id="edit{{ $p->id }}">
                        @include('service-providers._form', ['provider' => $p])
                    </div>
                </div>
            @empty
                <div class="text-center text-secondary py-5"><i class="bi bi-person-gear fs-1 d-block mb-2 opacity-50"></i>No service providers yet. Add your first one.</div>
            @endforelse
            @if($providers->hasPages())<div class="p-3">{{ $providers->links() }}</div>@endif
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3" style="color:var(--terra-navy);"><i class="bi bi-plus-circle me-1"></i> Add a provider</h2>
                @include('service-providers._form', ['provider' => null])
            </div>
        </div>
    </div>
</div>
@endsection
