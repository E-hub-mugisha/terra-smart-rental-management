@extends('layouts.app')

@section('title', 'Lease templates')
@section('page_heading', 'Lease templates')
@section('page_subtitle', 'Standard wording with placeholders. Generate a contract for any lease from its lease page.')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('lease-templates.create') }}" class="btn rounded-pill text-white px-4" style="background:var(--terra-orange);"><i class="bi bi-plus-lg me-1"></i> New template</a>
</div>
<div class="row g-3">
    @forelse($templates as $t)
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <h2 class="h6 fw-bold mb-1" style="color:var(--terra-navy);">{{ $t->name }}</h2>
                        @if($t->is_default)<span class="badge rounded-pill text-bg-success">Default</span>@endif
                    </div>
                    <div class="small text-secondary mb-2">{{ count($t->clauses) }} clauses · {{ $t->property?->name ?? 'All properties' }}</div>
                    <p class="small mb-3">{{ \Illuminate\Support\Str::limit($t->description, 140) }}</p>
                    <div class="mt-auto d-flex gap-2">
                        @if(auth()->user()->isAdmin() || $t->property_id)
                            <a href="{{ route('lease-templates.edit', $t) }}" class="btn btn-sm btn-outline-secondary rounded-pill">Edit</a>
                            <form method="POST" action="{{ route('lease-templates.destroy', $t) }}" onsubmit="return confirm('Delete this template? Existing contracts keep their text.')">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger">Delete</button></form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center text-secondary py-5">No templates yet.</div>
    @endforelse
</div>
@endsection
