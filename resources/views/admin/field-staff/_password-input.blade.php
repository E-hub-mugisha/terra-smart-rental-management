{{-- Password field with show/hide, generate and copy. Params: $id, $name, $required (bool). --}}
<div class="input-group">
    <input type="password" id="{{ $id }}" name="{{ $name }}" class="form-control font-monospace @error($name) is-invalid @enderror"
           autocomplete="new-password" minlength="8" maxlength="72" @if($required ?? false) required @endif
           placeholder="At least 8 characters, letters and numbers">
    <button class="btn btn-outline-secondary" type="button" data-pw-toggle="#{{ $id }}" title="Show / hide" aria-label="Show password"><i class="bi bi-eye"></i></button>
    <button class="btn btn-outline-secondary" type="button" data-pw-generate="#{{ $id }}" title="Generate a strong password" aria-label="Generate password"><i class="bi bi-magic"></i></button>
    <button class="btn btn-outline-secondary" type="button" data-pw-copy="#{{ $id }}" title="Copy" aria-label="Copy password"><i class="bi bi-clipboard"></i></button>
</div>
@error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
@once
@push('scripts')
<script>
(function () {
    var alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    function generate() {
        var out, buf = new Uint32Array(12);
        do {
            crypto.getRandomValues(buf);
            out = Array.from(buf, function (n) { return alphabet[n % alphabet.length]; }).join('');
        } while (!/[a-z]/.test(out) || !/[A-Z]/.test(out) || !/\d/.test(out));
        return out;
    }
    document.querySelectorAll('[data-pw-toggle]').forEach(function (b) {
        b.addEventListener('click', function () {
            var f = document.querySelector(b.dataset.pwToggle), show = f.type === 'password';
            f.type = show ? 'text' : 'password';
            b.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });
    document.querySelectorAll('[data-pw-generate]').forEach(function (b) {
        b.addEventListener('click', function () {
            var f = document.querySelector(b.dataset.pwGenerate);
            f.value = generate(); f.type = 'text';
            var t = document.querySelector('[data-pw-toggle="' + b.dataset.pwGenerate + '"] i'); if (t) t.className = 'bi bi-eye-slash';
            f.dispatchEvent(new Event('input'));
        });
    });
    document.querySelectorAll('[data-pw-copy]').forEach(function (b) {
        b.addEventListener('click', function () {
            var v = document.querySelector(b.dataset.pwCopy).value;
            if (!v) return;
            if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(v);
            b.querySelector('i').className = 'bi bi-check2';
            setTimeout(function () { b.querySelector('i').className = 'bi bi-clipboard'; }, 1500);
        });
    });
})();
</script>
@endpush
@endonce
