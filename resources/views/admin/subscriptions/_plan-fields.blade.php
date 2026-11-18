<div class="col-12">
    <label class="form-label small mb-1" for="{{ $prefix }}-name">Name</label>
    <input id="{{ $prefix }}-name" name="name" required maxlength="100" class="form-control form-control-sm" value="{{ $plan->name }}">
</div>
<div class="col-6">
    <label class="form-label small mb-1" for="{{ $prefix }}-price">Price</label>
    <input id="{{ $prefix }}-price" type="number" step="0.01" min="0" name="price" required class="form-control form-control-sm" value="{{ $plan->price }}">
</div>
<div class="col-6">
    <label class="form-label small mb-1" for="{{ $prefix }}-cur">Currency</label>
    <select id="{{ $prefix }}-cur" name="currency" class="form-select form-select-sm">
        @foreach(['RWF', 'USD', 'EUR', 'KES', 'UGX', 'TZS'] as $c)<option @selected($plan->currency === $c)>{{ $c }}</option>@endforeach
    </select>
</div>
<div class="col-12">
    <label class="form-label small mb-1" for="{{ $prefix }}-cycle">Billing cycle</label>
    <select id="{{ $prefix }}-cycle" name="billing_cycle" class="form-select form-select-sm">
        @foreach(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annual' => 'Annual'] as $k => $l)<option value="{{ $k }}" @selected($plan->billing_cycle === $k)>{{ $l }}</option>@endforeach
    </select>
</div>
<div class="col-6">
    <label class="form-label small mb-1" for="{{ $prefix }}-mp">Max properties</label>
    <input id="{{ $prefix }}-mp" type="number" min="1" name="max_properties" class="form-control form-control-sm" value="{{ $plan->max_properties }}" placeholder="Unlimited">
</div>
<div class="col-6">
    <label class="form-label small mb-1" for="{{ $prefix }}-mu">Max units</label>
    <input id="{{ $prefix }}-mu" type="number" min="1" name="max_units" class="form-control form-control-sm" value="{{ $plan->max_units }}" placeholder="Unlimited">
</div>
<div class="col-12">
    <label class="form-label small mb-1" for="{{ $prefix }}-desc">Description</label>
    <textarea id="{{ $prefix }}-desc" name="description" rows="2" maxlength="1000" class="form-control form-control-sm">{{ $plan->description }}</textarea>
</div>
<div class="col-12">
    <input type="hidden" name="code" value="{{ $plan->code }}">
    <div class="form-check form-switch">
        <input type="hidden" name="is_active" value="0">
        <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="{{ $prefix }}-active" @checked($plan->is_active)>
        <label class="form-check-label small" for="{{ $prefix }}-active">Offered to new subscribers</label>
    </div>
</div>
