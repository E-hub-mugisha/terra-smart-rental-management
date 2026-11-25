@extends('layouts.app')

@php
    use App\Models\Employee;
    $editing = $employee->exists;
    $val = fn ($field, $default = null) => old($field, $employee->{$field} instanceof \Carbon\CarbonInterface ? $employee->{$field}->format('Y-m-d') : ($employee->{$field} ?? $default));
@endphp

@section('title', $editing ? 'Edit employee' : 'Add employee')
@section('page_heading', $editing ? 'Edit ' . $employee->full_name : 'Add employee')
@section('page_subtitle', 'Staff details, pay and leave entitlement.')

@section('content')
@include('hr._nav', ['showEmployerFilter' => false])
@include('hr._errors')

<a href="{{ $editing ? route('hr.employees.show', $employee) : route('hr.employees.index') }}" class="sx-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3"><i class="bi bi-arrow-left"></i> Back</a>

<form method="POST" action="{{ $editing ? route('hr.employees.update', $employee) : route('hr.employees.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="sx-card mb-3">
                <div class="sx-card-head"><h2>Person</h2></div>
                <div class="sx-card-body row g-3">
                    @if($accounts->isNotEmpty())
                        <div class="col-12">
                            <label for="user_id" class="form-label">Platform account <span class="sx-muted">(optional)</span></label>
                            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror" data-account-picker>
                                <option value="">Not a platform user (e.g. caretaker, guard, cleaner)</option>
                                @foreach(Employee::LINKABLE_ROLES as $role => $roleLabel)
                                    @php $group = $accounts->filter(fn ($a) => $a['user']->role === $role); @endphp
                                    @if($group->isNotEmpty())
                                        <optgroup label="{{ \Illuminate\Support\Str::plural($roleLabel) }}">
                                            @foreach($group as $a)
                                                <option value="{{ $a['user']->id }}" @selected((string) $val('user_id') === (string) $a['user']->id)
                                                        data-name="{{ $a['user']->name }}" data-email="{{ $a['user']->email }}" data-phone="{{ $a['user']->contactPhone() }}"
                                                        data-job="{{ $roleLabel }}" data-employer="{{ $role === 'field_staff' ? '' : ($a['employer'] ?? '') }}">
                                                    {{ $a['user']->name }} · {{ $a['user']->email }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text">Link a property manager, accountant, agent or field staff member to put them on payroll. Their name, email and phone are filled in from their account.</div>
                        </div>
                    @endif
                    <div class="col-md-6">
                        <label for="full_name" class="form-label">Full name</label>
                        <input id="full_name" name="full_name" required maxlength="150" class="form-control @error('full_name') is-invalid @enderror" value="{{ $val('full_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="national_id" class="form-label">National ID / passport <span class="sx-muted">(optional)</span></label>
                        <input id="national_id" name="national_id" maxlength="40" class="form-control" value="{{ $val('national_id') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone <span class="sx-muted">(optional)</span></label>
                        <input id="phone" type="tel" name="phone" data-intl-phone class="form-control @error('phone') is-invalid @enderror" value="{{ $val('phone') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="sx-muted">(optional)</span></label>
                        <input id="email" type="email" name="email" maxlength="150" class="form-control @error('email') is-invalid @enderror" value="{{ $val('email') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Gender <span class="sx-muted">(optional)</span></label>
                        <select id="gender" name="gender" class="form-select">
                            <option value="">Not specified</option>
                            @foreach(['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $k => $l)<option value="{{ $k }}" @selected($val('gender') === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="date_of_birth" class="form-label">Date of birth <span class="sx-muted">(optional)</span></label>
                        <input id="date_of_birth" type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ $val('date_of_birth') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="emergency_contact_name" class="form-label">Emergency contact <span class="sx-muted">(optional)</span></label>
                        <input id="emergency_contact_name" name="emergency_contact_name" maxlength="150" class="form-control" value="{{ $val('emergency_contact_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="emergency_contact_phone" class="form-label">Emergency contact phone</label>
                        <input id="emergency_contact_phone" type="tel" name="emergency_contact_phone" data-intl-phone class="form-control @error('emergency_contact_phone') is-invalid @enderror" value="{{ $val('emergency_contact_phone') }}">
                    </div>
                </div>
            </div>

            <div class="sx-card mb-3">
                <div class="sx-card-head"><h2>Job</h2></div>
                <div class="sx-card-body row g-3">
                    <div class="col-md-6">
                        <label for="property_id" class="form-label">Employer</label>
                        <select id="property_id" name="property_id" class="form-select @error('property_id') is-invalid @enderror" @unless(array_key_exists('', $employers)) required @endunless>
                            @php
                                // With a single employer there is nothing to choose: preselect it.
                                $empSelected = (string) ($val('property_id') ?? (count($employers) === 1 ? array_key_first($employers) : ''));
                            @endphp
                            @unless(array_key_exists('', $employers) || count($employers) === 1)<option value="" disabled @selected($empSelected === '')>Choose a property</option>@endunless
                            @foreach($employers as $value => $label)
                                <option value="{{ $value }}" @selected($empSelected === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">The property this person works at. Payroll is run per employer.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="job_title" class="form-label">Job title</label>
                        <input id="job_title" name="job_title" required maxlength="100" list="jobTitles" class="form-control @error('job_title') is-invalid @enderror" value="{{ $val('job_title') }}">
                        <datalist id="jobTitles">@foreach(Employee::JOB_TITLES as $t)<option value="{{ $t }}">@endforeach</datalist>
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department <span class="sx-muted">(optional)</span></label>
                        <input id="department" name="department" maxlength="100" class="form-control" value="{{ $val('department') }}">
                    </div>
                    <div class="col-md-6">
                        <label for="employment_type" class="form-label">Contract type</label>
                        <select id="employment_type" name="employment_type" class="form-select">
                            @foreach(Employee::EMPLOYMENT_TYPES as $k => $l)<option value="{{ $k }}" @selected($val('employment_type') === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="hire_date" class="form-label">Start date</label>
                        <input id="hire_date" type="date" name="hire_date" required class="form-control @error('hire_date') is-invalid @enderror" value="{{ $val('hire_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End date <span class="sx-muted">(optional)</span></label>
                        <input id="end_date" type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ $val('end_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            @foreach(Employee::STATUSES as $k => $l)<option value="{{ $k }}" @selected($val('status') === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="sx-card mb-3">
                <div class="sx-card-head"><h2>Pay</h2></div>
                <div class="sx-card-body row g-3">
                    <div class="col-8">
                        <label for="base_salary" class="form-label">Monthly base salary</label>
                        <input id="base_salary" type="number" step="0.01" min="0" name="base_salary" required class="form-control @error('base_salary') is-invalid @enderror" value="{{ $val('base_salary', 0) }}">
                    </div>
                    <div class="col-4">
                        <label for="currency" class="form-label">Currency</label>
                        <select id="currency" name="currency" class="form-select">
                            @foreach($currencies as $c)<option @selected($val('currency') === $c)>{{ $c }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="payment_method" class="form-label">Paid by</label>
                        <select id="payment_method" name="payment_method" class="form-select">
                            <option value="">Not set</option>
                            @foreach(Employee::PAYMENT_METHODS as $k => $l)<option value="{{ $k }}" @selected($val('payment_method') === $k)>{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="bank_name" class="form-label">Bank or mobile money provider <span class="sx-muted">(optional)</span></label>
                        <input id="bank_name" name="bank_name" maxlength="100" class="form-control" value="{{ $val('bank_name') }}">
                    </div>
                    <div class="col-12">
                        <label for="account_number" class="form-label">Account / wallet number <span class="sx-muted">(optional)</span></label>
                        <input id="account_number" name="account_number" maxlength="60" class="form-control" value="{{ $val('account_number') }}">
                    </div>
                </div>
            </div>

            <div class="sx-card mb-3">
                <div class="sx-card-head"><h2>Leave</h2></div>
                <div class="sx-card-body">
                    <label for="annual_leave_days" class="form-label">Annual leave days per year</label>
                    <input id="annual_leave_days" type="number" min="0" max="60" name="annual_leave_days" required class="form-control @error('annual_leave_days') is-invalid @enderror" value="{{ $val('annual_leave_days', 18) }}">
                    <div class="form-text">Working days. Rwanda's labour law gives at least 18 for a full year.</div>
                </div>
            </div>

            <div class="sx-card mb-3">
                <div class="sx-card-head"><h2>Notes</h2></div>
                <div class="sx-card-body">
                    <label for="notes" class="visually-hidden">Notes</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="2000" class="form-control">{{ $val('notes') }}</textarea>
                </div>
            </div>

            <button class="sx-btn sx-btn-primary w-100 justify-content-center"><i class="bi bi-check2"></i> {{ $editing ? 'Save changes' : 'Add employee' }}</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    // Picking a platform account fills in the person's details (only empty fields, or ones still showing the previous account's values).
    (function () {
        var picker = document.querySelector('[data-account-picker]');
        if (!picker) { return; }
        var fields = { name: 'full_name', email: 'email', phone: 'phone', job: 'job_title' };
        var previous = picker.selectedOptions[0] ? Object.assign({}, picker.selectedOptions[0].dataset) : {};
        picker.addEventListener('change', function () {
            var data = picker.selectedOptions[0] ? picker.selectedOptions[0].dataset : {};
            Object.keys(fields).forEach(function (key) {
                var input = document.getElementById(fields[key]);
                if (input && data[key] !== undefined && (input.value === '' || input.value === (previous[key] || ''))) {
                    input.value = data[key];
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
            var employer = document.getElementById('property_id');
            if (employer && data.employer !== undefined && Array.prototype.some.call(employer.options, function (o) { return o.value === data.employer; })) {
                employer.value = data.employer;
            }
            previous = Object.assign({}, data);
        });
    })();
</script>
@endpush
@endsection
