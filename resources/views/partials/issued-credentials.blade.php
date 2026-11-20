{{-- One-time display of login details after an admin creates or resets an account. --}}
@php $issued = session(\App\Support\IssuedCredentials::SESSION_KEY); @endphp
@if ($issued)
    <div class="card border-0 shadow-sm mb-3" role="region" aria-label="New login details" style="border-radius:14px;border-left:4px solid var(--terra-orange,#D05208)!important;">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <h2 class="h6 fw-bold mb-1"><i class="bi bi-key me-1"></i> Login details for {{ $issued['name'] }}</h2>
                    <div class="small text-muted">
                        @if ($issued['emailed'])
                            <i class="bi bi-envelope-check text-success"></i> Also emailed to {{ $issued['username'] }}.
                        @else
                            <i class="bi bi-exclamation-triangle text-warning"></i> Not emailed — share these details with the person directly.
                        @endif
                        This password is shown only now; it cannot be viewed again.
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-all>
                    <i class="bi bi-clipboard"></i> Copy all
                </button>
            </div>

            <div class="row g-2">
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1" for="issuedUsername">Username (email)</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="issuedUsername" class="form-control font-monospace" value="{{ $issued['username'] }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" data-copy="#issuedUsername" aria-label="Copy username"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1" for="issuedPassword">Password</label>
                    <div class="input-group input-group-sm">
                        <input type="password" id="issuedPassword" class="form-control font-monospace" value="{{ $issued['password'] }}" readonly autocomplete="off">
                        <button class="btn btn-outline-secondary" type="button" data-toggle-password="#issuedPassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
                        <button class="btn btn-outline-secondary" type="button" data-copy="#issuedPassword" aria-label="Copy password"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Sign-in page</label>
                    <div class="small text-truncate pt-1"><a href="{{ $issued['login_url'] }}" target="_blank" rel="noopener">{{ $issued['login_url'] }}</a></div>
                </div>
            </div>
            <div class="small text-muted mt-2">Ask them to change the password after signing in.</div>
        </div>
    </div>
    <script>
    (function () {
        var copy = function (text, btn) {
            var done = function () { var i = btn.querySelector('i'); if (i) { i.className = 'bi bi-check2'; setTimeout(function () { i.className = 'bi bi-clipboard'; }, 1500); } };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(done); }
            else { var t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); done(); } catch (e) {} t.remove(); }
        };
        document.querySelectorAll('[data-copy]').forEach(function (b) { b.addEventListener('click', function () { copy(document.querySelector(b.dataset.copy).value, b); }); });
        document.querySelectorAll('[data-toggle-password]').forEach(function (b) {
            b.addEventListener('click', function () {
                var f = document.querySelector(b.dataset.togglePassword), show = f.type === 'password';
                f.type = show ? 'text' : 'password';
                b.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
                b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
        document.querySelectorAll('[data-copy-all]').forEach(function (b) {
            b.addEventListener('click', function () {
                copy('Terra login\nSign in: {{ $issued['login_url'] }}\nUsername: ' + document.getElementById('issuedUsername').value + '\nPassword: ' + document.getElementById('issuedPassword').value, b);
            });
        });
    })();
    </script>
@endif
