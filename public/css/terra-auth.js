/* Terra auth pages: shared behaviour. Place at public/js/terra-auth.js */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        if (window.AOS) {
            AOS.init({
                duration: 700,
                easing: 'ease-out-cubic',
                once: true,
                disable: window.matchMedia('(prefers-reduced-motion: reduce)').matches
            });
        }

        /* Show / hide password */
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.togglePassword);
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                var icon = btn.querySelector('i');
                icon.classList.toggle('bi-eye', !show);
                icon.classList.toggle('bi-eye-slash', show);
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                input.focus();
            });
        });

        /* Password strength meter */
        var strengthInput = document.querySelector('[data-strength]');
        if (strengthInput) {
            var meter = document.getElementById('strengthMeter');
            var label = document.getElementById('strengthLabel');
            var words = ['Use 8 or more characters', 'Weak', 'Fair', 'Good', 'Strong'];

            strengthInput.addEventListener('input', function () {
                var v = strengthInput.value;
                var score = 0;
                if (v.length >= 8) score++;
                if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
                if (/\d/.test(v)) score++;
                if (/[^A-Za-z0-9]/.test(v) || v.length >= 14) score++;
                if (!v) score = 0;
                meter.dataset.level = score;
                label.textContent = v ? 'Password strength: ' + words[score] : words[0];
            });
        }

        /* Confirm-password match indicator */
        var confirm = document.querySelector('[data-match]');
        if (confirm) {
            var source = document.getElementById(confirm.dataset.match);
            var hint = document.getElementById('matchHint');

            var check = function () {
                if (!confirm.value) {
                    hint.textContent = '';
                    hint.className = 'au-hint';
                    confirm.classList.remove('is-valid-match');
                    return;
                }
                var ok = confirm.value === source.value;
                hint.textContent = ok ? 'Passwords match' : 'Passwords do not match yet';
                hint.className = 'au-hint ' + (ok ? 'ok' : 'bad');
                confirm.classList.toggle('is-valid-match', ok);
            };
            confirm.addEventListener('input', check);
            source.addEventListener('input', check);
        }

        /* Submit spinner (prevents double submit) */
        document.querySelectorAll('form[data-auth-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (!form.checkValidity()) return;
                var btn = form.querySelector('.au-btn');
                if (!btn) return;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Please wait…';
            });
        });
    });
})();