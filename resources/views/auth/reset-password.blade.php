<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#19265d">
    <title>Choose a new password | Terra Smart Rental Management</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --orange: #D05208;
            --orange-dark: #ad4306;
            --orange-soft: #fff1e8;
            --navy: #19265d;
            --ink: #20263a;
            --muted: #687086;
            --line: #e5e8ef;
            --ground: #f6f7fb;
            --font: 'Montserrat', system-ui, -apple-system, 'Segoe UI', sans-serif;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            font-family: var(--font);
            font-size: .92rem;
            line-height: 1.6;
            color: var(--ink);
            /* background: radial-gradient(ellipse at 50% -10%, #ffe9db 0, transparent 55%), var(--ground); */
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: var(--orange);
            font-weight: 700;
            text-decoration: none;
        }

        a:hover {
            color: var(--orange-dark);
            text-decoration: underline;
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible {
            outline: 3px solid rgba(208, 82, 8, .35);
            outline-offset: 2px;
        }

        .wrap {
            width: 100%;
            max-width: 420px;
            animation: rise .5s ease both;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        .brand {
            display: block;
            text-align: center;
            margin-bottom: 22px;
            line-height: 1.1;
        }

        .brand:hover {
            text-decoration: none;
        }

        .brand b {
            display: block;
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -.06em;
            color: var(--orange);
        }

        .brand small {
            display: block;
            margin-top: 4px;
            font-size: .6rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--navy);
        }

        .card-box {
            background: #fff;
            border: 1px solid var(--line);
            border-top: 4px solid var(--orange);
            border-radius: 16px;
            padding: 32px 30px;
            box-shadow: 0 16px 40px rgba(25, 38, 93, .08);
        }

        .badge-ico {
            width: 48px;
            height: 48px;
            margin-bottom: 14px;
            border-radius: 14px;
            background: var(--orange-soft);
            color: var(--orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        h1 {
            margin: 0 0 4px;
            color: var(--navy);
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -.04em;
        }

        .sub {
            margin: 0 0 24px;
            color: var(--muted);
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            color: var(--navy);
            font-size: .78rem;
            font-weight: 700;
        }

        .input {
            position: relative;
        }

        .input input {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            font-family: var(--font);
            font-size: .9rem;
            color: var(--ink);
            background: #fff;
            border: 1.5px solid #d9deea;
            border-radius: 10px;
            transition: border-color .2s, box-shadow .2s;
        }

        .input input:hover {
            border-color: #c2c9da;
        }

        .input input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(208, 82, 8, .12);
            outline: none;
        }

        .input input.is-invalid {
            border-color: #dc3545;
        }

        .input input.pad-r {
            padding-right: 44px;
        }

        .input input.match-ok {
            border-color: #12b76a;
        }

        .eye {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #8b93ac;
            cursor: pointer;
        }

        .eye:hover {
            color: var(--orange);
            background: var(--orange-soft);
        }

        .err {
            display: block;
            margin-top: 5px;
            color: #dc3545;
            font-size: .78rem;
            font-weight: 600;
        }

        .meter {
            display: flex;
            gap: 5px;
            margin-top: 9px;
        }

        .meter i {
            flex: 1;
            height: 4px;
            border-radius: 99px;
            background: var(--line);
            transition: background .25s;
        }

        .meter[data-level="1"] i:nth-child(1) {
            background: #dc3545;
        }

        .meter[data-level="2"] i:nth-child(-n+2) {
            background: #f59e0b;
        }

        .meter[data-level="3"] i:nth-child(-n+3) {
            background: #84cc16;
        }

        .meter[data-level="4"] i {
            background: #12b76a;
        }

        .note {
            margin-top: 5px;
            font-size: .75rem;
            font-weight: 600;
            color: var(--muted);
            min-height: 1.1em;
        }

        .note.ok {
            color: #0f8a4f;
        }

        .note.bad {
            color: #dc3545;
        }

        .btn-main {
            width: 100%;
            height: 48px;
            margin-top: 6px;
            border: 0;
            border-radius: 10px;
            background: var(--orange);
            color: #fff;
            font-family: var(--font);
            font-size: .92rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .2s, box-shadow .2s;
        }

        .btn-main:hover {
            background: var(--orange-dark);
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(208, 82, 8, .25);
        }

        .btn-main:disabled {
            opacity: .8;
            cursor: wait;
            transform: none;
            box-shadow: none;
        }

        .foot {
            margin: 20px 0 0;
            text-align: center;
            font-size: .86rem;
        }

        .legal {
            margin: 18px 0 0;
            text-align: center;
            font-size: .74rem;
            color: #9aa2b8;
        }

        @media (max-width: 480px) {
            .card-box {
                padding: 26px 20px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .wrap {
                animation: none;
            }

            * {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="wrap">
        <a href="{{ url('/') }}" class="brand" aria-label="Terra home">
            <b>Terra</b><small>Smart Rental Management</small>
        </a>

        <div class="card-box">
            <div class="badge-ico"><i class="bi bi-shield-lock"></i></div>
            <h1>Choose a new password</h1>
            <p class="sub">Enter and confirm your new password below.</p>

            <form method="POST" action="{{ route('password.store') }}" id="authForm">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="field">
                    <label for="email">Email address</label>
                    <div class="input">
                        <input id="email" name="email" type="email" class="@error('email') is-invalid @enderror"
                            value="{{ old('email', $email) }}" autocomplete="username" required>
                    </div>
                    @error('email') <span class="err">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="password">New password</label>
                    <div class="input">
                        <input id="password" name="password" type="password" class="pad-r @error('password') is-invalid @enderror"
                            autocomplete="new-password" required autofocus>
                        <button type="button" class="eye" data-eye="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="meter" id="meter" data-level="0" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                    <div class="note" id="strengthNote" aria-live="polite">Use 8 or more characters</div>
                    @error('password') <span class="err">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm new password</label>
                    <div class="input">
                        <input id="password_confirmation" name="password_confirmation" type="password" class="pad-r"
                            autocomplete="new-password" required>
                        <button type="button" class="eye" data-eye="password_confirmation" aria-label="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                    <div class="note" id="matchNote" aria-live="polite"></div>
                </div>

                <button type="submit" class="btn-main">Reset password</button>
            </form>

            <p class="foot"><a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Back to sign in</a></p>
        </div>

        <p class="legal">&copy; {{ date('Y') }} Terra Smart Rental Management &middot; <a href="{{ url('/') }}" style="font-weight:600">Back to website</a></p>
    </div>

    <script>
        // Show / hide password
        document.querySelectorAll('[data-eye]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var input = document.getElementById(btn.dataset.eye);
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });

        // Strength meter and match check
        var pw = document.getElementById('password');
        var confirmPw = document.getElementById('password_confirmation');
        var meter = document.getElementById('meter');
        var strengthNote = document.getElementById('strengthNote');
        var matchNote = document.getElementById('matchNote');
        var words = ['Use 8 or more characters', 'Weak', 'Fair', 'Good', 'Strong'];

        pw.addEventListener('input', function() {
            var v = pw.value,
                s = 0;
            if (v.length >= 8) s++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
            if (/\d/.test(v)) s++;
            if (/[^A-Za-z0-9]/.test(v) || v.length >= 14) s++;
            if (!v) s = 0;
            meter.dataset.level = s;
            strengthNote.textContent = v ? 'Strength: ' + words[s] : words[0];
            checkMatch();
        });

        function checkMatch() {
            if (!confirmPw.value) {
                matchNote.textContent = '';
                matchNote.className = 'note';
                confirmPw.classList.remove('match-ok');
                return;
            }
            var ok = confirmPw.value === pw.value;
            matchNote.textContent = ok ? 'Passwords match' : 'Passwords do not match yet';
            matchNote.className = 'note ' + (ok ? 'ok' : 'bad');
            confirmPw.classList.toggle('match-ok', ok);
        }
        confirmPw.addEventListener('input', checkMatch);

        // Prevent double submit
        document.getElementById('authForm').addEventListener('submit', function(e) {
            if (!e.target.checkValidity()) return;
            var b = e.target.querySelector('.btn-main');
            b.disabled = true;
            b.textContent = 'Please wait…';
        });
    </script>
</body>

</html>