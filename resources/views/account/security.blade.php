@extends('layouts.app')

@section('title', __('Security & language'))
@section('page_heading', __('Security & language'))
@section('page_subtitle', __('Protect your account and choose the language Terra uses for you.'))

@section('content')
<div class="row g-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
            <div class="card-body">
                <h2 class="h6 fw-bold" style="color:var(--terra-navy);"><i class="bi bi-translate me-1"></i> {{ __('Language') }}</h2>
                <p class="small text-secondary">{{ __('Used for menus, emails and notifications where a translation is available.') }}</p>
                <form method="POST" action="{{ route('locale.update') }}" class="d-flex gap-2">
                    @csrf
                    <select name="locale" class="form-select" aria-label="{{ __('Language') }}">
                        @foreach(\App\Models\User::LOCALES as $code => $label)<option value="{{ $code }}" @selected($user->locale === $code)>{{ $label }}</option>@endforeach
                    </select>
                    <button class="btn btn-dark rounded-pill px-4">{{ __('Save') }}</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="h6 fw-bold mb-0" style="color:var(--terra-navy);"><i class="bi bi-shield-lock me-1"></i> {{ __('Two-step sign-in') }}</h2>
                    <span class="badge rounded-pill {{ $user->two_factor_enabled ? 'text-bg-success' : 'text-bg-light border' }}">{{ $user->two_factor_enabled ? __('On') : __('Off') }}</span>
                </div>
                <p class="small text-secondary mt-2">{{ __('After your password, we email you a 6-digit code. Recommended for administrators, owners, managers and accountants.') }}</p>
                @error('password')<div class="alert alert-danger small py-2">{{ $message }}</div>@enderror
                <form method="POST" action="{{ route('account.security.two-factor') }}" class="d-flex flex-wrap gap-2">
                    @csrf @method('PUT')
                    <input type="hidden" name="enable" value="{{ $user->two_factor_enabled ? 0 : 1 }}">
                    <input type="password" name="password" class="form-control" style="max-width:240px" placeholder="{{ __('Current password') }}" required autocomplete="current-password" aria-label="{{ __('Current password') }}">
                    <button class="btn rounded-pill px-4 {{ $user->two_factor_enabled ? 'btn-outline-danger' : 'btn-success' }}">
                        {{ $user->two_factor_enabled ? __('Turn off') : __('Turn on') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border-0 shadow-sm" style="border-radius:14px;">
            <div class="card-body">
                <h2 class="h6 fw-bold" style="color:var(--terra-navy);"><i class="bi bi-key me-1"></i> {{ __('API tokens') }}</h2>
                <p class="small text-secondary">{{ __('For the Terra mobile app or an approved integration.') }} <a href="{{ route('api.docs') }}" target="_blank">{{ __('API documentation') }}</a></p>

                @if($newToken)
                    <div class="alert alert-warning small">
                        <strong>{{ __('Copy this token now — it will not be shown again:') }}</strong>
                        <code class="d-block mt-1 p-2 bg-white border rounded" style="word-break:break-all;" id="newToken">{{ $newToken }}</code>
                        <button type="button" class="btn btn-sm btn-outline-dark mt-2" onclick="navigator.clipboard.writeText(document.getElementById('newToken').textContent); this.textContent='✓'">{{ __('Copy') }}</button>
                    </div>
                @endif

                <form method="POST" action="{{ route('account.security.tokens.store') }}" class="d-flex flex-wrap gap-2 mb-3">
                    @csrf
                    <input type="text" name="name" class="form-control" style="max-width:200px" maxlength="100" placeholder="{{ __('Token name, e.g. My phone') }}" required aria-label="{{ __('Token name') }}">
                    <input type="password" name="password" class="form-control" style="max-width:180px" placeholder="{{ __('Current password') }}" required aria-label="{{ __('Current password') }}">
                    <button class="btn btn-dark rounded-pill">{{ __('Create token') }}</button>
                </form>

                @forelse($tokens as $t)
                    <div class="d-flex justify-content-between align-items-center border-top py-2 small">
                        <span><strong>{{ $t->name }}</strong><br><span class="text-secondary">{{ __('Created') }} {{ $t->created_at->format('d M Y') }} · {{ $t->last_used_at ? __('last used') . ' ' . $t->last_used_at->diffForHumans() : __('never used') }}{{ $t->expires_at ? ' · ' . __('expires') . ' ' . $t->expires_at->format('d M Y') : '' }}</span></span>
                        <form method="POST" action="{{ route('account.security.tokens.destroy', $t->id) }}" onsubmit="return confirm('{{ __('Revoke this token?') }}')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger rounded-pill">{{ __('Revoke') }}</button></form>
                    </div>
                @empty
                    <p class="small text-secondary mb-0">{{ __('No API tokens.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
