{{--
    Platform typeface: Source Serif 4 (variable, with optical sizing), falling
    back to Georgia. Included once in every standalone page <head>.
--}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,opsz,wght@0,8..60,400..800;1,8..60,400..600&display=swap" rel="stylesheet">
<style>
    :root {
        --terra-font: "Source Serif 4", Georgia, serif;
        --bs-body-font-family: var(--terra-font);
        --bs-font-sans-serif: var(--terra-font);
    }
    html { -webkit-text-size-adjust: 100%; }
    body, button, input, select, textarea, optgroup, .btn, .form-control, .form-select,
    .dropdown-menu, .modal, .tooltip, .popover, .badge, .table, .nav-link {
        font-family: var(--terra-font);
        font-optical-sizing: auto;
    }
    /* Serif type reads best with natural spacing; undo sans-style tight tracking. */
    h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 { font-family: var(--terra-font); letter-spacing: -.005em; }
    /* Keep code and reference numbers monospaced. */
    code, kbd, pre, samp { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
</style>
