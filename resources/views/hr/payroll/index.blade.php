@extends('layouts.app')

@section('title', 'Payroll')
@section('page_heading', 'Payroll')
@section('page_subtitle', 'One payroll per employer and month. Paid property payrolls are recorded as salaries expenses.')

@php use App\Support\Money; use App\Models\PayrollRun; @endphp

@section('content')
@include('hr._nav')
@include('hr._errors')

<div class="row g-3">
    <div class="col-xl-8">
        <div class="sx-card">
            <div class="table-responsive">
                <table class="sx-table">
                    <thead><tr><th>Month</th><th>Employer</th><th class="sx-num">Staff</th><th class="sx-num">Gross</th><th class="sx-num">Net pay</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td><a href="{{ route('hr.payroll.show', $run) }}" class="fw-semibold text-decoration-none">{{ $run->period_label }}</a><div class="sx-muted small">{{ $run->run_number }}</div></td>
                            <td class="small">{{ $run->employer_label }}</td>
                            <td class="sx-num">{{ $run->payslips_count }}</td>
                            <td class="sx-num">{{ Money::format($run->total_gross, $run->currency) }}</td>
                            <td class="sx-num fw-semibold">{{ Money::format($run->total_net, $run->currency) }}</td>
                            <td><span class="sx-pill {{ PayrollRun::STATUSES[$run->status][1] }}">{{ PayrollRun::STATUSES[$run->status][0] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="sx-empty"><i class="bi bi-cash-coin"></i>No payroll yet. Create one on the right.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($runs->hasPages())<div class="p-3">{{ $runs->links() }}</div>@endif
        </div>
    </div>

    <div class="col-xl-4">
        <div class="sx-card">
            <div class="sx-card-head"><h2>New payroll</h2></div>
            <form method="POST" action="{{ route('hr.payroll.store') }}" class="sx-card-body row g-2">
                @csrf
                <div class="col-12">
                    <label for="prEmployer" class="form-label small">Employer</label>
                    <select id="prEmployer" name="property_id" class="form-select form-select-sm" @unless(array_key_exists('', $employers)) required @endunless>
                        @php
                            // With a single employer there is nothing to choose: preselect it.
                            $prSelected = (string) old('property_id', ctype_digit((string) request('employer')) ? request('employer') : (count($employers) === 1 ? array_key_first($employers) : ''));
                        @endphp
                        @unless(array_key_exists('', $employers) || count($employers) === 1)<option value="" disabled @selected($prSelected === '')>Choose a property</option>@endunless
                        @foreach($employers as $value => $label)
                            <option value="{{ $value }}" @selected($prSelected === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-7">
                    <label for="prMonth" class="form-label small">Month</label>
                    <input id="prMonth" type="month" name="month" required value="{{ old('month', now()->format('Y-m')) }}" class="form-control form-control-sm">
                </div>
                <div class="col-5">
                    <label for="prCurrency" class="form-label small">Currency</label>
                    <select id="prCurrency" name="currency" class="form-select form-select-sm">
                        @foreach($currencies as $c)<option @selected(old('currency', 'RWF') === $c)>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 small sx-muted">Every employee of that employer paid in this currency is added with their base salary and the days marked absent that month. You add allowances and deductions before approving.</div>
                <div class="col-12 d-grid"><button class="sx-btn sx-btn-primary justify-content-center"><i class="bi bi-plus-lg"></i> Create draft payroll</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
