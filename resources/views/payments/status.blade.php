@extends('layouts.app')

@section('title', 'Payment ' . $intent->reference)
@section('page_heading', 'Payment status')
@section('page_subtitle', 'Reference ' . $intent->reference)

@php $cur = $intent->currency ?: 'RWF'; $amount = $cur . ' ' . number_format((float) $intent->amount, $cur === 'RWF' ? 0 : 2); @endphp

@section('content')
<div class="mx-auto" style="max-width:560px;">
    <div class="card border-0 shadow-sm text-center" style="border-radius:16px;">
        <div class="card-body p-4 p-md-5" id="payState" data-status="{{ $intent->status }}" data-url="{{ route('payments.online.status', $intent) }}">

            <div data-show="pending" @if($intent->status !== 'pending') hidden @endif>
                <div class="spinner-border text-warning mb-3" style="width:3rem;height:3rem;" role="status" aria-hidden="true"></div>
                <h2 class="h4 fw-bold" style="color:var(--terra-navy);">Check your phone</h2>
                <p class="text-secondary mb-1">Approve the {{ $amount }} payment request sent to <strong>{{ $intent->phone }}</strong> with your Mobile Money PIN.</p>
                <p class="small text-secondary">This page updates by itself.</p>

                @if($isSandbox)
                    <div class="border rounded-3 p-3 mt-3 text-start" style="background:#fffaf2;">
                        <div class="small fw-semibold mb-2"><i class="bi bi-cone-striped me-1"></i> Test mode — simulate the phone</div>
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('payments.online.sandbox', $intent) }}" class="flex-fill">@csrf<input type="hidden" name="decision" value="approve">
                                <button class="btn btn-success w-100 rounded-pill">Approve</button></form>
                            <form method="POST" action="{{ route('payments.online.sandbox', $intent) }}" class="flex-fill">@csrf<input type="hidden" name="decision" value="decline">
                                <button class="btn btn-outline-danger w-100 rounded-pill">Decline</button></form>
                        </div>
                    </div>
                @endif
            </div>

            <div data-show="successful" @if($intent->status !== 'successful') hidden @endif>
                <i class="bi bi-check-circle-fill text-success" style="font-size:3.5rem;"></i>
                <h2 class="h4 fw-bold mt-2" style="color:var(--terra-navy);">Payment received</h2>
                <p class="text-secondary">{{ $amount }} has been recorded against invoice {{ $intent->invoice?->invoice_number }}. Thank you!</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ $intent->rent_payment_id ? route('receipts.show', $intent->rent_payment_id) : '#' }}" data-receipt class="btn rounded-pill text-white px-4" style="background:var(--terra-orange);">
                        <i class="bi bi-receipt me-1"></i> View receipt
                    </a>
                    <a href="{{ route('ledger.show', $intent->lease_id) }}" class="btn btn-outline-secondary rounded-pill px-4">Rent ledger</a>
                </div>
            </div>

            <div data-show="failed expired" @if(! in_array($intent->status, ['failed', 'expired'], true)) hidden @endif>
                <i class="bi bi-x-circle-fill text-danger" style="font-size:3.5rem;"></i>
                <h2 class="h4 fw-bold mt-2" style="color:var(--terra-navy);">Payment not completed</h2>
                <p class="text-secondary" data-reason>{{ $intent->failure_reason ?: 'The payment was cancelled or timed out.' }}</p>
                <p class="small text-secondary">No money was taken. You can try again.</p>
                @if($intent->rent_invoice_id)
                    <a href="{{ route('payments.online.create', $intent->rent_invoice_id) }}" class="btn btn-outline-primary rounded-pill px-4">Try again</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var box = document.getElementById('payState');
    if (box.dataset.status !== 'pending') return;
    var tries = 0;
    var timer = setInterval(function () {
        if (document.hidden) return;
        if (++tries > 120) { clearInterval(timer); return; } // stop after ~10 minutes
        fetch(box.dataset.url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d || d.status === 'pending') return;
                clearInterval(timer);
                box.querySelectorAll('[data-show]').forEach(function (el) {
                    el.hidden = el.dataset.show.split(' ').indexOf(d.status) === -1;
                });
                if (d.receipt) box.querySelector('[data-receipt]').href = d.receipt;
                if (d.reason) box.querySelector('[data-reason]').textContent = d.reason;
            })
            .catch(function () {});
    }, 5000);
})();
</script>
@endpush
