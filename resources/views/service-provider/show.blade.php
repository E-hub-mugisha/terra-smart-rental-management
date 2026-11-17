@extends('layouts.app')

@section('title', $job->title)
@section('page_heading', $job->title)
@section('page_subtitle', $job->request_number . ' · Unit ' . $job->unit?->unit_number . ', ' . $job->unit?->building?->property?->name)

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge rounded-pill text-bg-secondary">{{ ucwords(str_replace('_', ' ', $job->status)) }}</span>
                    <span class="badge rounded-pill text-bg-light border">{{ ucfirst($job->priority) }} priority</span>
                    <span class="badge rounded-pill text-bg-light border">{{ ucfirst(str_replace('_', ' ', (string) $job->category)) }}</span>
                </div>
                <p style="white-space:pre-line;">{{ $job->description }}</p>
                <dl class="row small mb-0">
                    <dt class="col-sm-4 text-secondary fw-normal">Location</dt><dd class="col-sm-8">Unit {{ $job->unit?->unit_number }}, {{ $job->unit?->building?->name }}, {{ $job->unit?->building?->property?->name }}
                        @if($job->unit?->building?->property?->latitude) · <a href="https://www.google.com/maps?q={{ $job->unit->building->property->latitude }},{{ $job->unit->building->property->longitude }}" target="_blank" rel="noopener">Map</a>@endif</dd>
                    <dt class="col-sm-4 text-secondary fw-normal">Tenant</dt><dd class="col-sm-8">{{ $job->tenant?->full_name }} · <a href="tel:{{ $job->tenant?->phone }}">{{ $job->tenant?->phone }}</a></dd>
                    @if($job->preferred_visit_at)<dt class="col-sm-4 text-secondary fw-normal">Preferred visit</dt><dd class="col-sm-8">{{ $job->preferred_visit_at->format('d M Y, H:i') }}</dd>@endif
                    @if($job->scheduled_at)<dt class="col-sm-4 text-secondary fw-normal">Scheduled</dt><dd class="col-sm-8">{{ $job->scheduled_at->format('d M Y, H:i') }}</dd>@endif
                    @if($job->tenant_feedback)<dt class="col-sm-4 text-secondary fw-normal">Tenant feedback</dt><dd class="col-sm-8 text-danger">{{ $job->tenant_feedback }}</dd>@endif
                </dl>
                @if($job->attachment_path)
                    <a href="{{ asset('storage/' . $job->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill mt-3"><i class="bi bi-paperclip"></i> Tenant's photo</a>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body">
                <h2 class="h6 fw-bold mb-3">History</h2>
                @foreach($job->activities->sortByDesc('id') as $a)
                    <div class="small border-start border-3 ps-2 mb-2">
                        <strong>{{ $a->user?->name ?? 'System' }}</strong> · {{ $a->created_at->diffForHumans() }}<br>{{ $a->description }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if(in_array($job->status, ['closed', 'rejected'], true))
            <div class="alert alert-success">This job is closed{{ $job->tenant_rating ? ' · tenant rated it ' . $job->tenant_rating . '/5' : '' }}.</div>
        @else
            <div class="card border-0 shadow-sm" style="border-radius:14px;">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3" style="color:var(--terra-navy);">Update the job</h2>
                    @if($errors->any())<div class="alert alert-danger small py-2">{{ $errors->first() }}</div>@endif
                    <form method="POST" action="{{ route('service-provider.maintenance.update', $job) }}" class="d-grid gap-2">
                        @csrf @method('PATCH')
                        <select name="status" class="form-select" id="jobStatus" aria-label="Status">
                            @foreach($statuses as $k => $l)<option value="{{ $k }}" @selected(old('status', $job->status) === $k)>{{ $l }}</option>@endforeach
                        </select>
                        <label class="small text-secondary mb-0" for="sched">Visit date &amp; time (for “Scheduled”)</label>
                        <input type="datetime-local" name="scheduled_at" id="sched" class="form-control" value="{{ old('scheduled_at', $job->scheduled_at?->format('Y-m-d\TH:i')) }}">
                        <textarea name="note" rows="3" class="form-control" maxlength="3000" placeholder="What did you do / find? (required when resolved or on hold)">{{ old('note') }}</textarea>
                        <div class="input-group">
                            <span class="input-group-text">Cost RWF</span>
                            <input type="number" name="actual_cost" min="0" class="form-control" value="{{ old('actual_cost', $job->actual_cost) }}">
                        </div>
                        <button class="btn rounded-pill text-white" style="background:var(--terra-orange);">Save update</button>
                        <p class="small text-secondary mb-0">The tenant and property manager are notified. After “Resolved”, the tenant confirms the fix.</p>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
