@php
    $isEdit = isset($expense);
@endphp

<div class="row g-4">

    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Expense Information</h6>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Property <span class="text-danger">*</span>
                        </label>
                        <select name="property_id"
                                class="form-select @error('property_id') is-invalid @enderror"
                                required>
                            <option value="">Select property</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}"
                                    @selected(old('property_id', $expense->property_id ?? request('property_id')) == $property->id)>
                                    {{ $property->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('property_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Expense Title <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $expense->title ?? '') }}"
                               placeholder="e.g. Water tank repair"
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Category <span class="text-danger">*</span>
                        </label>
                        <select name="category"
                                class="form-select @error('category') is-invalid @enderror"
                                required>
                            <option value="">Select category</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}"
                                    @selected(old('category', $expense->category ?? '') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description"
                                  rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Describe what the expense was for...">{{ old('description', $expense->description ?? '') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Amount <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                               name="amount"
                               step="0.01"
                               min="0.01"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount', $expense->amount ?? '') }}"
                               placeholder="0.00"
                               required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Currency <span class="text-danger">*</span>
                        </label>
                        <select name="currency"
                                class="form-select @error('currency') is-invalid @enderror"
                                required>
                            <option value="RWF" @selected(old('currency', $expense->currency ?? 'RWF') === 'RWF')>RWF</option>
                            <option value="USD" @selected(old('currency', $expense->currency ?? '') === 'USD')>USD</option>
                        </select>
                        @error('currency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Expense Date <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               name="expense_date"
                               max="{{ now()->toDateString() }}"
                               class="form-control @error('expense_date') is-invalid @enderror"
                               value="{{ old('expense_date', isset($expense) ? $expense->expense_date?->format('Y-m-d') : now()->toDateString()) }}"
                               required>
                        @error('expense_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <select name="payment_method"
                                class="form-select @error('payment_method') is-invalid @enderror">
                            <option value="">Select payment method</option>
                            @foreach([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'mobile_money' => 'Mobile Money',
                                'card' => 'Card',
                                'other' => 'Other',
                            ] as $key => $label)
                                <option value="{{ $key }}"
                                    @selected(old('payment_method', $expense->payment_method ?? '') === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Vendor and Reference Information</h6>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Vendor / Supplier</label>
                        <input type="text"
                               name="vendor_name"
                               class="form-control @error('vendor_name') is-invalid @enderror"
                               value="{{ old('vendor_name', $expense->vendor_name ?? '') }}"
                               placeholder="Vendor or supplier name">
                        @error('vendor_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Reference Number</label>
                        <input type="text"
                               name="reference_number"
                               class="form-control @error('reference_number') is-invalid @enderror"
                               value="{{ old('reference_number', $expense->reference_number ?? '') }}"
                               placeholder="Receipt or transaction reference">
                        @error('reference_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Additional Notes</label>
                        <textarea name="notes"
                                  rows="3"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="Additional information...">{{ old('notes', $expense->notes ?? '') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Payment Status</h6>
            </div>

            <div class="card-body">
                <label class="form-label fw-semibold">
                    Status <span class="text-danger">*</span>
                </label>

                <select name="status"
                        class="form-select @error('status') is-invalid @enderror"
                        required>
                    <option value="paid"
                        @selected(old('status', $expense->status ?? 'paid') === 'paid')>
                        Paid
                    </option>

                    <option value="pending"
                        @selected(old('status', $expense->status ?? '') === 'pending')>
                        Pending
                    </option>

                    <option value="cancelled"
                        @selected(old('status', $expense->status ?? '') === 'cancelled')>
                        Cancelled
                    </option>
                </select>

                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <div class="alert alert-light border small mt-3 mb-0">
                    <strong>Paid:</strong> The expense has been settled.
                    <br>
                    <strong>Pending:</strong> Payment is still outstanding.
                    <br>
                    <strong>Cancelled:</strong> The expense was cancelled.
                </div>
            </div>
        </div>

        <div class="d-grid gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>
                {{ $isEdit ? 'Save Changes' : 'Record Expense' }}
            </button>

            <a href="{{ route('property-manager.expenses.index') }}"
               class="btn btn-light border">
                Cancel
            </a>
        </div>
    </div>

</div>