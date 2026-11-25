<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Payslip {{ $employee->full_name }} {{ $run->period_label }} | Terra</title>
    @include('partials.fonts')
    @php
        $cur = $run->currency ?: 'RWF';
        $fmt = fn ($v) => $cur . ' ' . number_format((float) $v, $cur === 'RWF' ? 0 : 2);
        $methods = \App\Models\Employee::PAYMENT_METHODS;
    @endphp
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f5; color: #1d2433; font-size: 14px; line-height: 1.5; }
        .slip { max-width: 720px; margin: 24px auto; background: #fff; padding: 36px 40px; box-shadow: 0 6px 24px rgba(0,0,0,.08); border-top: 6px solid #19265d; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 22px; }
        .brand { font-size: 1.6rem; font-weight: 800; color: #19265d; letter-spacing: .02em; line-height: 1; }
        .brand small { display: block; font-size: .7rem; font-weight: 600; color: #D05208; letter-spacing: .1em; text-transform: uppercase; margin-top: 4px; }
        h1 { font-size: 1.15rem; margin: 0 0 2px; color: #19265d; text-align: right; }
        .muted { color: #667085; font-size: .85rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 24px; margin-bottom: 20px; font-size: .9rem; }
        .grid dt { color: #667085; } .grid dd { margin: 0 0 6px; font-weight: 600; color: #19265d; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th { text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #667085; border-bottom: 1px solid #e4e7ec; padding: 6px 0; }
        td { padding: 7px 0; border-bottom: 1px solid #f2f4f7; }
        td.n, th.n { text-align: right; font-variant-numeric: tabular-nums; }
        tr.total td { font-weight: 700; color: #19265d; border-bottom: 0; }
        .net { display: flex; justify-content: space-between; align-items: center; padding: 16px 18px; background: #f6f7fb; border-radius: 12px; margin-top: 8px; }
        .net strong { font-size: 1.6rem; color: #19265d; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: .75rem; font-weight: 700; background: #f2f4f7; color: #475467; }
        .status.paid { background: #ecfdf3; color: #067647; }
        .foot { margin-top: 26px; font-size: .78rem; color: #98a2b3; display: flex; justify-content: space-between; gap: 12px; }
        .actions { max-width: 720px; margin: 0 auto 24px; display: flex; justify-content: flex-end; gap: 8px; }
        .actions button { border: 0; background: #19265d; color: #fff; padding: 9px 16px; border-radius: 9px; font-weight: 600; cursor: pointer; }
        @media (max-width: 560px) { .slip { padding: 24px 18px; margin: 0; } .grid { grid-template-columns: 1fr; } .head { flex-direction: column; } h1 { text-align: left; } }
        @media print { body { background: #fff; } .slip { box-shadow: none; margin: 0; max-width: none; } .actions { display: none; } }
    </style>
</head>
<body>
<main class="slip">
    <div class="head">
        <div class="brand">TERRA<small>{{ $run->employer_label }}</small></div>
        <div>
            <h1>Payslip · {{ $run->period_label }}</h1>
            <div class="muted" style="text-align:right">{{ $run->run_number }}
                <span class="status {{ $run->status === 'paid' ? 'paid' : '' }}">{{ \App\Models\PayrollRun::STATUSES[$run->status][0] }}</span></div>
        </div>
    </div>

    <dl class="grid">
        <div><dt>Employee</dt><dd>{{ $employee->full_name }}</dd></div>
        <div><dt>Employee number</dt><dd>{{ $employee->employee_number }}</dd></div>
        <div><dt>Job title</dt><dd>{{ $employee->job_title }}</dd></div>
        <div><dt>National ID</dt><dd>{{ $employee->national_id ?: '—' }}</dd></div>
        <div><dt>Paid by</dt><dd>{{ $methods[$run->payment_method ?? $employee->payment_method] ?? '—' }}{{ $employee->account_number ? ' · ' . $employee->account_number : '' }}</dd></div>
        <div><dt>Pay date</dt><dd>{{ $run->paid_at?->format('d M Y') ?? 'Not paid yet' }}</dd></div>
    </dl>

    <table>
        <thead><tr><th>Earnings</th><th class="n">Amount</th></tr></thead>
        <tbody>
            <tr><td>Base salary</td><td class="n">{{ $fmt($payslip->base_salary) }}</td></tr>
            @if((float) $payslip->allowances)<tr><td>Allowances</td><td class="n">{{ $fmt($payslip->allowances) }}</td></tr>@endif
            @if((float) $payslip->overtime)<tr><td>Overtime</td><td class="n">{{ $fmt($payslip->overtime) }}</td></tr>@endif
            <tr class="total"><td>Gross pay</td><td class="n">{{ $fmt($payslip->gross_pay) }}</td></tr>
        </tbody>
    </table>

    <table>
        <thead><tr><th>Deductions</th><th class="n">Amount</th></tr></thead>
        <tbody>
            <tr><td>PAYE (income tax)</td><td class="n">{{ $fmt($payslip->paye) }}</td></tr>
            <tr><td>Pension</td><td class="n">{{ $fmt($payslip->pension) }}</td></tr>
            @if((float) $payslip->other_deductions)<tr><td>Other deductions{{ $payslip->note ? ' (' . $payslip->note . ')' : '' }}</td><td class="n">{{ $fmt($payslip->other_deductions) }}</td></tr>@endif
            <tr class="total"><td>Total deductions</td><td class="n">{{ $fmt($payslip->total_deductions) }}</td></tr>
        </tbody>
    </table>

    <div class="net"><span>Net pay</span><strong>{{ $fmt($payslip->net_pay) }}</strong></div>

    @if($payslip->days_absent)
        <p class="muted" style="margin-top:12px">Days marked absent this month: {{ $payslip->days_absent }}.</p>
    @endif

    <div class="foot"><span>Generated by Terra Smart Rental Management</span><span>{{ now()->format('d M Y H:i') }}</span></div>
</main>
<div class="actions"><button type="button" onclick="window.print()">Print / save as PDF</button></div>
</body>
</html>
