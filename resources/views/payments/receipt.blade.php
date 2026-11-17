<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Receipt {{ $payment->payment_number }} | Terra</title>
    @include('partials.fonts')
    @php
        $cur = $payment->currency ?: 'RWF';
        $fmt = fn ($v) => $cur . ' ' . number_format((float) $v, $cur === 'RWF' ? 0 : 2);
        $periods = $payment->allocations->map(fn ($a) => $a->invoice)->filter()
            ->map(fn ($i) => $i->billing_period_start ? $i->billing_period_start->format('d M Y') . ' – ' . $i->billing_period_end?->format('d M Y') : $i->invoice_number);
    @endphp
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f5; color: #1d2433; font-size: 14px; line-height: 1.5; }
        .receipt { max-width: 640px; margin: 24px auto; background: #fff; padding: 36px 40px; box-shadow: 0 6px 24px rgba(0,0,0,.08); border-top: 6px solid #19265d; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 22px; }
        .brand { font-size: 1.6rem; font-weight: 800; color: #19265d; letter-spacing: .02em; line-height: 1; }
        .brand small { display: block; font-size: .7rem; font-weight: 600; color: #D05208; letter-spacing: .1em; text-transform: uppercase; margin-top: 4px; }
        .paid { display: inline-block; padding: 6px 14px; border: 2px solid #147847; color: #147847; border-radius: 8px; font-weight: 800; letter-spacing: .12em; transform: rotate(-4deg); }
        h1 { font-size: 1.15rem; margin: 0 0 2px; color: #19265d; }
        .amount { text-align: center; margin: 18px 0 22px; padding: 18px; background: #f6f7fb; border-radius: 12px; }
        .amount strong { display: block; font-size: 2rem; color: #19265d; }
        .amount span { color: #6b7280; }
        dl { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; margin: 0 0 18px; }
        dt { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: #6b7280; font-weight: 700; }
        dd { margin: 2px 0 0; font-weight: 600; word-break: break-word; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 16px; font-size: .9rem; }
        th, td { padding: 7px 6px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; }
        .num { text-align: right; }
        .foot { font-size: .8rem; color: #6b7280; border-top: 1px dashed #d1d5db; padding-top: 12px; }
        .toolbar { max-width: 640px; margin: 16px auto 0; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }
        .toolbar button, .toolbar a { font: inherit; font-size: .9rem; padding: 8px 16px; border-radius: 999px; border: 1px solid #19265d; background: #fff; color: #19265d; cursor: pointer; text-decoration: none; }
        .toolbar .primary { background: #19265d; color: #fff; }
        @media print { body { background: #fff; } .receipt { box-shadow: none; margin: 0; max-width: none; } .toolbar { display: none; } }
        @media (max-width: 560px) { .receipt { padding: 22px; margin: 0; } dl { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
@if($shareUrl)
    <div class="toolbar">
        <a href="{{ url()->previous() }}">Back</a>
        <a href="https://wa.me/?text={{ rawurlencode('Rent receipt ' . $payment->payment_number . ': ' . $shareUrl) }}" target="_blank" rel="noopener">Share on WhatsApp</a>
        <button type="button" id="copyLink" data-url="{{ $shareUrl }}">Copy share link</button>
        <button type="button" class="primary" onclick="window.print()">Download PDF / Print</button>
    </div>
@endif

<div class="receipt">
    <div class="head">
        <div>
            <div class="brand">TERRA<small>Property Management</small></div>
            <div style="margin-top:10px;font-size:.85rem;color:#4b5563;">
                {{ $property?->name }}<br>
                @if($manager?->user)Managed by {{ $manager->user->name }}@if($manager->phone) · {{ $manager->phone }}@endif<br>@endif
                @if($property?->owner)Owner: {{ $property->owner->name }}@endif
            </div>
        </div>
        <div style="text-align:right">
            <h1>Payment receipt</h1>
            <div>{{ $payment->payment_number }}</div>
            <div style="margin-top:10px"><span class="paid">PAID</span></div>
        </div>
    </div>

    <div class="amount">
        <strong>{{ $fmt($payment->amount) }}</strong>
        <span>received on {{ $payment->payment_date?->format('d F Y') }}</span>
    </div>

    <dl>
        <div><dt>Tenant</dt><dd>{{ $payment->tenant?->full_name }}</dd></div>
        <div><dt>Property / unit</dt><dd>{{ $property?->name }} · Unit {{ $payment->unit?->unit_number }}</dd></div>
        <div><dt>Payment method</dt><dd>{{ ucwords(str_replace('_', ' ', (string) $payment->payment_method)) }}{{ $payment->provider ? ' · ' . $payment->provider : '' }}</dd></div>
        <div><dt>Transaction ID</dt><dd>{{ $payment->transaction_reference ?: $payment->payment_number }}</dd></div>
        <div><dt>Lease</dt><dd>{{ $payment->lease?->lease_number }}</dd></div>
        <div><dt>Payment period</dt><dd>{{ $periods->isNotEmpty() ? $periods->implode(', ') : '—' }}</dd></div>
    </dl>

    @if($payment->allocations->isNotEmpty())
        <table>
            <thead><tr><th>Applied to invoice</th><th>Period</th><th class="num">Amount</th></tr></thead>
            <tbody>
                @foreach($payment->allocations as $a)
                    <tr>
                        <td>{{ $a->invoice?->invoice_number }}</td>
                        <td>{{ $a->invoice?->billing_period_start?->format('M Y') }}</td>
                        <td class="num">{{ $fmt($a->amount) }}</td>
                    </tr>
                @endforeach
                @if((float) $payment->amount > (float) $payment->allocations->sum('amount'))
                    <tr><td colspan="2">Credit on account</td><td class="num">{{ $fmt((float) $payment->amount - (float) $payment->allocations->sum('amount')) }}</td></tr>
                @endif
            </tbody>
        </table>
    @endif

    <div class="foot">
        Issued {{ now()->format('d M Y, H:i') }} by Terra Property Management System on behalf of the landlord / property manager.
        This receipt confirms the payment was recorded in the Terra rent ledger.
    </div>
</div>

<script>
(function () {
    var b = document.getElementById('copyLink');
    if (!b) return;
    b.addEventListener('click', function () {
        var url = b.dataset.url;
        var done = function () { b.textContent = 'Link copied ✓'; setTimeout(function () { b.textContent = 'Copy share link'; }, 2000); };
        if (navigator.clipboard) navigator.clipboard.writeText(url).then(done, function () { prompt('Copy this link', url); });
        else prompt('Copy this link', url);
    });
})();
</script>
</body>
</html>
