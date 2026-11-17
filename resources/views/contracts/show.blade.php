<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Lease {{ $lease->lease_number }} | Terra</title>
    @include('partials.fonts')
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f5; color: #1d2433; font-size: 14.5px; line-height: 1.65; }
        .doc { max-width: 820px; margin: 24px auto; background: #fff; padding: 48px 56px; box-shadow: 0 6px 24px rgba(0,0,0,.08); }
        .draft { background: #fff3dc; color: #8f5a08; text-align: center; padding: 8px; font-weight: 700; letter-spacing: .1em; margin: -48px -56px 32px; }
        h1 { text-align: center; color: #19265d; font-size: 1.5rem; margin: 0 0 4px; letter-spacing: .02em; }
        .sub { text-align: center; color: #6b7280; margin-bottom: 30px; }
        h2 { font-size: 1.02rem; color: #19265d; margin: 22px 0 6px; }
        p { margin: 0 0 8px; white-space: pre-line; text-align: justify; }
        .sign { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; margin-top: 56px; }
        .sign div { border-top: 1px solid #1d2433; padding-top: 8px; font-size: .88rem; color: #4b5563; }
        .accept { margin-top: 28px; padding: 12px 14px; background: #e8f7ee; border-radius: 8px; font-size: .9rem; color: #147847; }
        .toolbar { max-width: 820px; margin: 16px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .toolbar a, .toolbar button { font: inherit; font-size: .9rem; padding: 8px 16px; border-radius: 999px; border: 1px solid #19265d; background: #fff; color: #19265d; cursor: pointer; text-decoration: none; }
        .toolbar .primary { background: #19265d; color: #fff; }
        @media print { body { background: #fff; } .doc { box-shadow: none; margin: 0; padding: 0; max-width: none; } .draft { margin: 0 0 20px; } .toolbar { display: none; } h2 { page-break-after: avoid; } }
        @media (max-width: 640px) { .doc { padding: 24px; margin: 0; } .draft { margin: -24px -24px 20px; } .sign { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="toolbar">
    @if($role === 'staff' && ! $contract->isFinal())<a href="{{ route('contracts.edit', $lease) }}">Back to editing</a>@endif
    <button type="button" class="primary" onclick="window.print()">Print / save as PDF</button>
</div>
<div class="doc">
    @unless($contract->isFinal())<div class="draft">DRAFT — NOT YET FINAL</div>@endunless
    <h1>Residential Lease Agreement</h1>
    <div class="sub">Lease {{ $lease->lease_number }} · {{ $lease->unit?->building?->property?->name }}, Unit {{ $lease->unit?->unit_number }}</div>

    @foreach($contract->clauses as $i => $c)
        <h2>{{ $i + 1 }}. {{ $c['title'] }}</h2>
        <p>{{ $c['body'] }}</p>
    @endforeach

    @if($lease->tenant_accepted_at)
        <div class="accept">Accepted electronically by {{ $lease->tenant?->full_name }} on {{ $lease->tenant_accepted_at->format('d F Y, H:i') }} through the Terra tenant portal.</div>
    @endif

    <div class="sign">
        <div>Landlord / authorised manager<br><br>Name, signature &amp; date</div>
        <div>Tenant: {{ $lease->tenant?->full_name }}<br><br>Signature &amp; date</div>
    </div>
</div>
</body>
</html>
