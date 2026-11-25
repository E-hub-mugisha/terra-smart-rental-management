@extends('layouts.app')

@php
    use App\Support\Money; use App\Models\PayrollRun;
    $editable = $run->isEditable();
    $money = fn ($v) => Money::format($v, $run->currency);
    $amount = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
@endphp

@section('title', 'Payroll ' . $run->period_label)
@section('page_heading', 'Payroll · ' . $run->period_label)
@section('page_subtitle', $run->employer_label . ' · ' . $run->run_number)

@section('content')
@include('hr._nav', ['showEmployerFilter' => false])
@include('hr._errors')

<a href="{{ route('hr.payroll.index') }}" class="sx-muted small text-decoration-none d-inline-flex align-items-center gap-1 mb-3"><i class="bi bi-arrow-left"></i> All payroll</a>

<div class="sx-card mb-3">
    <div class="sx-card-body d-flex flex-wrap justify-content-between gap-3 align-items-center">
        <div>
            <span class="sx-pill {{ PayrollRun::STATUSES[$run->status][1] }}" style="font-size:.8rem">{{ PayrollRun::STATUSES[$run->status][0] }}</span>
            <div class="small sx-muted mt-1">
                Created by {{ $run->creator?->name ?? 'someone' }} on {{ $run->created_at->format('d M Y') }}
                @if($run->approved_at) · approved by {{ $run->approver?->name ?? '—' }} {{ $run->approved_at->format('d M Y') }}@endif
                @if($run->paid_at) · paid {{ $run->paid_at->format('d M Y') }} by {{ \App\Models\Employee::PAYMENT_METHODS[$run->payment_method] ?? $run->payment_method }}@endif
                @if($run->expense) · booked as expense {{ $run->expense->expense_number }}@endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($run->status === 'draft')
                <form method="POST" action="{{ route('hr.payroll.approve', $run) }}" class="m-0" onsubmit="return confirm('Approve this payroll? Save any changes first; amounts are locked after approval.');">@csrf
                    <button class="sx-btn sx-btn-success"><i class="bi bi-check2-circle"></i> Approve</button></form>
            @elseif($run->status === 'approved')
                <form method="POST" action="{{ route('hr.payroll.reopen', $run) }}" class="m-0">@csrf
                    <button class="sx-btn sx-btn-outline"><i class="bi bi-unlock"></i> Reopen</button></form>
                <button class="sx-btn sx-btn-success" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-cash-coin"></i> Mark as paid</button>
            @endif
            @if($run->status !== 'paid')
                <form method="POST" action="{{ route('hr.payroll.destroy', $run) }}" class="m-0" onsubmit="return confirm('Delete this payroll and its payslips?');">@csrf @method('DELETE')
                    <button class="sx-btn sx-btn-danger"><i class="bi bi-trash3"></i> Delete</button></form>
            @endif
        </div>
    </div>
</div>

<div class="sx-stats">
    <div class="sx-stat"><div class="sx-label">Employees</div><div class="sx-value">{{ $payslips->count() }}</div></div>
    <div class="sx-stat"><div class="sx-label">Gross pay</div><div class="sx-value">{{ $money($run->total_gross) }}</div></div>
    <div class="sx-stat"><div class="sx-label">Deductions</div><div class="sx-value">{{ $money($run->total_deductions) }}</div></div>
    <div class="sx-stat sx-ok"><div class="sx-label">Net pay to send</div><div class="sx-value">{{ $money($run->total_net) }}</div></div>
</div>

