<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('Enter your sign-in code') }} | Terra</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.fonts')
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px; background: #f4f6fb; }
        .box { width: 100%; max-width: 420px; background: #fff; border-radius: 18px; padding: 32px; box-shadow: 0 18px 50px rgba(25,38,93,.12); }
        .brand { font-size: 1.6rem; font-weight: 800; color: #19265d; letter-spacing: .02em; }
        .code { font-size: 1.8rem; letter-spacing: .5em; text-align: center; font-variant-numeric: tabular-nums; }
        .btn-terra { background: #D05208; border-color: #D05208; color: #fff; }
        .btn-terra:hover { background: #ac4205; color: #fff; }
    </style>
</head>
<body>
<main class="box">
    <div class="brand mb-3">TERRA</div>
    <h1 class="h4 fw-bold" style="color:#19265d;">{{ __('Check your email') }}</h1>
    <p class="text-secondary">{{ __('We sent a 6-digit code to') }} <strong>{{ $email }}</strong>. {{ __('It expires in 10 minutes.') }}</p>

    @if(session('status'))<div class="alert alert-success py-2 small">{{ session('status') }}</div>@endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf
        <label class="visually-hidden" for="code">{{ __('Sign-in code') }}</label>
        <input type="text" name="code" id="code" class="form-control form-control-lg code @error('code') is-invalid @enderror"
               inputmode="numeric" autocomplete="one-time-code" maxlength="7" pattern="[0-9 ]*" required autofocus placeholder="••••••">
        @error('code')<div class="invalid-feedback text-center">{{ $message }}</div>@enderror
        <button class="btn btn-terra btn-lg w-100 rounded-pill mt-3 fw-semibold">{{ __('Verify and sign in') }}</button>
    </form>

    <form method="POST" action="{{ route('two-factor.resend') }}" class="text-center mt-3">
        @csrf
        <button class="btn btn-link btn-sm">{{ __('Send a new code') }}</button>
        · <a href="{{ route('login') }}" class="small">{{ __('Back to sign in') }}</a>
    </form>
</main>
</body>
</html>
