{{-- Language switcher (brief §23). $compact: smaller select for navbars. --}}
@php $current = app()->getLocale(); $locales = \App\Models\User::LOCALES; @endphp
<form method="POST" action="{{ route('locale.update') }}" class="m-0 d-inline-flex align-items-center" aria-label="{{ __('Language') }}">
    @csrf
    <label class="visually-hidden" for="locale-{{ $compact ?? false ? 'c' : 'f' }}">{{ __('Language') }}</label>
    <i class="bi bi-translate me-1 text-secondary" aria-hidden="true"></i>
    <select name="locale" id="locale-{{ $compact ?? false ? 'c' : 'f' }}" onchange="this.form.submit()"
            class="form-select form-select-sm border-0 bg-transparent fw-semibold" style="width:auto;padding-right:1.6rem;">
        @foreach($locales as $code => $label)
            <option value="{{ $code }}" @selected($current === $code)>{{ ($compact ?? false) ? strtoupper($code) : $label }}</option>
        @endforeach
    </select>
    <noscript><button class="btn btn-sm btn-light">{{ __('Save') }}</button></noscript>
</form>
