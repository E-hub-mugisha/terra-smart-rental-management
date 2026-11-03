<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#19265d">
    <title>Forgot password | Terra Smart Rental Management</title>

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

        .err {
            display: block;
            margin-top: 5px;
            color: #dc3545;
            font-size: .78rem;
            font-weight: 600;
        }

        .btn-main {
            width: 100%;
            height: 48px;
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

        .alert-ok {
            margin-bottom: 18px;
            padding: 11px 14px;
            border-radius: 10px;
            background: #e6f7ee;
            border: 1px solid #bfe8d1;
            color: #0b6b3c;
            font-size: .84rem;
            font-weight: 600;
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
            <div class="badge-ico"><i class="bi bi-key"></i></div>
            <h1>Reset your password</h1>
            <p class="sub">Enter your email address to request a reset link.</p>

            @if(session('status'))
            <div class="alert-ok" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" id="authForm">
                @csrf

                <div class="field">
                    <label for="email">Email address</label>
                    <div class="input">
                        <input id="email" name="email" type="email" class="@error('email') is-invalid @enderror"
                            value="{{ old('email') }}" autocomplete="email" required autofocus>
                    </div>
                    @error('email') <span class="err">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn-main">Send reset link</button>
            </form>

            <p class="foot"><a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Back to sign in</a></p>
        </div>

        <p class="legal">&copy; {{ date('Y') }} Terra Smart Rental Management &middot; <a href="{{ url('/') }}" style="font-weight:600">Back to website</a></p>
    </div>

    <script>
        // Prevent double submit
        document.getElementById('authForm').addEventListener('submit', function(e) {
            if (!e.target.checkValidity()) return;
            var b = e.target.querySelector('.btn-main');
            b.disabled = true;
            b.textContent = 'Sending…';
        });
    </script>
</body>

</html>