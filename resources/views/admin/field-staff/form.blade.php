@extends('layouts.app')

@php $editing = $member->exists; @endphp
@section('title', $editing ? 'Edit field staff' : 'Add field staff')
@section('page_heading', $editing ? 'Edit ' . $user->name : 'Add field staff')
@section('page_subtitle', 'Field staff can only register property owners and managers. A login is created and emailed to them.')

@include('partials.sx-styles')

@section('content')
@php $provinces = config('rwanda.provinces', []); @endphp
<a href="{{ $editing ? route('admin.field-staff.show', $member) : route('admin.field-staff.index') }}" class="sx-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3"><i class="bi bi-arrow-left"></i> Back</a>

<div class="row">
    <div class="col-lg-8 col-xl-7">
        <form method="POST" action="{{ $editing ? route('admin.field-staff.update', $member) : route('admin.field-staff.store') }}" class="sx-card">
            @csrf
            @if($editing) @method('PUT') @endif
            <div class="sx-card-head"><h2>Account & contact</h2></div>
            <div class="sx-card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="name">Full name <span class="text-danger">*</span></label>
                    <input id="name" name="name" required maxlength="255" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                    <input id="email" type="email" name="email" required maxlength="255" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phone">Phone <span class="text-danger">*</span></label>
                    <input id="phone" type="tel" name="phone" data-intl-phone required class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $member->phone) }}">
                    @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        @foreach(\App\Models\FieldStaff::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $member->status) === $k)>{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="region">Province / region</label>
                    <input id="region" name="region" list="provinceList" maxlength="100" class="form-control" value="{{ old('region', $member->region) }}">
                    <datalist id="provinceList">@foreach(array_keys($provinces) as $p)<option value="{{ $p }}">@endforeach</datalist>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="district">District</label>
                    <input id="district" name="district" list="districtList" maxlength="100" class="form-control" value="{{ old('district', $member->district) }}">
                    <datalist id="districtList">@foreach(collect($provinces)->flatten()->unique()->sort() as $d)<option value="{{ $d }}">@endforeach</datalist>
                </div>
                <div class="col-12">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="2000" class="form-control">{{ old('notes', $member->notes) }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.field-staff.index') }}" class="sx-btn sx-btn-outline">Cancel</a>
                    <button class="sx-btn sx-btn-primary"><i class="bi bi-check2-circle"></i> {{ $editing ? 'Save changes' : 'Create account' }}</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-4 col-xl-5 mt-3 mt-lg-0">
        <div class="sx-card">
            <div class="sx-card-head"><h2>What field staff can do</h2></div>
            <div class="sx-card-body small">
                <ul class="mb-0 ps-3" style="line-height:1.9">
                    <li>Register property owners and property managers (each gets their own login by email)</li>
                    <li>See the list of people they registered</li>
                    <li>Message Terra administrators</li>
                    <li><strong>Cannot</strong> open admin pages, properties, tenants, finance, reports, documents or settings</li>
                    <li>Owners they register start as <em>pending review</em> until an admin verifies them</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
