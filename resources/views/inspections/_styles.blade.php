@once
@push('styles')
<style>
    .ti-card { background: var(--terra-surface, #fff); border: 1px solid var(--terra-border, #e5e9f1); border-radius: var(--terra-radius, 14px); box-shadow: var(--terra-shadow, none); }
    .ti-card + .ti-card { margin-top: 18px; }
    .ti-card-head { padding: 14px 18px; border-bottom: 1px solid var(--terra-border, #e5e9f1); display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    .ti-card-title { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--terra-navy, #19265d); }
    .ti-card-body { padding: 16px 18px; }
    .ti-pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 11px; border-radius: 999px; font-size: 12.5px; font-weight: 600; white-space: nowrap; }
    .ti-pill.st-draft { background: #eef1f7; color: #4b5671; }
    .ti-pill.st-awaiting_tenant { background: #fff3dc; color: #8f5a08; }
    .ti-pill.st-disputed { background: #fff1f0; color: #b42318; }
    .ti-pill.st-completed { background: #e8f7ee; color: #147847; }
    .ti-pill.ty-move_in { background: #e7f4fd; color: #116a9c; }
    .ti-pill.ty-move_out { background: #f3eefe; color: #5b3aa8; }
    .ti-cond { display: inline-block; padding: 2px 9px; border-radius: 6px; font-size: 12.5px; font-weight: 600; }
    .ti-cond.c-excellent, .ti-cond.c-good { background: #e8f7ee; color: #147847; }
    .ti-cond.c-fair { background: #fff8e1; color: #8a6100; }
    .ti-cond.c-poor { background: #fff3dc; color: #8f5a08; }
    .ti-cond.c-damaged, .ti-cond.c-missing { background: #fff1f0; color: #b42318; }
    .ti-cond.c-na, .ti-cond.c-none { background: #f2f4f8; color: #6b7280; }
    .ti-change { font-size: 12px; font-weight: 700; }
    .ti-change.worse { color: #b42318; } .ti-change.better { color: #147847; } .ti-change.same { color: #6b7280; }
    .ti-area { font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 700; color: var(--terra-muted, #737d91); padding: 12px 18px 6px; background: #fafbfd; border-top: 1px solid var(--terra-border, #e5e9f1); }
    .ti-row { display: grid; gap: 10px; align-items: center; padding: 10px 18px; border-top: 1px solid #f0f2f7; }
    .ti-row:first-of-type { border-top: 0; }
    .ti-row.is-worse { background: #fffafa; }
    .ti-facts { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; margin: 0; }
    .ti-facts dt { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--terra-muted, #737d91); font-weight: 700; }
    .ti-facts dd { margin: 2px 0 0; font-weight: 600; }
    .ti-thumbs { display: flex; flex-wrap: wrap; gap: 8px; }
    .ti-thumb { position: relative; width: 92px; height: 70px; border-radius: 8px; overflow: hidden; background: #e7ebf3; display: block; border: 1px solid var(--terra-border, #e5e9f1); }
    .ti-thumb img, .ti-thumb video { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ti-thumb .ti-play { position: absolute; inset: 0; display: grid; place-items: center; color: #fff; font-size: 1.4rem; background: rgba(0,0,0,.25); }
    .ti-thumb.is-sm { width: 46px; height: 36px; border-radius: 6px; }
    .ti-money { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed var(--terra-border, #e5e9f1); }
    .ti-money:last-child { border-bottom: 0; font-weight: 700; font-size: 1.05rem; color: var(--terra-navy, #19265d); }
    .ti-steps { display: flex; flex-wrap: wrap; gap: 6px; list-style: none; padding: 0; margin: 0; }
    .ti-steps li { display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: #9aa3b5; }
    .ti-steps li + li::before { content: ''; width: 18px; height: 2px; background: #dfe3ec; }
    .ti-steps li.is-done { color: #147847; } .ti-steps li.is-current { color: var(--terra-orange, #D05208); }
    .ti-btn-primary { background: var(--terra-orange, #D05208); border-color: var(--terra-orange, #D05208); color: #fff; }
    .ti-btn-primary:hover { background: var(--terra-orange-dark, #ac4205); border-color: var(--terra-orange-dark, #ac4205); color: #fff; }
    @media (max-width: 767.98px) { .ti-row { grid-template-columns: 1fr !important; } .ti-hide-sm { display: none !important; } }
</style>
@endpush
@endonce
