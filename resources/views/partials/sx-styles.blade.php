{{-- Shared styles for subscriber, contract and field staff pages. --}}
@once
@push('styles')
<style>
    .sx-card { background: var(--terra-surface, #fff); border: 1px solid var(--terra-border, #e7eaf0); border-radius: 14px; }
    .sx-card > .sx-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--terra-border, #e7eaf0); }
    .sx-card > .sx-card-head h2, .sx-card > .sx-card-head h3 { font-size: 1rem; margin: 0; font-weight: 700; color: var(--terra-navy, #19265d); }
    .sx-card > .sx-card-body { padding: 16px 18px; }
    .sx-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 18px; }
    .sx-stat { background: var(--terra-surface, #fff); border: 1px solid var(--terra-border, #e7eaf0); border-radius: 14px; padding: 14px 16px; text-decoration: none; color: inherit; display: block; }
    a.sx-stat:hover { border-color: var(--terra-orange, #D05208); }
    .sx-stat .sx-label { font-size: .75rem; color: var(--terra-muted, #748094); text-transform: uppercase; letter-spacing: .04em; display: flex; align-items: center; gap: 6px; }
    .sx-stat .sx-value { font-size: 1.35rem; font-weight: 700; color: var(--terra-navy, #19265d); margin-top: 4px; font-variant-numeric: tabular-nums; }
    .sx-stat .sx-sub { font-size: .75rem; color: var(--terra-muted, #748094); }
    .sx-stat.sx-danger .sx-value { color: #b42318; }
    .sx-stat.sx-warn .sx-value { color: #b54708; }
    .sx-stat.sx-ok .sx-value { color: #067647; }
    .sx-table { width: 100%; font-size: .86rem; }
    .sx-table th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--terra-muted, #748094); font-weight: 600; white-space: nowrap; }
    .sx-table td, .sx-table th { padding: .6rem .75rem; vertical-align: middle; }
    .sx-table tbody tr + tr td { border-top: 1px solid var(--terra-border, #eef0f4); }
    .sx-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .sx-pill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 999px; font-size: .72rem; font-weight: 600; white-space: nowrap; }
    .sx-pill.success { background: #ecfdf3; color: #067647; }
    .sx-pill.warning { background: #fffaeb; color: #b54708; }
    .sx-pill.danger { background: #fef3f2; color: #b42318; }
    .sx-pill.info { background: #eff8ff; color: #175cd3; }
    .sx-pill.secondary { background: #f2f4f7; color: #475467; }
    .sx-avatar { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #fff4ec; color: var(--terra-orange, #D05208); font-weight: 700; font-size: .85rem; flex: none; }
    .sx-avatar.lg { width: 56px; height: 56px; font-size: 1.1rem; }
    .sx-muted { color: var(--terra-muted, #748094); }
    .sx-btn { display: inline-flex; align-items: center; gap: 6px; border-radius: 10px; padding: .45rem .9rem; font-size: .85rem; font-weight: 600; border: 1px solid transparent; text-decoration: none; cursor: pointer; }
    .sx-btn-primary { background: var(--terra-orange, #D05208); color: #fff; }
    .sx-btn-primary:hover { background: #b84706; color: #fff; }
    .sx-btn-outline { background: transparent; border-color: var(--terra-border, #d0d5dd); color: var(--terra-navy, #19265d); }
    .sx-btn-outline:hover { border-color: var(--terra-orange, #D05208); color: var(--terra-orange, #D05208); }
    .sx-btn-success { background: #067647; color: #fff; }
    .sx-btn-success:hover { background: #05603a; color: #fff; }
    .sx-btn-danger { background: transparent; border-color: #fda29b; color: #b42318; }
    .sx-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--terra-border, #e7eaf0); margin-bottom: 16px; overflow-x: auto; }
    .sx-tabs button { background: none; border: 0; padding: .6rem .9rem; font-size: .88rem; color: var(--terra-muted, #748094); border-bottom: 2px solid transparent; white-space: nowrap; }
    .sx-tabs button.active { color: var(--terra-navy, #19265d); border-bottom-color: var(--terra-orange, #D05208); font-weight: 700; }
    .sx-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: end; }
    .sx-filters .form-control, .sx-filters .form-select { font-size: .85rem; }
    .sx-chipbar { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
    .sx-chip { padding: .3rem .8rem; border-radius: 999px; border: 1px solid var(--terra-border, #d0d5dd); font-size: .8rem; text-decoration: none; color: var(--terra-navy, #19265d); }
    .sx-chip.active { background: var(--terra-navy, #19265d); border-color: var(--terra-navy, #19265d); color: #fff; }
    .sx-dl { display: grid; grid-template-columns: max-content 1fr; gap: 6px 16px; font-size: .86rem; margin: 0; }
    .sx-dl dt { color: var(--terra-muted, #748094); font-weight: 500; }
    .sx-dl dd { margin: 0; color: var(--terra-navy, #19265d); word-break: break-word; }
    .sx-empty { text-align: center; padding: 28px 12px; color: var(--terra-muted, #748094); font-size: .88rem; }
    .sx-empty i { font-size: 1.6rem; display: block; margin-bottom: 6px; opacity: .6; }
    .sx-timeline { list-style: none; padding: 0; margin: 0; }
    .sx-timeline li { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--terra-border, #eef0f4); }
    .sx-timeline li:last-child { border-bottom: 0; }
    .sx-timeline .sx-ico { width: 32px; height: 32px; border-radius: 50%; background: #f2f4f7; display: inline-flex; align-items: center; justify-content: center; flex: none; color: var(--terra-navy, #19265d); }
    .sx-bar { display: flex; align-items: end; gap: 8px; height: 110px; padding-top: 8px; }
    .sx-bar > div { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; font-size: .7rem; color: var(--terra-muted, #748094); height: 100%; justify-content: flex-end; }
    .sx-bar span.b { width: 100%; max-width: 36px; background: var(--terra-orange, #D05208); border-radius: 4px 4px 0 0; min-height: 2px; }
    .sx-bar strong { font-size: .72rem; color: var(--terra-navy, #19265d); }
    @media (max-width: 575.98px) {
        .sx-card > .sx-card-head { flex-wrap: wrap; }
        .sx-dl { grid-template-columns: 1fr; }
        .sx-dl dt { margin-top: 6px; }
    }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) .sx-pill.success { background: rgba(6,118,71,.2); color: #6ce9a6; }
        :root:not([data-theme="light"]) .sx-pill.warning { background: rgba(181,71,8,.2); color: #fec84b; }
        :root:not([data-theme="light"]) .sx-pill.danger { background: rgba(180,35,24,.2); color: #fda29b; }
        :root:not([data-theme="light"]) .sx-pill.info { background: rgba(23,92,211,.2); color: #84caff; }
    }
</style>
@endpush
@endonce
