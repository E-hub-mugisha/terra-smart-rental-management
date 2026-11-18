{{--
    International phone input (all countries, dialing codes, validation).
    Any <input data-intl-phone> is enhanced with a country picker; on submit
    the value is replaced by the E.164 form (+250788123456). The server
    validates and normalises again, so this is a convenience, not the guard.
    The library is only downloaded on pages that contain a phone field.
--}}
<style>
    .iti { width: 100%; display: block; }
    .iti__country-list { z-index: 1060; font-size: .85rem; max-width: min(360px, 90vw); white-space: normal; }
    .iti input[data-intl-phone] { padding-left: 92px !important; }
    .tf-input-icon .iti ~ i, .tf-input-icon:has(.iti) > i.bi { display: none; }
    .iti-feedback { display: none; font-size: .78rem; color: #b42318; margin-top: .25rem; }
    .iti-feedback.show { display: block; }
    .iti-hint { font-size: .75rem; color: var(--terra-muted, #6b7280); margin-top: .2rem; }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) .iti__country-list { background: #1f2937; color: #e5e7eb; border-color: #374151; }
        :root:not([data-theme="light"]) .iti__country.iti__highlight { background: #374151; }
    }
    :root[data-theme="dark"] .iti__country-list { background: #1f2937; color: #e5e7eb; border-color: #374151; }
    :root[data-theme="dark"] .iti__country.iti__highlight { background: #374151; }
</style>
<script>
(function () {
    var BASE = 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.2.1/';

    function enhance() {
        var inputs = document.querySelectorAll('input[data-intl-phone]:not([data-iti-ready])');
        if (!inputs.length || !window.intlTelInput) return;

        inputs.forEach(function (input) {
            input.setAttribute('data-iti-ready', '1');
            input.setAttribute('type', 'tel');
            input.removeAttribute('pattern');
            var iti = window.intlTelInput(input, {
                initialCountry: (input.dataset.country || 'rw').toLowerCase(),
                preferredCountries: ['rw', 'ug', 'ke', 'tz', 'bi', 'cd'],
                separateDialCode: true,
                nationalMode: true,
                autoPlaceholder: 'aggressive',
                formatOnDisplay: true,
                utilsScript: BASE + 'js/utils.min.js'
            });

            var feedback = document.createElement('div');
            feedback.className = 'iti-feedback';
            feedback.textContent = 'Enter a valid number for the selected country.';
            (input.closest('.iti') || input).insertAdjacentElement('afterend', feedback);

            function check(final) {
                var empty = input.value.trim() === '';
                var ok = empty || !window.intlTelInputUtils || iti.isValidNumber();
                feedback.classList.toggle('show', !ok && (final || input.dataset.touched));
                input.classList.toggle('is-invalid', !ok && !!(final || input.dataset.touched));
                return ok;
            }

            input.addEventListener('blur', function () { input.dataset.touched = '1'; check(false); });
            input.addEventListener('countrychange', function () { if (input.dataset.touched) check(false); });

            if (input.form) {
                input.form.addEventListener('submit', function (e) {
                    if (input.value.trim() === '') return;
                    if (!check(true)) {
                        e.preventDefault();
                        input.focus();
                        return;
                    }
                    if (window.intlTelInputUtils) input.value = iti.getNumber(); // E.164
                });
            }
        });
    }

    function load() {
        if (!document.querySelector('input[data-intl-phone]')) return;
        if (window.intlTelInput) return enhance();
        var css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = BASE + 'css/intlTelInput.css';
        document.head.appendChild(css);
        var js = document.createElement('script');
        js.src = BASE + 'js/intlTelInput.min.js';
        js.onload = enhance;
        document.head.appendChild(js);
    }

    window.terraEnhancePhones = load;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', load); else load();
})();
</script>
