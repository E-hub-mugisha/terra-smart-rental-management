<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Terra Smart Rental Management | Find and Manage Rentals')</title>

    <meta name="description"
        content="@yield('meta_description', 'Terra Smart Rental Management helps you find available rental homes, apply online, and manage leases, rent payments and maintenance in one place.')">

    <meta name="robots" content="@yield('meta_robots', 'index, follow')">
    <meta name="author" content="Terra Smart Rental Management">
    <meta name="theme-color" content="#19265d">

    <link rel="canonical" href="@yield('canonical_url', url()->current())">

    {{-- Open Graph --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="Terra Smart Rental Management">
    <meta property="og:title" content="@yield('og_title', 'Terra Smart Rental Management | Find and Manage Rentals')">
    <meta property="og:description" content="@yield('og_description', 'Find available rental homes, apply online and manage your tenancy with Terra Smart Rental Management.')">
    <meta property="og:url" content="@yield('og_url', url()->current())">

    @hasSection('og_image')
    <meta property="og:image" content="@yield('og_image')">
    <meta property="og:image:alt" content="@yield('og_image_alt', 'Terra Smart Rental Management')">
    @else
    <meta property="og:image" content="{{ asset('images/terra-social.jpg') }}">
    <meta property="og:image:alt" content="Terra Smart Rental Management">
    @endif

    {{-- X / Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', 'Terra Smart Rental Management | Find and Manage Rentals')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Find available rental homes, apply online and manage your tenancy with Terra Smart Rental Management.')">

    @hasSection('og_image')
    <meta name="twitter:image" content="@yield('og_image')">
    @else
    <meta name="twitter:image" content="{{ asset('images/terra-social.jpg') }}">
    @endif

    {{-- Bootstrap 5 + Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.fonts')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    {{-- AOS (animate on scroll) --}}
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">


    <style>
        :root {
            --tr-orange: #D05208;
            --tr-orange-dark: #ad4306;
            --tr-orange-soft: #fff1e8;
            --tr-navy: #19265d;
            --tr-navy-deep: #111b48;
            --tr-navy-soft: #eef1fb;
            --tr-ink: #20263a;
            --tr-muted: #687086;
            --tr-line: #e5e8ef;
            --tr-surface: #ffffff;
            --tr-ground: #f6f7fb;
            --tr-font: var(--terra-font);

            --tr-shadow-sm: 0 2px 10px rgba(25, 38, 93, .06);
            --tr-shadow-md: 0 12px 32px rgba(25, 38, 93, .10);
            --tr-shadow-lg: 0 24px 60px rgba(25, 38, 93, .16);
            --tr-ease: cubic-bezier(.22, .8, .24, 1);

            /* Backwards-compatible aliases for older pages */
            --tr-forest: var(--tr-orange);
            --tr-forest-dark: var(--tr-orange-dark);
            --tr-forest-tint: var(--tr-orange-soft);

            --bs-body-font-family: var(--tr-font);
            --bs-body-color: var(--tr-ink);
            --bs-link-color: var(--tr-orange);
            --bs-link-hover-color: var(--tr-orange-dark);
            --bs-primary: var(--tr-orange);
            --bs-primary-rgb: 208, 82, 8;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--tr-font);
            font-size: .95rem;
            line-height: 1.7;
            color: var(--tr-ink);
            background: var(--tr-surface);
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4, h5, h6 {
            color: var(--tr-navy);
            font-family: var(--tr-font);
            font-weight: 700;
            letter-spacing: -.035em;
            line-height: 1.25;
        }
        main { min-height: 60vh; }
        ::selection { background: var(--tr-orange); color: #fff; }
        a { transition: color .2s ease, background-color .2s ease, border-color .2s ease, transform .2s ease; }
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
            outline: 3px solid rgba(208, 82, 8, .35);
            outline-offset: 3px;
        }

        /* ---------- Scroll progress ---------- */
        .tr-progress {
            position: fixed;
            top: 0; left: 0;
            height: 3px;
            width: 0;
            z-index: 2000;
            background: linear-gradient(90deg, var(--tr-orange), #f0894a);
            box-shadow: 0 0 10px rgba(208, 82, 8, .5);
            transition: width .1s linear;
        }

        /* ---------- Utility strip ---------- */
        .tr-topbar {
            background: var(--tr-navy-deep);
            color: rgba(255,255,255,.78);
            font-size: .8rem;
            font-weight: 500;
        }
        .tr-topbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 36px;
            gap: 16px;
        }
        .tr-topbar a {
            color: #fff;
            font-weight: 700;
            text-decoration: none;
        }
        .tr-topbar a:hover { color: #ffc9a6; }
        .tr-topbar a i { transition: transform .2s ease; display: inline-block; }
        .tr-topbar a:hover i { transform: translate(2px, -2px); }

        /* ---------- Navigation ---------- */
        .tr-nav {
            background: rgba(255,255,255,.88);
            border-bottom: 1px solid transparent;
            padding: 0;
            backdrop-filter: saturate(1.6) blur(16px);
            -webkit-backdrop-filter: saturate(1.6) blur(16px);
            transition: box-shadow .3s ease, border-color .3s ease, background .3s ease;
        }
        .tr-nav.is-scrolled {
            background: rgba(255,255,255,.96);
            border-bottom-color: var(--tr-line);
            box-shadow: 0 8px 30px rgba(25,38,93,.08);
        }
        .tr-brand {
            display: flex;
            flex-direction: column;
            line-height: 1.08;
            text-decoration: none;
            padding: 15px 0;
            margin-right: 2rem;
            flex-shrink: 0;
            transition: padding .3s ease;
        }
        .tr-nav.is-scrolled .tr-brand { padding: 10px 0; }
        .tr-brand-name {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -.065em;
            color: var(--tr-orange);
        }
        .tr-brand-sub {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--tr-navy);
            margin-top: 5px;
        }
        .tr-nav .navbar-nav { gap: 2px; }
        .tr-nav .nav-link {
            position: relative;
            color: #454b60;
            font-size: .87rem;
            font-weight: 600;
            padding: 27px 14px;
            transition: color .2s ease, padding .3s ease;
        }
        .tr-nav.is-scrolled .nav-link { padding-top: 22px; padding-bottom: 22px; }
        .tr-nav .nav-link::after {
            content: "";
            position: absolute;
            left: 14px; right: 14px; bottom: 0;
            height: 3px;
            border-radius: 3px 3px 0 0;
            background: var(--tr-orange);
            transform: scaleX(0);
            transform-origin: center;
            transition: transform .3s var(--tr-ease);
        }
        .tr-nav .nav-link:hover,
        .tr-nav .nav-link.active { color: var(--tr-orange); }
        .tr-nav .nav-link:hover::after,
        .tr-nav .nav-link.active::after { transform: scaleX(1); }

        .tr-nav-link-plain {
            color: var(--tr-navy);
            font-size: .87rem;
            font-weight: 700;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 10px;
        }
        .tr-nav-link-plain:hover {
            color: var(--tr-orange);
            background: var(--tr-orange-soft);
        }
        .tr-nav-btn {
            background: var(--tr-orange);
            border: 1px solid var(--tr-orange);
            color: #fff;
            font-size: .86rem;
            font-weight: 700;
            border-radius: 10px;
            padding: 11px 20px;
            box-shadow: 0 6px 16px rgba(208,82,8,.22);
        }
        .tr-nav-btn:hover, .tr-nav-btn:focus-visible {
            background: var(--tr-orange-dark);
            border-color: var(--tr-orange-dark);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(208,82,8,.30);
        }
        .tr-nav .navbar-toggler {
            border: 1px solid var(--tr-line);
            border-radius: 10px;
            padding: 6px 10px;
            color: var(--tr-navy);
        }
        .tr-nav .navbar-toggler:focus { box-shadow: 0 0 0 3px rgba(208,82,8,.15); }

        /* ---------- Shared components ---------- */
        .btn { border-radius: 10px; font-weight: 650; transition: all .25s var(--tr-ease); }
        .btn-primary {
            --bs-btn-bg: var(--tr-orange);
            --bs-btn-border-color: var(--tr-orange);
            --bs-btn-hover-bg: var(--tr-orange-dark);
            --bs-btn-hover-border-color: var(--tr-orange-dark);
            --bs-btn-active-bg: var(--tr-orange-dark);
            --bs-btn-active-border-color: var(--tr-orange-dark);
        }
        .btn-outline-primary {
            --bs-btn-color: var(--tr-orange);
            --bs-btn-border-color: var(--tr-orange);
            --bs-btn-hover-bg: var(--tr-orange);
            --bs-btn-hover-border-color: var(--tr-orange);
        }
        .form-control, .form-select {
            border-color: #dce1eb;
            border-radius: 10px;
            padding: .7rem .9rem;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }
        .form-control:hover, .form-select:hover { border-color: #c4cbdb; }
        .form-control:focus, .form-select:focus {
            border-color: var(--tr-orange);
            box-shadow: 0 0 0 .25rem rgba(208,82,8,.12);
        }
        .card {
            border-color: var(--tr-line);
            border-radius: 16px;
            box-shadow: var(--tr-shadow-sm);
        }
        .text-primary { color: var(--tr-orange) !important; }

        .tr-page { background: var(--tr-ground); color: var(--tr-ink); min-height: 70vh; }
        .tr-muted { color: var(--tr-muted); }

        .tr-btn {
            position: relative;
            overflow: hidden;
            background: var(--tr-orange);
            border: 1px solid var(--tr-orange);
            color: #fff;
            font-weight: 700;
            border-radius: 11px;
            padding: 12px 22px;
            box-shadow: 0 8px 20px rgba(208,82,8,.22);
        }
        .tr-btn::after {
            content: "";
            position: absolute;
            top: 0; left: -75%;
            width: 50%; height: 100%;
            background: linear-gradient(100deg, transparent, rgba(255,255,255,.35), transparent);
            transform: skewX(-20deg);
            transition: left .6s ease;
        }
        .tr-btn:hover, .tr-btn:focus-visible {
            background: var(--tr-orange-dark);
            border-color: var(--tr-orange-dark);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(208,82,8,.30);
        }
        .tr-btn:hover::after { left: 130%; }
        .tr-btn:active { transform: translateY(0); }

        .tr-btn-line {
            background: transparent;
            border: 1.5px solid var(--tr-orange);
            color: var(--tr-orange);
            font-weight: 700;
            border-radius: 11px;
            padding: 11px 22px;
        }
        .tr-btn-line:hover, .tr-btn-line:focus-visible {
            background: var(--tr-orange);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(208,82,8,.22);
        }
        .tr-btn-quiet {
            background: var(--tr-surface);
            border: 1.5px solid var(--tr-line);
            color: var(--tr-ink);
            font-weight: 700;
            border-radius: 11px;
            padding: 12px 20px;
        }
        .tr-btn-quiet:hover { border-color: var(--tr-navy); color: var(--tr-navy); }

        .tr-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 3;
            width: 38px; height: 38px;
            border: 0;
            border-radius: 50%;
            background: rgba(255,255,255,.95);
            color: var(--tr-navy);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(17,27,72,.25);
            padding: 0;
            transition: background .2s ease, color .2s ease, transform .2s ease, opacity .2s ease;
        }
        .tr-arrow.prev { left: 12px; }
        .tr-arrow.next { right: 12px; }
        .tr-arrow:hover { background: var(--tr-orange); color: #fff; transform: translateY(-50%) scale(1.1); }

        /* ---------- Back to top ---------- */
        .tr-top {
            position: fixed;
            right: 22px; bottom: 22px;
            z-index: 1500;
            width: 46px; height: 46px;
            border: 0;
            border-radius: 50%;
            background: var(--tr-navy);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--tr-shadow-md);
            opacity: 0;
            transform: translateY(16px);
            pointer-events: none;
            transition: opacity .3s ease, transform .3s var(--tr-ease), background .2s ease;
        }
        .tr-top.is-visible { opacity: 1; transform: none; pointer-events: auto; }
        .tr-top:hover { background: var(--tr-orange); transform: translateY(-3px); }

        /* ---------- Toast ---------- */
        .tr-toast {
            position: fixed;
            left: 50%; bottom: 28px;
            z-index: 2100;
            transform: translate(-50%, 24px);
            opacity: 0;
            background: var(--tr-navy-deep);
            color: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: .88rem;
            font-weight: 600;
            box-shadow: var(--tr-shadow-lg);
            pointer-events: none;
            transition: all .35s var(--tr-ease);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .tr-toast.is-visible { opacity: 1; transform: translate(-50%, 0); }
        .tr-toast i { color: #ffa46b; }

        /* ---------- Footer ---------- */
        .tr-footer {
            background: var(--tr-navy-deep);
            color: #cbd1e4;
            padding: 64px 0 24px;
            font-size: .88rem;
            position: relative;
            overflow: hidden;
        }
        
        .tr-footer .container { position: relative; z-index: 1; }
        .tr-footer h2 {
            color: #fff;
            font-size: .84rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 18px;
        }
        .tr-footer a { color: #cbd1e4; text-decoration: none; }
        .tr-footer ul { list-style: none; padding: 0; margin: 0; }
        .tr-footer li + li { margin-top: 11px; }
        .tr-footer ul a {
            display: inline-block;
            position: relative;
            transition: color .2s ease, transform .25s var(--tr-ease);
        }
        .tr-footer ul a:hover { color: #fff; transform: translateX(5px); }
        .tr-footer-brand {
            font-size: 1.9rem;
            font-weight: 800;
            color: #fff !important;
            letter-spacing: -.06em;
            line-height: 1.1;
        }
        .tr-footer-brand small {
            display: block;
            font-size: .72rem;
            font-weight: 700;
            color: #f2a477;
            margin-top: 7px;
            letter-spacing: .07em;
            text-transform: uppercase;
        }
        .tr-footer-base {
            border-top: 1px solid rgba(255,255,255,.12);
            margin-top: 44px;
            padding-top: 20px;
            font-size: .8rem;
            color: #aab3d0;
        }

        @media (max-width: 991.98px) {
            .tr-brand { padding: 12px 0; }
            .tr-nav .navbar-collapse {
                padding: 10px 0 18px;
                border-top: 1px solid var(--tr-line);
            }
            .tr-nav .nav-link { padding: 12px 2px !important; border-bottom: 1px solid var(--tr-line); }
            .tr-nav .nav-link::after { display: none; }
            .tr-nav .nav-link.active { color: var(--tr-orange); }
            .tr-nav-actions { margin-top: 16px; }
        }
        @media (max-width: 575.98px) {
            body { font-size: .92rem; }
            .tr-footer { padding-top: 44px; }
            .tr-footer-base { margin-top: 30px; }
            .tr-top { right: 14px; bottom: 84px; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
        }
    </style>

    @stack('styles')
    @stack('head')
</head>

<body>

    <div class="tr-progress" id="trProgress" aria-hidden="true"></div>

    {{-- Utility strip --}}
    <div class="tr-topbar d-none d-md-block">
        <div class="container">
            <span><i class="bi bi-geo-alt me-1"></i> Kigali, Rwanda &nbsp;&middot;&nbsp; Find a rental, apply online and manage your tenancy.</span>
            <a href="https://www.terra.rw" target="_blank" rel="noopener">Visit terra.rw <i class="bi bi-arrow-up-right"></i></a>
        </div>
    </div>

    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg tr-nav sticky-top" id="trNav">
        <div class="container">

            {{-- To use a logo file: <img src="{{ asset('images/terra-logo.svg') }}" alt="Terra Smart Rental Management" height="34"> --}}
            <a class="tr-brand" href="{{ url('/') }}" aria-label="Terra Smart Rental Management home">
                <span class="tr-brand-name">Terra</span>
                <span class="tr-brand-sub">Smart Rental Management</span>
            </a>

            <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#baseNavbar"
                aria-controls="baseNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list fs-4"></i>
            </button>

            <div class="collapse navbar-collapse" id="baseNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}">{{ __('Home') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('listings.*') ? 'active' : '' }}" href="{{ route('listings.index') }}">{{ __('Properties') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('listings.map') ? 'active' : '' }}" href="{{ route('listings.map') }}">{{ __('Map') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('rentals.*') ? 'active' : '' }}" href="{{ route('rentals.index') }}">{{ __('Units For Rent') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/#property-management') }}">{{ __('Features') }}</a>
                    </li>
                </ul>

                <div class="tr-nav-actions d-flex flex-column flex-lg-row align-items-lg-center gap-2">
                    @include('partials.language-switcher', ['compact' => true])
                    @auth
                        <span class="small fw-semibold px-lg-2">
                            <i class="bi bi-person-circle me-1 text-primary"></i>{{ auth()->user()->name }}
                        </span>

                        @if(Route::has('dashboard'))
                            <a href="{{ route('dashboard') }}" class="btn tr-nav-btn">{{ __('My dashboard') }}</a>
                        @endif

                        @if(Route::has('logout'))
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="tr-nav-link-plain border-0 bg-transparent">{{ __('Sign out') }}</button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="tr-nav-link-plain">{{ __('Sign in') }}</a>
                        <a href="{{ route('register') }}" class="btn tr-nav-btn">{{ __('Create account') }}</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="tr-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5" data-aos="fade-up">
                    <a href="{{ url('/') }}" class="tr-footer-brand d-inline-block">
                        Terra
                        <small>Smart Rental Management</small>
                    </a>
                    <p class="mt-3 mb-0 pe-lg-5" style="max-width: 46ch;">
                        Find an available home, apply online and keep your lease,
                        rent payments and maintenance requests in one place.
                    </p>
                </div>

                <div class="col-6 col-lg-2 offset-lg-1" data-aos="fade-up" data-aos-delay="100">
                    <h2>Explore</h2>
                    <ul>
                        <li><a href="{{ route('rentals.index') }}">Find a rental</a></li>
                        <li><a href="{{ url('/#property-management') }}">{{ __('Features') }}</a></li>
                        <li><a href="{{ url('/#rentals') }}">Available homes</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-2" data-aos="fade-up" data-aos-delay="200">
                    <h2>Account</h2>
                    <ul>
                        <li><a href="{{ route('register') }}">{{ __('Create account') }}</a></li>
                        <li><a href="{{ route('login') }}">{{ __('Sign in') }}</a></li>
                    </ul>
                </div>

                <div class="col-lg-2" data-aos="fade-up" data-aos-delay="300">
                    <h2>Terra</h2>
                    <ul>
                        <li><a href="https://www.terra.rw" target="_blank" rel="noopener">terra.rw</a></li>
                    </ul>
                </div>
            </div>

            <div class="tr-footer-base d-flex flex-wrap justify-content-between gap-2">
                <span>&copy; {{ date('Y') }} Terra Smart Rental Management. All rights reserved.</span>
                <span><i class="bi bi-geo-alt me-1"></i>Kigali, Rwanda</span>
            </div>
        </div>
    </footer>

    <button type="button" class="tr-top" id="trTop" aria-label="Back to top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <div class="tr-toast" id="trToast" role="status" aria-live="polite">
        <i class="bi bi-check-circle-fill"></i><span id="trToastText"></span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        (function () {
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (window.AOS) {
                AOS.init({
                    duration: 750,
                    easing: 'ease-out-cubic',
                    once: true,
                    offset: 50,
                    disable: reduce
                });
            }

            var nav = document.getElementById('trNav');
            var bar = document.getElementById('trProgress');
            var topBtn = document.getElementById('trTop');
            var ticking = false;

            function onScroll() {
                var y = window.scrollY || document.documentElement.scrollTop;
                var h = document.documentElement.scrollHeight - window.innerHeight;
                nav.classList.toggle('is-scrolled', y > 10);
                bar.style.width = (h > 0 ? (y / h) * 100 : 0) + '%';
                topBtn.classList.toggle('is-visible', y > 480);
                ticking = false;
            }

            window.addEventListener('scroll', function () {
                if (!ticking) { requestAnimationFrame(onScroll); ticking = true; }
            }, { passive: true });
            onScroll();

            topBtn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
            });

            // Global toast helper: window.trToast('Saved')
            var toast = document.getElementById('trToast');
            var toastText = document.getElementById('trToastText');
            var toastTimer;
            window.trToast = function (message) {
                toastText.textContent = message;
                toast.classList.add('is-visible');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(function () { toast.classList.remove('is-visible'); }, 2600);
            };
        })();
    </script>

    @include('partials.intl-phone')
@stack('scripts')

</body>

</html>