@extends('layouts.app')

@section('title', 'My registrations')
@section('page_heading', 'My registrations')
@section('page_subtitle', 'Owners and managers you have registered on Terra.')

@use('App\Support\Phone')
@include('partials.sx-styles')

@section('content')
<div class="sx-card">
    <div class="sx-card-head">
        <h2>{{ $registrations->count() }} {{ \Illuminate\Support\Str::plural('registration', $registrations->count()) }}</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('field-staff.owners.create') }}" class="sx-btn sx-btn-primary"><i class="bi bi-person-badge"></i> Owner</a>
            <a href="{{ route('field-staff.managers.create') }}" class="sx-btn sx-btn-outline"><i class="bi bi-person-gear"></i> Manager</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="sx-table">
            <thead><tr><th>Name</th><th>Type</th><th>Number</th><th>Email</th><th>Phone</th><th>District</th><th>Status</th><th>Registered</th></tr></thead>
            <tbody>
            @forelse($registrations as $r)
                <tr>
                    <td><strong>{{ $r['name'] }}</strong></td>
                    <td><span class="sx-pill {{ $r['type'] === 'Owner' ? 'info' : 'secondary' }}">{{ $r['type'] }}</span></td>
                    <td class="small">{{ $r['number'] }}</td>
                    <td class="small">{{ $r['email'] }}</td>
                    <td class="small">{{ $r['phone'] ? Phone::display($r['phone']) : '—' }}</td>
                    <td class="small">{{ $r['place'] ?: '—' }}</td>
                    <td class="small">{{ $r['status'] }}</td>
                    <td class="small">{{ $r['created_at']?->format('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="sx-empty"><i class="bi bi-person-plus"></i>No registrations yet.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
