
@php
    $isEditing = isset($expense) && $expense->exists;

    $current = fn ($field, $default = '') => old(
        $field,
        $isEditing ? ($expense->{$field} ?? $default) : $default
    );
@endphp

@if($errors->any())
    <div class="alert alert-danger rounded-3 mb-4">
        <div class="fw-bold mb-2">
            <i class="bi bi-exclamation-circle me-2"></i>
            Please correct the following errors.
        </div>
        <ul class="mb-0 ps-4">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    {{-- Property details --}}
    <div class="col-12">
        <div class="expense-form-section">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="expense-form-icon"><i class="bi bi-buildings"></i></span>
                <div>
                    <h2 class="h6 fw-bold mb-1">Property and expense details</h2>
                    <p class="small text-muted mb-0">Select the property and describe the expense.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="property_id" class="form-label">
                        Property <span class="text-danger">*</span>
                    </label>
                    <select name="property_id" id="property_id"
                            class="form-select @error('property_id') is-invalid @enderror" required>
                        <option value="">Select a property</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}"
                                @selected((string) $current('property_id') === (string) $property->id)>
                                {{ $property->name }}
                                @if($property->property_code)
                                    — {{ $property->property_code }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('property_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if($properties->isEmpty())
                        <div class="form-text text-danger">
                            No properties are available. Create a property before recording an expense.
                        </div>
                    @endif
                </div>

                <div class="col-12 col-md-6">
                    <label for="category" class="form-label">
                        Category <span class="text-danger">*</span>
                    </label>
                    <select name="category" id="category"
                            class="form-select @error('category') is-invalid @enderror" required>
                        <option value="">Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}"
                                @selected($current('category') === $category)>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="title" class="form-label">
                        Expense title <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="title" id="title"
                           class="form-control @error('title') is-invalid @enderror"
                           value="{{ $current('title') }}"
                           maxlength="255"
                           placeholder="e.g. Electrical repairs in apartment building"
                           required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Add details about the expense...">{{ $current('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Amount and payment --}}
    <div class="col-12">
        <div class="expense-form-section">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="expense-form-icon"><i class="bi bi-wallet2"></i></span>
                <div>
                    <h2 class="h6 fw-bold mb-1">Amount and payment</h2>
                    <p class="small text-muted mb-0">Record the amount, date and payment information.</p>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label for="amount" class="form-label">
                        Amount <span class="text-danger">*</span>
                    </label>
                    <input type="number" name="amount" id="amount"
                           class="form-control @error('amount') is-invalid @enderror"
                           value="{{ $current('amount') }}"
                           min="0.01" max="9999999999999" step="0.01"
                           placeholder="0.00" required>
                    @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label for="currency" class="form-label">
                        Currency <span class="text-danger">*</span>
                    </label>
                    <select name="currency" id="currency"
                            class="form-select @error('currency') is-invalid @enderror" required>
                        <option value="RWF" @selected($current('currency', 'RWF') === 'RWF')>RWF — Rwanda Franc</option>
                        <option value="USD" @selected($current('currency', 'RWF') === 'USD')>USD — US Dollar</option>
                    </select>
                    @error('currency')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label for="expense_date" class="form-label">
                        Expense date <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="expense_date" id="expense_date"
                           class="form-control @error('expense_date') is-invalid @enderror"
                           value="{{ $current('expense_date', now()->toDateString()) instanceof \Illuminate\Support\Carbon ? $current('expense_date', now()->toDateString())->format('Y-m-d') : substr((string) $current('expense_date', now()->toDateString()), 0, 10) }}"
                           required>
                    @error('expense_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label for="payment_method" class="form-label">Payment method</label>
                    <select name="payment_method" id="payment_method"
                            class="form-select @error('payment_method') is-invalid @enderror">
                        <option value="">Select payment method</option>
                        @foreach(['Cash', 'Bank Transfer', 'Mobile Money', 'Card', 'Other'] as $method)
                            <option value="{{ $method }}"
                                @selected($current('payment_method') === $method)>
                                {{ $method }}
                            </option>
                        @endforeach
                    </select>
                    @error('payment_method')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label for="status" class="form-label">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select name="status" id="status"
                            class="form-select @error('status') is-invalid @enderror" required>
                        <option value="paid" @selected($current('status', 'paid') === 'paid')>Paid</option>
                        <option value="pending" @selected($current('status', 'paid') === 'pending')>Pending</option>
                        <option value="cancelled" @selected($current('status', 'paid') === 'cancelled')>Cancelled</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label for="vendor_name" class="form-label">Vendor / supplier</label>
                    <input type="text" name="vendor_name" id="vendor_name"
                           class="form-control @error('vendor_name') is-invalid @enderror"
                           value="{{ $current('vendor_name') }}"
                           maxlength="255"
                           placeholder="Supplier or service provider">
                    @error('vendor_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label for="reference_number" class="form-label">Payment reference</label>
                    <input type="text" name="reference_number" id="reference_number"
                           class="form-control @error('reference_number') is-invalid @enderror"
                           value="{{ $current('reference_number') }}"
                           maxlength="255"
                           placeholder="Bank transaction ID, receipt number, etc.">
                    @error('reference_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Additional notes --}}
    <div class="col-12">
        <div class="expense-form-section">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="expense-form-icon"><i class="bi bi-journal-text"></i></span>
                <div>
                    <h2 class="h6 fw-bold mb-1">Additional notes</h2>
                    <p class="small text-muted mb-0">Add any internal comments or supporting information.</p>
                </div>
            </div>

            <label for="notes" class="form-label">Notes</label>
            <textarea name="notes" id="notes" rows="3"
                      class="form-control @error('notes') is-invalid @enderror"
                      placeholder="Internal notes...">{{ $current('notes') }}</textarea>
            @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
    <a href="{{ route('admin.property-expenses.index') }}" class="btn btn-light border px-4">
        <i class="bi bi-arrow-left me-1"></i>Cancel
    </a>

    <button type="submit" class="btn expense-form-submit px-4" @disabled($properties->isEmpty())>
        <i class="bi bi-check2-circle me-1"></i>
        {{ $isEditing ? 'Save changes' : 'Record expense' }}
    </button>
</div>
