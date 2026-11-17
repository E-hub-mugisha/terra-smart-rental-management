@php
    $p = $provider;
    $selected = old('categories', $p?->categories ?? []);
    $uid = $p?->id ?? 'new';
@endphp
<form method="POST" action="{{ $p ? route('service-providers.update', $p) : route('service-providers.store') }}" class="d-grid gap-2">
    @csrf @if($p) @method('PUT') @endif
    <input type="text" name="company_name" class="form-control form-control-sm" placeholder="Company / trade name" required maxlength="150" value="{{ old('company_name', $p?->company_name) }}" aria-label="Company name">
    <input type="text" name="contact_name" class="form-control form-control-sm" placeholder="Contact person" maxlength="150" value="{{ old('contact_name', $p?->contact_name) }}" aria-label="Contact person">
    <div class="d-flex gap-2">
        <input type="tel" name="phone" class="form-control form-control-sm" placeholder="Phone" required maxlength="30" value="{{ old('phone', $p?->phone) }}" aria-label="Phone">
        <input type="text" name="district" class="form-control form-control-sm" placeholder="District" maxlength="100" value="{{ old('district', $p?->district) }}" aria-label="District">
    </div>
    <input type="email" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror" placeholder="Email (needed for a login)" maxlength="150" value="{{ old('email', $p?->email) }}" aria-label="Email">
    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    <div class="d-flex flex-wrap gap-1">
        @foreach(\App\Models\ServiceProvider::CATEGORIES as $k => [$label, $icon])
            <input type="checkbox" class="btn-check" name="categories[]" value="{{ $k }}" id="cat{{ $uid }}{{ $k }}" @checked(in_array($k, (array) $selected, true))>
            <label class="btn btn-sm btn-outline-secondary rounded-pill" for="cat{{ $uid }}{{ $k }}"><i class="bi {{ $icon }}"></i> {{ $label }}</label>
        @endforeach
    </div>
    @error('categories')<div class="small text-danger">{{ $message }}</div>@enderror
    <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Notes (rates, availability…)" maxlength="2000" aria-label="Notes">{{ old('notes', $p?->notes) }}</textarea>
    @unless($p?->user_id)
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="create_login" value="1" id="login{{ $uid }}">
            <label class="form-check-label small" for="login{{ $uid }}">Give them a technician login to receive jobs (we email a password-setup link)</label>
        </div>
    @endunless
    <button class="btn btn-sm rounded-pill text-white" style="background:var(--terra-orange);">{{ $p ? 'Save changes' : 'Add provider' }}</button>
</form>
