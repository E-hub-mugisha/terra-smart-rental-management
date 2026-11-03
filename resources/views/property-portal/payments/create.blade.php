
@extends('layouts.app')

@section('title', 'Record Payment | Terra Smart Rental Management')

@section('content')
@php
    $paymentMethods = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'mobile_money' => 'Mobile money',
        'card' => 'Card',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ];

    $selectedInvoiceId = old('allocations.0.invoice_id');
@endphp

<style>
    .payment-page {
        --terra-orange: #D05208;
        --terra-navy: #19265d;
        color: #263247;
    }

    .payment-page .page-heading {
        color: var(--terra-navy);
        font-weight: 750;
        letter-spacing: -.5px;
    }

    .payment-page .panel {
        background: #fff;
        border: 1px solid #e8ebf1;
        border-radius: 14px;
        box-shadow: 0 4px 18px rgba(25, 38, 93, .045);
    }

    .payment-page .panel-heading {
        color: var(--terra-navy);
        font-size: 1rem;
        font-weight: 700;
    }

    .payment-page .section-icon {
        display: inline-flex;
        width: 38px;
        height: 38px;
        align-items: center;
        justify-content: center;
        background: #fff0e7;
        color: var(--terra-orange);
        border-radius: 10px;
        font-size: 1.1rem;
    }

    .payment-page .form-label {
        color: #344054;
        font-size: .86rem;
        font-weight: 650;
        margin-bottom: .45rem;
    }

    .payment-page .form-control,
    .payment-page .form-select {
        min-height: 44px;
        border-color: #dce1e9;
        border-radius: 9px;
        font-size: .9rem;
    }

    .payment-page .form-control:focus,
    .payment-page .form-select:focus {
        border-color: var(--terra-orange);
        box-shadow: 0 0 0 .2rem rgba(208, 82, 8, .1);
    }

    .payment-page .btn-terra {
        background: var(--terra-orange);
        border-color: var(--terra-orange);
        color: #fff;
    }

    .payment-page .btn-terra:hover {
        background: #b84606;
        border-color: #b84606;
        color: #fff;
    }

    .payment-page .text-navy {
        color: var(--terra-navy);
    }

    .payment-page .allocation-row {
        background: #fafbfc;
        border: 1px solid #e8ebf1;
        border-radius: 11px;
        padding: 16px;
    }

    .payment-page .summary-value {
        color: var(--terra-navy);
        font-weight: 750;
        font-size: 1.15rem;
    }

    .payment-page .hint {
        color: #7b8495;
        font-size: .78rem;
    }
</style>

