@extends('layouts.app')

@section('title', 'Pay invoice ' . $invoice->invoice_number)
@section('page_heading', 'Pay rent online')
@section('page_subtitle', 'Invoice ' . $invoice->invoice_number . ' · Unit ' . $invoice->unit?->unit_number . ' · ' . $invoice->unit?->building?->property?->name)

@php $cur = $invoice->currency ?: 'RWF'; $fmt = fn ($v) => $cur . ' ' . number_format((float) $v, $cur === 'RWF' ? 0 : 2); @endphp

@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body p-4">
                @if($provider->name() === 'sandbox')
                    <div class="alert alert-warning small"><i class="bi bi-cone-striped me-1"></i>
                        <strong>Test mode.</strong> No money moves. Use any number; numbers ending in <code>0000</code> are declined.</div>
                @endif

                <form method="POST" action="{{ route('payments.online.store', $invoice) }}" id="payForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="amount">Amount to pay</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">{{ $cur }}</span>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount"
                                   min="{{ $minimum }}" max="{{ (float) $invoice->balance_due }}" step="1" required
                                   value="{{ old('amount', (float) $invoice->balance_due) }}">
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">Outstanding: {{ $fmt($invoice->balance_due) }}. You can pay part of it.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="phone">Mobile Money number</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="bi bi-phone"></i></span>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" required
                                   inputmode="tel" autocomplete="tel" placeholder="0788 123 456" value="{{ old('phone', $phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">You'll get a prompt on this phone to approve the payment with your PIN.</div>
                    </div>

                    <button type="submit" class="btn btn-lg w-100 rounded-pill fw-semibold text-white" style="background:var(--terra-orange);">
                        <i class="bi bi-lock-fill me-1"></i> Pay with {{ $provider->label() }}
                    </button>
                    <p class="small text-secondary text-center mt-3 mb-0">
                        The payment goes straight to your landlord's account. Terra records it and sends your receipt.
                    </p>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3" style="color:var(--terra-navy);">Invoice summary</h2>
                <dl class="row small mb-0">
                    <dt class="col-6 text-secondary fw-normal">Invoice</dt><dd class="col-6 text-end fw-semibold">{{ $invoice->invoice_number }}</dd>
                    <dt class="col-6 text-secondary fw-normal">Period</dt><dd class="col-6 text-end">{{ $invoice->billing_period_start?->format('d M') }} – {{ $invoice->billing_period_end?->format('d M Y') }}</dd>
                    <dt class="col-6 text-secondary fw-normal">Due date</dt><dd class="col-6 text-end {{ $invoice->due_date?->isPast() ? 'text-danger fw-semibold' : '' }}">{{ $invoice->due_date?->format('d M Y') }}</dd>
                    <dt class="col-6 text-secondary fw-normal">Total</dt><dd class="col-6 text-end">{{ $fmt($invoice->total_amount) }}</dd>
                    <dt class="col-6 text-secondary fw-normal">Paid so far</dt><dd class="col-6 text-end">{{ $fmt($invoice->amount_paid) }}</dd>
                    <dt class="col-6 fw-bold">Balance due</dt><dd class="col-6 text-end fw-bold" style="color:var(--terra-orange);">{{ $fmt($invoice->balance_due) }}</dd>
                </dl>
            </div>
        </div>

        @if($recent->isNotEmpty())
            <div class="card border-0 shadow-sm mt-3" style="border-radius:14px;">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-2" style="color:var(--terra-navy);">Recent attempts</h2>
                    @foreach($recent as $r)
                        <a href="{{ route('payments.online.show', $r) }}" class="d-flex justify-content-between small py-1 text-decoration-none text-reset">
                            <span>{{ $r->created_at->format('d M H:i') }} · {{ $fmt($r->amount) }}</span>
                            <span class="badge rounded-pill {{ ['successful' => 'text-bg-success', 'failed' => 'text-bg-danger', 'expired' => 'text-bg-secondary'][$r->status] ?? 'text-bg-warning' }}">{{ ucfirst($r->status) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
