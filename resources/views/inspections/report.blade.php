<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $inspection->type_label }} {{ $inspection->inspection_number }} | Terra</title>
    @include('partials.fonts')
    @php
        $moveOut = $inspection->isMoveOut();
        $currency = $inspection->currency ?: 'RWF';
        $property = $inspection->unit?->building?->property;
    @endphp
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f5; color: #1d2433; font-size: 13.5px; line-height: 1.5; }
        .page { max-width: 900px; margin: 24px auto; background: #fff; padding: 36px 40px; box-shadow: 0 6px 24px rgba(0,0,0,.08); }
        .top { display: flex; justify-content: space-between; gap: 20px; border-bottom: 3px solid #19265d; padding-bottom: 14px; margin-bottom: 18px; }
        .brand { font-size: 1.6rem; font-weight: 800; color: #19265d; letter-spacing: .02em; }
        .brand small { display: block; font-size: .75rem; font-weight: 600; color: #D05208; letter-spacing: .08em; text-transform: uppercase; }
        h1 { font-size: 1.35rem; margin: 0 0 4px; color: #19265d; }
        .meta { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px 18px; margin: 0 0 18px; }
        .meta dt { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; font-weight: 700; }
        .meta dd { margin: 0; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; background: #f8f9fb; }
        tr.area td { background: #f1f3f8; font-weight: 700; color: #19265d; }
        tr.worse td { background: #fff5f5; }
        .num { text-align: right; white-space: nowrap; }
        .totals { margin-left: auto; width: 320px; }
        .totals td { border: 0; padding: 4px 8px; }
        .totals tr:last-child td { border-top: 2px solid #19265d; font-weight: 800; }
        .sign { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 36px; }
        .sign div { border-top: 1px solid #1d2433; padding-top: 6px; font-size: .85rem; color: #4b5563; }
        .note { font-size: .8rem; color: #6b7280; margin-top: 18px; }
        .toolbar { max-width: 900px; margin: 16px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .toolbar button, .toolbar a { font: inherit; padding: 8px 18px; border-radius: 999px; border: 1px solid #19265d; background: #19265d; color: #fff; cursor: pointer; text-decoration: none; }
        .toolbar a { background: #fff; color: #19265d; }
        @media print { body { background: #fff; } .page { box-shadow: none; margin: 0; padding: 0; max-width: none; } .toolbar { display: none; } tr { page-break-inside: avoid; } }
        @media (max-width: 640px) { .page { padding: 20px; margin: 0; } .meta { grid-template-columns: 1fr 1fr; } .sign { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="toolbar">
    <a href="{{ route('inspections.show', $inspection) }}">Back</a>
    <button type="button" onclick="window.print()">Print / save as PDF</button>
</div>

<div class="page">
    <div class="top">
        <div class="brand">TERRA<small>Property Management</small></div>
        <div style="text-align:right">
            <h1>{{ $inspection->type_label }}</h1>
            <div>{{ $inspection->inspection_number }} · {{ \App\Models\Inspection::STATUSES[$inspection->status] ?? $inspection->status }}</div>
        </div>
    </div>

    <dl class="meta">
        <div><dt>Property</dt><dd>{{ $property?->name }}</dd></div>
        <div><dt>Unit</dt><dd>{{ $inspection->unit?->unit_number }}</dd></div>
        <div><dt>Tenant</dt><dd>{{ $inspection->lease?->tenant?->full_name }}</dd></div>
        <div><dt>Lease</dt><dd>{{ $inspection->lease?->lease_number }}</dd></div>
        <div><dt>Inspection date</dt><dd>{{ $inspection->inspection_date?->format('d M Y') }}</dd></div>
        <div><dt>Inspected by</dt><dd>{{ $inspection->inspector?->name }}</dd></div>
        <div><dt>Electricity / water</dt><dd>{{ $inspection->electricity_reading ?? '—' }} / {{ $inspection->water_reading ?? '—' }}</dd></div>
        <div><dt>Keys</dt><dd>{{ $inspection->keys_handed ?? '—' }}</dd></div>
    </dl>

    @if($inspection->general_notes)<p><strong>General notes:</strong> {{ $inspection->general_notes }}</p>@endif

    <table>
        <thead>
            <tr>
                <th>Item</th>
                @if($moveOut)<th>Move-in</th><th>Move-out</th>@else<th>Condition</th><th>Qty</th>@endif
                <th>Notes</th>
                @if($moveOut)<th class="num">Deduction</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach($areas as $area => $items)
                <tr class="area"><td colspan="{{ $moveOut ? 5 : 4 }}">{{ $area }}</td></tr>
                @foreach($items as $item)
                    <tr class="{{ $moveOut && $item->changeFromBaseline() === 'worse' ? 'worse' : '' }}">
                        <td>{{ $item->item }}</td>
                        @if($moveOut)
                            <td>{{ $item->baseline?->condition_label ?? '—' }}</td>
                            <td>{{ $item->condition_label ?? '—' }}</td>
                        @else
                            <td>{{ $item->condition_label ?? '—' }}</td>
                            <td>{{ $item->quantity }}</td>
                        @endif
                        <td>{{ $item->notes }}</td>
                        @if($moveOut)<td class="num">{{ (float) $item->deduction_amount > 0 ? number_format((float) $item->deduction_amount) : '' }}</td>@endif
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    @if($moveOut)
        <table class="totals">
            <tr><td>Deposit held</td><td class="num">{{ $currency }} {{ number_format((float) $inspection->deposit_held) }}</td></tr>
            <tr><td>Deductions</td><td class="num">− {{ $currency }} {{ number_format((float) $inspection->deposit_deductions) }}</td></tr>
            <tr><td>Refund to tenant</td><td class="num">{{ $currency }} {{ number_format((float) $inspection->deposit_refund) }}</td></tr>
        </table>
    @endif

    <p class="note">{{ $inspection->media->count() }} photo/video file(s) are attached to this inspection in Terra.
        @if($inspection->tenant_responded_at) Tenant response recorded {{ $inspection->tenant_responded_at->format('d M Y, H:i') }}{{ $inspection->tenant_comment ? ': “' . $inspection->tenant_comment . '”' : '' }}.@endif
    </p>

    <div class="sign">
        <div>Tenant: {{ $inspection->lease?->tenant?->full_name }}<br><br>Signature &amp; date</div>
        <div>Landlord / property manager: {{ $inspection->inspector?->name }}<br><br>Signature &amp; date</div>
    </div>
</div>
</body>
</html>
