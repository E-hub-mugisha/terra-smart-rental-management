@extends('layouts.app')

@section('title', 'Database updates')
@section('page_heading', 'Database updates')
@section('page_subtitle', 'Apply new database migrations after deploying an update, without a terminal.')

@include('partials.sx-styles')

@section('content')
@if($errors->any())
    <div class="alert alert-danger small" role="alert">{{ $errors->first() }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="sx-card">
            <div class="sx-card-head">
                <h2>Pending migrations</h2>
                <span class="sx-pill {{ $pending ? 'warning' : 'success' }}">{{ $pending ? count($pending) . ' pending' : 'Up to date' }}</span>
            </div>
            @if($pending)
                <ul class="list-unstyled m-0">
                    @foreach($pending as $name)
                        <li class="px-3 py-2 border-bottom small font-monospace text-break">{{ $name }}</li>
                    @endforeach
                </ul>
            @else
                <div class="sx-empty"><i class="bi bi-check2-circle"></i>The database is up to date. {{ $ranCount }} migrations have run.</div>
            @endif
        </div>

        @if($output)
            <div class="sx-card mt-3">
                <div class="sx-card-head"><h2>Output of the last run</h2></div>
                <pre class="m-0 p-3 small" style="white-space:pre-wrap;max-height:360px;overflow:auto;background:#f6f7fb">{{ $output }}</pre>
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="sx-card">
            <div class="sx-card-head"><h2>Run migrations</h2></div>
            <div class="sx-card-body">
                <p class="small mb-2">This changes the live database structure. Before running:</p>
                <ul class="small sx-muted ps-3">
                    <li>Take a database backup.</li>
                    <li>Make sure the new code has been fully uploaded.</li>
                    <li>Run it once; the run is recorded in the audit log.</li>
                </ul>
                @if($pending)
                    <form method="POST" action="{{ route('admin.system.migrations.run') }}"
                          onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerText = 'Running…';">
                        @csrf
                        <label for="confirmMigrate" class="form-label small">Type <strong>MIGRATE</strong> to confirm</label>
                        <input id="confirmMigrate" name="confirm" autocomplete="off" required pattern="MIGRATE" class="form-control mb-2 @error('confirm') is-invalid @enderror">
                        <button class="sx-btn sx-btn-primary w-100 justify-content-center"><i class="bi bi-database-up"></i> Run {{ count($pending) }} {{ \Illuminate\Support\Str::plural('migration', count($pending)) }}</button>
                    </form>
                @else
                    <button class="sx-btn sx-btn-outline w-100 justify-content-center" disabled>Nothing to run</button>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
