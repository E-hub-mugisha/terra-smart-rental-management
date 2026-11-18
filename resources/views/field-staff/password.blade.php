@extends('layouts.app')

@section('title', 'Change password')
@section('page_heading', 'Change password')
@section('page_subtitle', 'Replace the temporary password you received by email.')

@include('partials.sx-styles')

@section('content')
<div class="row"><div class="col-md-7 col-lg-5">
    <form method="POST" action="{{ route('field-staff.password.update') }}" class="sx-card">
        @csrf @method('PUT')
        <div class="sx-card-body">
            <div class="mb-3">
                <label class="form-label" for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="form-control @error('current_password') is-invalid @enderror">
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">New password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirmation">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="form-control">
            </div>
            <button class="sx-btn sx-btn-primary"><i class="bi bi-key"></i> Change password</button>
        </div>
    </form>
</div></div>
@endsection