<div class="container-fluid py-4 payment-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ route('property-manager.payments.index') }}"
               class="text-decoration-none text-secondary small d-inline-flex align-items-center gap-2 mb-2">
                <i class="bi bi-arrow-left"></i>
                Back to payments
            </a>

            <h1 class="page-heading h3 mb-1">Record a payment</h1>
            <p class="text-secondary mb-0">
                Record rent or another lease payment and allocate it to outstanding invoices.
            </p>
        </div>

        <span class="badge rounded-pill px-3 py-2"
              style="background:#fff0e7;color:#D05208;">
            <i class="bi bi-shield-check me-1"></i>
            Property manager
        </span>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
            <div class="fw-semibold mb-2">
                Please correct the following errors:
            </div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('property-manager.payments.store') }}"
          method="POST"
          id="paymentForm">
        @csrf

        {{-- These values are populated from the selected invoice. --}}
        <input type="hidden" name="tenant_id" id="tenant_id" value="{{ old('tenant_id') }}">
        <input type="hidden" name="lease_id" id="lease_id" value="{{ old('lease_id') }}">
        <input type="hidden" name="unit_id" id="unit_id" value="{{ old('unit_id') }}">

        <div class="row g-4">
            <div class="col-xl-8">
                {{-- Invoice allocation --}}
                <div class="panel p-3 p-lg-4 mb-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="section-icon">
                            <i class="bi bi-receipt"></i>
                        </span>
                        <div>
                            <h2 class="panel-heading mb-1">Invoice allocation</h2>
                            <p class="text-secondary small mb-0">
                                Choose an outstanding invoice to identify the tenant, lease and unit.
                            </p>
                        </div>
                    </div>

                    <div id="allocationRows" class="d-flex flex-column gap-3">
                        <div class="allocation-row" data-allocation-row>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="fw-semibold small text-navy">
                                    Invoice allocation <span data-row-number>1</span>
                                </span>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger d-none"
                                        data-remove-row>
                                    <i class="bi bi-trash3 me-1"></i> Remove
                                </button>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label">Outstanding invoice <span class="text-danger">*</span></label>
                                    <select name="allocations[0][invoice_id]"
                                            class="form-select invoice-select"
                                            required>
                                        <option value="">Select an invoice</option>

                                        @forelse ($invoices as $invoice)
                                            <option
                                                value="{{ $invoice->id }}"
                                                data-tenant="{{ $invoice->tenant_id }}"
                                                data-lease="{{ $invoice->lease_id }}"
                                                data-unit="{{ $invoice->unit_id }}"
                                                data-currency="{{ $invoice->currency }}"
                                                data-balance="{{ $invoice->balance_due }}"
                                                data-number="{{ $invoice->invoice_number }}"
                                                data-type="{{ $invoice->invoice_type }}"
                                                data-tenant-name="{{ $invoice->tenant?->full_name }}"
                                                data-property="{{ $invoice->unit?->building?->property?->name }}"
                                                data-unit-name="{{ $invoice->unit?->unit_number }}"
                                                @selected((string) $selectedInvoiceId === (string) $invoice->id)>
                                                {{ $invoice->invoice_number }}
                                                — {{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}
                                                — {{ $invoice->currency }}
                                                {{ number_format((float) $invoice->balance_due, 2) }}
                                            </option>
                                        @empty
                                        @endforelse
                                    </select>

                                    @error('allocations.0.invoice_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label">Amount allocated <span class="text-danger">*</span></label>
                                    <input type="number"
                                           name="allocations[0][amount]"
                                           class="form-control allocation-amount"
                                           min="0.01"
                                           step="0.01"
                                           placeholder="0.00"
                                           value="{{ old('allocations.0.amount') }}"
                                           required>
                                    @error('allocations.0.amount')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="invoice-details mt-3 small text-secondary" aria-live="polite">
                                Select an invoice to view its details.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addAllocation">
                            <i class="bi bi-plus-circle me-1"></i>
                            Add another invoice
                        </button>

                        <div class="text-end">
                            <div class="text-secondary small">Total allocated</div>
                            <div class="summary-value" id="totalAllocated">0.00</div>
                        </div>
                    </div>

                    <div class="alert alert-light border small text-secondary mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Allocations must belong to the same tenant, lease, unit and currency as the payment.
                        The total allocated cannot exceed the payment amount or the outstanding invoice balances.
                    </div>
                </div>

                {{-- Payment information --}}
                <div class="panel p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="section-icon">
                            <i class="bi bi-wallet2"></i>
                        </span>
                        <div>
                            <h2 class="panel-heading mb-1">Payment information</h2>
                            <p class="text-secondary small mb-0">
                                Enter the amount received and the payment method.
                            </p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="amount" class="form-label">
                                Total payment amount <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   name="amount"
                                   id="amount"
                                   class="form-control"
                                   min="0.01"
                                   step="0.01"
                                   value="{{ old('amount') }}"
                                   placeholder="e.g. 300000"
                                   required>
                            <div class="hint mt-1">
                                Enter the full amount received, not just one allocation.
                            </div>
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="currency" class="form-label">
                                Currency <span class="text-danger">*</span>
                            </label>
                            <select name="currency" id="currency" class="form-select" required>
                                <option value="RWF" @selected(old('currency', 'RWF') === 'RWF')>RWF — Rwandan franc</option>
                                <option value="USD" @selected(old('currency') === 'USD')>USD — US dollar</option>
                            </select>
                            @error('currency')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">
                                Payment method <span class="text-danger">*</span>
                            </label>
                            <select name="payment_method" id="payment_method" class="form-select" required>
                                <option value="">Select method</option>
                                @foreach ($paymentMethods as $value => $label)
                                    <option value="{{ $value }}"
                                        @selected(old('payment_method') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="payment_date" class="form-label">
                                Payment date <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   name="payment_date"
                                   id="payment_date"
                                   class="form-control"
                                   max="{{ now()->toDateString() }}"
                                   value="{{ old('payment_date', now()->toDateString()) }}"
                                   required>
                            @error('payment_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="transaction_reference" class="form-label">
                                Transaction reference
                            </label>
                            <input type="text"
                                   name="transaction_reference"
                                   id="transaction_reference"
                                   class="form-control"
                                   maxlength="255"
                                   value="{{ old('transaction_reference') }}"
                                   placeholder="Bank, mobile money or receipt reference">
                            @error('transaction_reference')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                Payment status <span class="text-danger">*</span>
                            </label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="pending" @selected(old('status', 'pending') === 'pending')>
                                    Pending confirmation
                                </option>
                                <option value="confirmed" @selected(old('status') === 'confirmed')>
                                    Confirmed
                                </option>
                            </select>
                            <div class="hint mt-1">
                                Only confirmed payments should count toward invoice balances.
                            </div>
                            @error('status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea name="notes"
                                      id="notes"
                                      rows="3"
                                      maxlength="2000"
                                      class="form-control"
                                      placeholder="Optional payment details or internal notes">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summary --}}
            <div class="col-xl-4">
                <div class="panel p-3 p-lg-4 position-sticky" style="top: 20px;">
                    <h2 class="panel-heading mb-3">Payment summary</h2>

                    <div class="rounded-3 p-3 mb-3" style="background:#f5f7fb;">
                        <div class="text-secondary small mb-1">Payment amount</div>
                        <div class="summary-value" id="summaryPayment">RWF 0.00</div>
                    </div>

                    <div class="d-flex justify-content-between gap-3 py-3 border-bottom">
                        <span class="text-secondary small">Total allocated</span>
                        <strong id="summaryAllocated">RWF 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between gap-3 py-3 border-bottom">
                        <span class="text-secondary small">Unallocated amount</span>
                        <strong id="summaryUnallocated">RWF 0.00</strong>
                    </div>

                    <div class="d-flex justify-content-between gap-3 py-3">
                        <span class="text-secondary small">Selected invoices</span>
                        <strong id="summaryInvoiceCount">0</strong>
                    </div>

                    <div id="allocationWarning" class="alert alert-warning small d-none">
                        The allocation total does not match the payment amount. Review the amounts before saving.
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-terra py-2" id="submitPayment">
                            <i class="bi bi-check2-circle me-2"></i>
                            Save payment
                        </button>

                        <a href="{{ route('property-manager.payments.index') }}"
                           class="btn btn-light border py-2">
                            Cancel
                        </a>
                    </div>

                    <p class="text-secondary hint mt-3 mb-0">
                        Check the invoice and allocation amounts carefully before saving. Payment confirmation
                        and invoice balances should follow the payment service's transaction rules.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rowsContainer = document.getElementById('allocationRows');
    const addButton = document.getElementById('addAllocation');
    const paymentAmount = document.getElementById('amount');
    const currencySelect = document.getElementById('currency');
    const tenantInput = document.getElementById('tenant_id');
    const leaseInput = document.getElementById('lease_id');
    const unitInput = document.getElementById('unit_id');
    const warning = document.getElementById('allocationWarning');
    const submitButton = document.getElementById('submitPayment');
    const form = document.getElementById('paymentForm');

    const money = value => new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Number(value) || 0);

    function getRows() {
        return [...rowsContainer.querySelectorAll('[data-allocation-row]')];
    }

    function selectedOption(select) {
        return select?.selectedOptions?.[0] || null;
    }

    function selectedInvoice() {
        for (const row of getRows()) {
            const option = selectedOption(row.querySelector('.invoice-select'));
            if (option && option.value) return option;
        }
        return null;
    }

    function updateInvoiceDetails(row) {
        const select = row.querySelector('.invoice-select');
        const option = selectedOption(select);
        const details = row.querySelector('.invoice-details');
        const amountInput = row.querySelector('.allocation-amount');

        if (!option || !option.value) {
            details.textContent = 'Select an invoice to view its details.';
            amountInput.removeAttribute('max');
            return;
        }

        const currency = option.dataset.currency || '';
        const balance = Number(option.dataset.balance || 0);

        details.innerHTML = '';

        const info = document.createElement('div');
        info.className = 'd-flex flex-wrap gap-3';

        [
            ['Tenant', option.dataset.tenantName || '—'],
            ['Property', option.dataset.property || '—'],
            ['Unit', option.dataset.unitName || '—'],
            ['Outstanding', currency + ' ' + money(balance)]
        ].forEach(([label, value]) => {
            const item = document.createElement('span');
            item.textContent = label + ': ' + value;
            info.appendChild(item);
        });

        details.appendChild(info);
        amountInput.max = balance.toFixed(2);

        if (!amountInput.value) {
            amountInput.value = balance > 0 ? balance.toFixed(2) : '';
        }
    }

    function updateCompatibleInvoices() {
        const rows = getRows();
        let anchor = null;

        for (const row of rows) {
            const option = selectedInvoiceForRow(row);
            if (option) {
                anchor = option;
                break;
            }
        }

        rows.forEach(row => {
            const select = row.querySelector('.invoice-select');
            const currentValue = select.value;

            [...select.options].forEach(option => {
                if (!option.value || !anchor || option.value === currentValue) {
                    option.disabled = false;
                    return;
                }

                option.disabled =
                    option.dataset.tenant !== anchor.dataset.tenant ||
                    option.dataset.lease !== anchor.dataset.lease ||
                    option.dataset.unit !== anchor.dataset.unit ||
                    option.dataset.currency !== anchor.dataset.currency;
            });
        });

        if (anchor) {
            tenantInput.value = anchor.dataset.tenant || '';
            leaseInput.value = anchor.dataset.lease || '';
            unitInput.value = anchor.dataset.unit || '';

            if (anchor.dataset.currency) {
                currencySelect.value = anchor.dataset.currency;
            }
        } else {
            tenantInput.value = '';
            leaseInput.value = '';
            unitInput.value = '';
        }
    }

    function selectedInvoiceForRow(row) {
        const option = selectedOption(row.querySelector('.invoice-select'));
        return option && option.value ? option : null;
    }

    function updateSummary() {
        const rows = getRows();
        const allocated = rows.reduce((sum, row) => {
            return sum + (Number(row.querySelector('.allocation-amount').value) || 0);
        }, 0);

        const amount = Number(paymentAmount.value) || 0;
        const currency = currencySelect.value || 'RWF';
        const selectedCount = rows.filter(row => row.querySelector('.invoice-select').value).length;
        const unallocated = amount - allocated;

        document.getElementById('totalAllocated').textContent = currency + ' ' + money(allocated);
        document.getElementById('summaryPayment').textContent = currency + ' ' + money(amount);
        document.getElementById('summaryAllocated').textContent = currency + ' ' + money(allocated);
        document.getElementById('summaryUnallocated').textContent = currency + ' ' + money(unallocated);
        document.getElementById('summaryInvoiceCount').textContent = selectedCount;

        const mismatch = Math.abs(amount - allocated) > 0.009;
        warning.classList.toggle('d-none', !mismatch);
        submitButton.disabled = amount <= 0 || selectedCount === 0 || allocated <= 0 || allocated > amount + 0.009;
    }

    function renumberRows() {
        getRows().forEach((row, index) => {
            row.querySelector('[data-row-number]').textContent = index + 1;

            const removeButton = row.querySelector('[data-remove-row]');
            removeButton.classList.toggle('d-none', index === 0);

            row.querySelector('.invoice-select').name = `allocations[${index}][invoice_id]`;
            row.querySelector('.allocation-amount').name = `allocations[${index}][amount]`;
        });
    }

    function bindRow(row) {
        row.querySelector('.invoice-select').addEventListener('change', function () {
            updateInvoiceDetails(row);
            updateCompatibleInvoices();
            updateSummary();
        });

        row.querySelector('.allocation-amount').addEventListener('input', updateSummary);

        row.querySelector('[data-remove-row]').addEventListener('click', function () {
            row.remove();
            renumberRows();
            updateCompatibleInvoices();
            updateSummary();
        });
    }

    function addRow() {
        const firstRow = rowsContainer.querySelector('[data-allocation-row]');
        const row = firstRow.cloneNode(true);

        row.querySelector('.invoice-select').value = '';
        row.querySelector('.allocation-amount').value = '';
        row.querySelector('.allocation-amount').removeAttribute('max');
        row.querySelector('.invoice-details').textContent = 'Select an invoice to view its details.';

        rowsContainer.appendChild(row);
        bindRow(row);
        renumberRows();
        updateCompatibleInvoices();
        updateSummary();
    }

    addButton.addEventListener('click', addRow);
    paymentAmount.addEventListener('input', updateSummary);

    currencySelect.addEventListener('change', function () {
        const anchor = selectedInvoice();
        if (anchor && anchor.dataset.currency !== currencySelect.value) {
            currencySelect.value = anchor.dataset.currency;
        }
        updateSummary();
    });

    getRows().forEach(row => {
        bindRow(row);
        updateInvoiceDetails(row);
    });

    updateCompatibleInvoices();
    updateSummary();

    form.addEventListener('submit', function (event) {
        const amount = Number(paymentAmount.value) || 0;
        const allocated = getRows().reduce((sum, row) => {
            return sum + (Number(row.querySelector('.allocation-amount').value) || 0);
        }, 0);

        if (amount <= 0 || allocated <= 0 || allocated > amount + 0.009) {
            event.preventDefault();
            warning.classList.remove('d-none');
            warning.textContent = 'Enter a valid payment amount and ensure allocations do not exceed it.';
            return;
        }

        for (const row of getRows()) {
            const select = row.querySelector('.invoice-select');
            const allocation = Number(row.querySelector('.allocation-amount').value) || 0;
            const option = selectedOption(select);

            if (!option || !option.value || allocation <= 0) {
                event.preventDefault();
                alert('Select an invoice and enter a valid allocation amount for each allocation row.');
                return;
            }

            if (allocation > Number(option.dataset.balance || 0) + 0.009) {
                event.preventDefault();
                alert('An allocation cannot exceed the outstanding balance of its invoice.');
                return;
            }
        }
    });
});
</script>
@endsection