<div class="sx-card">
    <div class="sx-card-head">
        <h2>Payslips</h2>
        @if($editable)<span class="small sx-muted">Enter allowances and deductions (PAYE, pension and others), then save.</span>@endif
    </div>
    <form method="POST" action="{{ route('hr.payroll.update', $run) }}" id="payrollForm">
        @csrf @method('PUT')
        <div class="table-responsive position-relative">
            <table class="sx-table">
                <thead>
                    <tr>
                        <th>Employee</th><th class="sx-num">Base</th><th class="sx-num">Allowances</th><th class="sx-num">Overtime</th>
                        <th class="sx-num">PAYE</th><th class="sx-num">Pension</th><th class="sx-num">Other ded.</th><th class="sx-num">Net pay</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($payslips as $slip)
                    <tr>
                        <td style="min-width:180px">
                            <div class="fw-semibold">{{ $slip->employee?->full_name }}</div>
                            <div class="sx-muted small">{{ $slip->employee?->job_title }}@if($slip->days_absent) · <span class="text-danger">{{ $slip->days_absent }} {{ \Illuminate\Support\Str::plural('day', $slip->days_absent) }} absent</span>@endif</div>
                        </td>
                        @foreach(['base_salary', 'allowances', 'overtime', 'paye', 'pension', 'other_deductions'] as $field)
                            <td class="sx-num" style="min-width:110px">
                                @if($editable)
                                    <label for="{{ $field }}{{ $slip->id }}" class="visually-hidden">{{ str_replace('_', ' ', $field) }} for {{ $slip->employee?->full_name }}</label>
                                    <input id="{{ $field }}{{ $slip->id }}" type="number" step="0.01" min="0" name="lines[{{ $slip->id }}][{{ $field }}]"
                                           value="{{ old("lines.{$slip->id}.{$field}", $amount($slip->{$field})) }}" class="form-control form-control-sm text-end">
                                @else
                                    {{ number_format((float) $slip->{$field}, $run->currency === 'RWF' ? 0 : 2) }}
                                @endif
                            </td>
                        @endforeach
                        <td class="sx-num fw-semibold">{{ $money($slip->net_pay) }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('hr.payslips.show', $slip) }}" target="_blank" class="btn btn-sm btn-light" title="Payslip" aria-label="Payslip for {{ $slip->employee?->full_name }}"><i class="bi bi-file-earmark-text"></i></a>
                            @if($editable)
                                <button type="submit" form="remove{{ $slip->id }}" class="btn btn-sm btn-light text-danger" title="Remove from this payroll" aria-label="Remove {{ $slip->employee?->full_name }} from this payroll"><i class="bi bi-x-lg"></i></button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($editable)
            <div class="p-3 d-flex justify-content-end"><button class="sx-btn sx-btn-primary"><i class="bi bi-calculator"></i> Save and recalculate</button></div>
        @endif
    </form>

    @if($editable)
        @foreach($payslips as $slip)
            <form id="remove{{ $slip->id }}" method="POST" action="{{ route('hr.payroll.payslips.destroy', [$run, $slip]) }}" class="d-none" onsubmit="return confirm('Remove this employee from the payroll?');">@csrf @method('DELETE')</form>
        @endforeach
    @endif
</div>

@if($run->status === 'approved')
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('hr.payroll.pay', $run) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title" id="payModalLabel">Mark payroll as paid</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body row g-3">
                <div class="col-sm-6">
                    <label for="payMethod" class="form-label">Paid by</label>
                    <select id="payMethod" name="payment_method" class="form-select">
                        @foreach($paymentMethods as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                    </select>
                </div>
                <div class="col-sm-6">
                    <label for="payDate" class="form-label">Paid on</label>
                    <input id="payDate" type="date" name="paid_on" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required class="form-control">
                </div>
                <div class="col-12 small sx-muted">
                    {{ $money($run->total_net) }} in net pay.
                    @if($run->property_id) It will be recorded as a "Salaries" expense for {{ $run->employer_label }}.@endif
                    A paid payroll can no longer be changed or deleted.
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="sx-btn sx-btn-outline" data-bs-dismiss="modal">Cancel</button><button class="sx-btn sx-btn-success">Mark as paid</button></div>
        </form>
    </div>
</div>
@endif
@endsection
