@extends('layouts.app')

@section('title', $property->name . ' | Property Details')



@section('content')

<style>
/* =========================================================
   TERRA PROPERTIES — shared design system (index + show)
   Everything is scoped under .tp so it cannot leak into the layout.
   Tip: if your top navbar is fixed, set --tp-sticky to its height.
   ========================================================= */
.tp {
    --o: #D05208; --o-dark: #b94706; --o-soft: #fff2e9; --o-line: #f4d3bf;
    --navy: #19265d; --ink: #27334a; --mute: #6b778c;
    --line: #e6e9f0; --line-soft: #eff1f6; --surface: #fff; --wash: #f6f7fb;
    --ok: #147847; --ok-bg: #e8f7ee; --warn: #8f5a08; --warn-bg: #fff3dc;
    --bad: #b42318; --bad-bg: #fff1f0; --info: #116a9c; --info-bg: #e7f4fd;
    --tp-sticky: 0px;
    color: var(--ink); font-size: .9rem; padding-bottom: 40px;
}
.tp *, .tp *::before, .tp *::after { box-sizing: border-box; }
.tp a { text-underline-offset: 3px; }
.tp :focus-visible { outline: 2px solid var(--o); outline-offset: 2px; border-radius: 6px; }
.tp .min-w-0 { min-width: 0; }
.tp .is-mute { color: var(--mute); }

/* ---------- Page head ---------- */
.tp-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
.tp-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; color: var(--mute); font-size: .8rem; }
.tp-crumbs a { color: var(--mute); text-decoration: none; }
.tp-crumbs a:hover { color: var(--o); }
.tp-crumbs i { font-size: 10px; }
.tp-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
.tp-title { margin: 0 0 6px; color: var(--navy); font-size: clamp(1.5rem, 2.6vw, 2rem); font-weight: 800; letter-spacing: -.035em; }
.tp-sub { margin: 0; max-width: 620px; color: var(--mute); line-height: 1.6; }

/* ---------- Buttons ---------- */
.tp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    min-height: 38px; padding: 8px 14px; border: 1px solid var(--line); border-radius: 10px;
    background: var(--surface); color: var(--navy); font-size: .82rem; font-weight: 700; line-height: 1.2;
    text-decoration: none; white-space: nowrap; cursor: pointer;
    transition: background .15s, border-color .15s, color .15s;
}
.tp-btn:hover { background: var(--o-soft); border-color: var(--o-line); color: var(--o); }
.tp-btn.is-primary { background: var(--o); border-color: var(--o); color: #fff; }
.tp-btn.is-primary:hover { background: var(--o-dark); border-color: var(--o-dark); color: #fff; }
.tp-btn.is-danger { color: var(--bad); }
.tp-btn.is-danger:hover { background: var(--bad-bg); border-color: #f1c9c5; color: var(--bad); }
.tp-btn.is-sm { min-height: 32px; padding: 5px 10px; font-size: .76rem; border-radius: 8px; }
.tp-btn.is-icon { width: 36px; min-width: 36px; padding: 0; }
.tp-btn.is-sm.is-icon { width: 32px; min-width: 32px; }
.tp-btn:disabled, .tp-btn[disabled] { opacity: .5; pointer-events: none; }

.tp-menu { min-width: 200px; padding: 6px; border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 14px 34px rgba(25,38,93,.13); font-size: .83rem; }
.tp-menu .dropdown-item { display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: 8px; font-weight: 600; }
.tp-menu .dropdown-item:hover { background: var(--wash); }
.tp-menu .dropdown-item.is-danger { color: var(--bad); }
.tp-menu .dropdown-item.is-danger:hover { background: var(--bad-bg); }

/* ---------- Status pills ---------- */
.tp-pill {
    display: inline-flex; align-items: center; gap: 5px; width: fit-content; max-width: 100%;
    padding: 4px 9px; border-radius: 999px; font-size: .72rem; font-weight: 700; line-height: 1.3; white-space: nowrap;
    background: var(--wash); color: var(--mute);
}
.tp-pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.tp-pill.no-dot::before { display: none; }
.tp-pill.is-success { background: var(--ok-bg); color: var(--ok); }
.tp-pill.is-warning { background: var(--warn-bg); color: var(--warn); }
.tp-pill.is-info { background: var(--info-bg); color: var(--info); }
.tp-pill.is-navy { background: #edf0fb; color: var(--navy); }
.tp-pill.is-orange { background: var(--o-soft); color: #a64108; }

/* ---------- Panels ---------- */
.tp-panel { min-width: 0; background: var(--surface); border: 1px solid var(--line); border-radius: 16px; }
.tp-panel + .tp-panel { margin-top: 20px; }
.tp-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 20px; border-bottom: 1px solid var(--line); }
.tp-panel-title { margin: 0; color: var(--navy); font-size: 1rem; font-weight: 800; letter-spacing: -.015em; }
.tp-panel-note { margin: 3px 0 0; color: var(--mute); font-size: .79rem; }
.tp-panel-body { padding: 20px; }

/* ---------- Summary strip (replaces four separate cards) ---------- */
.tp-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 20px; background: var(--surface); border: 1px solid var(--line); border-radius: 16px; }
.tp-strip-cell { min-width: 0; padding: 18px 20px; border-left: 1px solid var(--line); }
.tp-strip-cell:first-child { border-left: 0; }
.tp-strip-label { display: flex; align-items: center; gap: 7px; color: var(--mute); font-size: .78rem; font-weight: 700; }
.tp-strip-value { margin-top: 8px; color: var(--navy); font-size: 1.8rem; font-weight: 800; letter-spacing: -.04em; line-height: 1; }
.tp-strip-foot { margin-top: 7px; color: var(--mute); font-size: .75rem; }
.tp-strip-cell.is-link { text-decoration: none; transition: background .15s; }
.tp-strip-cell.is-link:hover { background: var(--wash); }
.tp-strip-cell.is-current { box-shadow: inset 0 -3px 0 var(--o); }

.tp-ring { --p: 0; width: 54px; height: 54px; flex: 0 0 54px; display: grid; place-items: center; border-radius: 50%;
    background: conic-gradient(var(--o) calc(var(--p) * 1%), #edf0f5 0); }
.tp-ring span { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 50%; background: var(--surface); color: var(--navy); font-size: .78rem; font-weight: 800; }

/* ---------- Toolbar (search, tabs, view toggle) ---------- */
.tp-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 16px; border-bottom: 1px solid var(--line); }
.tp-search { position: relative; flex: 1 1 260px; max-width: 420px; }
.tp-search > i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #8f9ab0; pointer-events: none; }
.tp-input { width: 100%; min-height: 40px; padding: 8px 12px; border: 1px solid #dde2ec; border-radius: 10px; background: #fff; color: var(--ink); font-size: .85rem; }
.tp-search .tp-input { padding-left: 38px; padding-right: 36px; }
.tp-input:focus { border-color: var(--o); box-shadow: 0 0 0 3px rgba(208,82,8,.1); outline: none; }
.tp-search-clear { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); display: grid; place-items: center; width: 28px; height: 28px; border-radius: 8px; color: var(--mute); text-decoration: none; background: none; border: 0; }
.tp-search-clear:hover { background: var(--wash); color: var(--o); }
.tp-tabs { display: inline-flex; flex-wrap: wrap; gap: 4px; padding: 4px; background: var(--wash); border-radius: 11px; }
.tp-tab { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 0; border-radius: 8px; background: transparent; color: var(--mute); font-size: .79rem; font-weight: 700; text-decoration: none; cursor: pointer; }
.tp-tab:hover { color: var(--navy); }
.tp-tab.is-active { background: #fff; color: var(--navy); box-shadow: 0 1px 3px rgba(25,38,93,.12); }
.tp-tab small { color: var(--mute); font-weight: 700; font-size: .72rem; }
.tp-toolbar-end { display: flex; align-items: center; gap: 8px; margin-left: auto; }
.tp-count { color: var(--mute); font-size: .78rem; }
.tp-filter-note { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 10px 16px; background: var(--o-soft); border-bottom: 1px solid var(--o-line); color: #8a3d08; font-size: .8rem; }
.tp-filter-note a { color: inherit; font-weight: 700; }

/* ---------- Property list (one markup, list or grid view) ---------- */
.tp-list-head, .tp-row { display: grid; align-items: center; gap: 0 16px;
    grid-template-columns: 76px minmax(0, 2.1fr) minmax(0, 1.3fr) minmax(0, 1.3fr) 112px 128px 104px; }
.tp-list-head { padding: 10px 18px; background: var(--wash); border-bottom: 1px solid var(--line); color: var(--mute); font-size: .74rem; font-weight: 700; }
.tp-list-head .t-end { text-align: right; }
.tp-row { position: relative; padding: 14px 18px; border-bottom: 1px solid var(--line-soft); transition: background .15s; }
.tp-row:last-child { border-bottom: 0; border-radius: 0 0 16px 16px; }
.tp-row:hover { background: #fcfcfe; }
.tp-meta { display: contents; }
.tp-thumb { position: relative; display: grid; place-items: center; width: 76px; height: 60px; overflow: hidden; border: 1px solid var(--line); border-radius: 11px; background: var(--wash); color: #9aa4b8; font-size: 1.4rem; text-decoration: none; }
.tp-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.tp-thumb-count { position: absolute; right: 4px; bottom: 4px; padding: 2px 6px; border-radius: 6px; background: rgba(25,38,93,.88); color: #fff; font-size: .68rem; font-weight: 700; }
.tp-name { display: inline-block; color: var(--navy); font-size: .93rem; font-weight: 800; line-height: 1.35; text-decoration: none; overflow-wrap: anywhere; }
.tp-name:hover { color: var(--o); }
.tp-sub-line { margin-top: 3px; color: var(--mute); font-size: .78rem; line-height: 1.5; overflow-wrap: anywhere; }
.tp-cell { min-width: 0; font-size: .82rem; }
.tp-cell-strong { color: #2f3b52; font-weight: 700; overflow-wrap: anywhere; }
.tp-cell-label { display: none; margin-bottom: 2px; color: var(--mute); font-size: .7rem; font-weight: 700; }
.tp-act { display: flex; justify-content: flex-end; align-items: center; gap: 6px; }

.tp-list[data-view="grid"] { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 16px; padding: 16px; }
.tp-list[data-view="grid"] .tp-list-head { display: none; }
.tp-list[data-view="grid"] .tp-row { display: flex; flex-direction: column; align-items: stretch; gap: 12px; padding: 0 0 14px; border: 1px solid var(--line); border-radius: 14px; overflow: visible; }
.tp-list[data-view="grid"] .tp-row:last-child { border-radius: 14px; }
.tp-list[data-view="grid"] .tp-thumb { width: 100%; height: 168px; border: 0; border-radius: 13px 13px 0 0; }
.tp-list[data-view="grid"] .tp-main, .tp-list[data-view="grid"] .tp-meta, .tp-list[data-view="grid"] .tp-act { padding: 0 14px; }
.tp-list[data-view="grid"] .tp-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; }
.tp-list[data-view="grid"] .tp-cell-label { display: block; }
.tp-list[data-view="grid"] .tp-act { justify-content: flex-start; margin-top: auto; }

.tp-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; border-top: 1px solid var(--line); }
.tp-foot .pagination { margin: 0; gap: 4px; }
.tp-foot .page-link { border-radius: 8px; border-color: var(--line); color: var(--navy); font-size: .8rem; box-shadow: none; }
.tp-foot .page-item.active .page-link { background: var(--o); border-color: var(--o); color: #fff; }

.tp-empty { padding: 56px 20px; text-align: center; }
.tp-empty-icon { display: grid; place-items: center; width: 60px; height: 60px; margin: 0 auto 16px; border-radius: 18px; background: var(--o-soft); color: var(--o); font-size: 1.6rem; }
.tp-empty h3 { margin: 0 0 6px; color: var(--navy); font-size: 1.02rem; font-weight: 800; }
.tp-empty p { max-width: 400px; margin: 0 auto 18px; color: var(--mute); line-height: 1.6; }
.tp-empty.is-inline { padding: 26px 16px; background: var(--wash); border: 1px dashed #d6dbe6; border-radius: 12px; }
.tp-empty.is-inline .tp-empty-icon { width: 46px; height: 46px; font-size: 1.2rem; border-radius: 14px; }

/* ---------- Show page: hero ---------- */
.tp-hero { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 0; margin-bottom: 20px; overflow: hidden; background: var(--surface); border: 1px solid var(--line); border-radius: 18px; }
.tp-hero-main { padding: 26px 28px; }
.tp-hero-title { margin: 12px 0 8px; color: var(--navy); font-size: clamp(1.6rem, 3vw, 2.3rem); font-weight: 800; letter-spacing: -.04em; line-height: 1.1; overflow-wrap: anywhere; }
.tp-hero-loc { display: flex; gap: 8px; margin: 0 0 18px; color: var(--mute); line-height: 1.6; }
.tp-hero-loc i { color: var(--o); margin-top: 3px; }
.tp-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin: 0; padding-top: 18px; border-top: 1px solid var(--line-soft); }
.tp-facts dt { margin-bottom: 3px; color: var(--mute); font-size: .74rem; font-weight: 700; }
.tp-facts dd { margin: 0; color: var(--ink); font-weight: 700; overflow-wrap: anywhere; }
.tp-facts dd small { display: block; color: var(--mute); font-weight: 500; font-size: .76rem; }
.tp-hero-cover { position: relative; display: block; width: 100%; min-height: 260px; height: 100%; padding: 0; border: 0; background: var(--wash); cursor: zoom-in; }
.tp-hero-cover img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.tp-hero-cover.is-empty { display: grid; place-items: center; align-content: center; gap: 8px; color: #9aa4b8; cursor: default; }
.tp-hero-cover.is-empty i { font-size: 2.4rem; }
.tp-hero-hint { position: absolute; right: 12px; bottom: 12px; padding: 5px 10px; border-radius: 999px; background: rgba(25,38,93,.85); color: #fff; font-size: .72rem; font-weight: 700; }

/* ---------- Show page: section nav ---------- */
.tp-subnav { position: sticky; top: var(--tp-sticky); z-index: 20; display: flex; gap: 4px; margin: 0 0 20px; padding: 6px; overflow-x: auto; background: rgba(255,255,255,.94); backdrop-filter: blur(8px); border: 1px solid var(--line); border-radius: 13px; scrollbar-width: none; }
.tp-subnav::-webkit-scrollbar { display: none; }
.tp-subnav a { flex: 0 0 auto; padding: 7px 13px; border-radius: 9px; color: var(--mute); font-size: .8rem; font-weight: 700; text-decoration: none; }
.tp-subnav a:hover { color: var(--navy); background: var(--wash); }
.tp-subnav a.is-active { color: var(--o); background: var(--o-soft); }
.tp section[id], .tp [id^="property-"], .tp div[id^="building-"] { scroll-margin-top: calc(var(--tp-sticky) + 74px); }
.tp-side { position: sticky; top: calc(var(--tp-sticky) + 74px); }

/* ---------- Show page: overview ---------- */
.tp-dl { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0; margin: 0; border: 1px solid var(--line-soft); border-radius: 12px; overflow: hidden; }
.tp-dl > div { padding: 12px 14px; border-right: 1px solid var(--line-soft); border-bottom: 1px solid var(--line-soft); margin: -0.5px; }
.tp-dl dt { margin-bottom: 3px; color: var(--mute); font-size: .74rem; font-weight: 700; }
.tp-dl dd { margin: 0; color: var(--ink); font-weight: 650; overflow-wrap: anywhere; }
.tp-label { margin-bottom: 6px; color: var(--mute); font-size: .76rem; font-weight: 700; }

.tp-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; }
.tp-tile { position: relative; display: block; width: 100%; padding: 0; overflow: hidden; border: 1px solid var(--line); border-radius: 12px; background: var(--wash); cursor: zoom-in; }
.tp-tile img { display: block; width: 100%; height: 150px; object-fit: cover; transition: transform .25s; }
.tp-tile:hover img { transform: scale(1.04); }
.tp-tile .tp-pill { position: absolute; top: 8px; left: 8px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.12); }

/* ---------- Show page: structure ---------- */
.tp-struct-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 16px; }
.tp-struct-bar .tp-search { max-width: 300px; }
.tp-building { margin-top: 14px; border: 1px solid var(--line); border-radius: 14px; background: #fff; overflow: hidden; }
.tp-building:first-of-type { margin-top: 0; }
.tp-building-head { display: flex; align-items: center; gap: 12px; padding: 14px; background: #fbfcfe; }
.tp-toggle { display: grid; place-items: center; flex: 0 0 30px; width: 30px; height: 30px; border: 1px solid var(--line); border-radius: 8px; background: #fff; color: var(--navy); }
.tp-toggle i { transition: transform .2s; }
.tp-toggle[aria-expanded="false"] i { transform: rotate(-90deg); }
.tp-sq { display: grid; place-items: center; flex: 0 0 56px; width: 56px; height: 56px; object-fit: cover; border: 1px solid var(--line); border-radius: 12px; background: var(--wash); color: #8a96aa; font-size: 1.2rem; }
.tp-sq.is-sm { flex-basis: 38px; width: 38px; height: 38px; border-radius: 9px; font-size: 1rem; }
.tp-building-name { margin: 0; color: var(--navy); font-size: .95rem; font-weight: 800; overflow-wrap: anywhere; }
.tp-occ { display: flex; align-items: center; gap: 8px; margin-top: 6px; color: var(--mute); font-size: .76rem; }
.tp-occ-bar { width: 90px; height: 5px; overflow: hidden; border-radius: 9px; background: #edf0f5; }
.tp-occ-bar i { display: block; height: 100%; background: var(--o); border-radius: 9px; }
.tp-building-body { border-top: 1px solid var(--line); }
.tp-building-inner { padding: 16px; }
.tp-floor { margin-top: 12px; border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
.tp-floor:first-child { margin-top: 0; }
.tp-floor-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 14px; background: var(--wash); border-bottom: 1px solid var(--line); }
.tp-floor-head strong { color: var(--navy); font-size: .85rem; }
.tp-floor-empty { padding: 16px; text-align: center; color: var(--mute); font-size: .82rem; }
.tp-table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.tp-units { width: 100%; min-width: 640px; margin: 0; border-collapse: collapse; font-size: .82rem; }
.tp-units th { padding: 10px 14px; background: #fff; border-bottom: 1px solid var(--line); color: var(--mute); font-size: .74rem; font-weight: 700; white-space: nowrap; text-align: left; }
.tp-units td { padding: 11px 14px; border-bottom: 1px solid var(--line-soft); vertical-align: middle; }
.tp-units tr:last-child td { border-bottom: 0; }
.tp-units tbody tr:hover td { background: #fcfcfe; }
.tp-units .t-end { text-align: right; }
.tp-unit-no { color: var(--navy); font-weight: 800; white-space: nowrap; }
.tp-rent { color: var(--navy); font-weight: 800; white-space: nowrap; }
.tp-no-match { display: none; padding: 26px 16px; text-align: center; color: var(--mute); }
.tp-no-match.is-visible { display: block; }

/* ---------- Sidebar ---------- */
.tp-manager { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line-soft); }
.tp-manager:first-child { padding-top: 0; }
.tp-avatar { display: grid; place-items: center; flex: 0 0 40px; width: 40px; height: 40px; border-radius: 12px; background: #edf0fb; color: var(--navy); font-size: .8rem; font-weight: 800; }
.tp-record { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line-soft); font-size: .82rem; }
.tp-record:last-child { border-bottom: 0; padding-bottom: 0; }
.tp-record span:first-child { color: var(--mute); }
.tp-record span:last-child { color: var(--ink); font-weight: 700; text-align: right; }

/* ---------- Modals & forms ---------- */
.tp .modal-content { border: 0; border-radius: 16px; box-shadow: 0 24px 70px rgba(20,30,60,.22); overflow: hidden; }
.tp .modal-header { padding: 18px 22px; background: #fbfcfe; border-bottom: 1px solid var(--line); }
.tp .modal-title { color: var(--navy); font-size: 1.02rem; font-weight: 800; }
.tp .modal-body { padding: 22px; }
.tp .modal-footer { padding: 14px 22px; background: #fbfcfe; border-top: 1px solid var(--line); }
.tp .form-label { margin-bottom: 6px; color: #34415a; font-size: .8rem; font-weight: 700; }
.tp .form-control, .tp .form-select { min-height: 42px; border-color: #dde2ec; border-radius: 9px; font-size: .86rem; box-shadow: none; }
.tp textarea.form-control { min-height: 84px; }
.tp .form-control:focus, .tp .form-select:focus { border-color: var(--o); box-shadow: 0 0 0 3px rgba(208,82,8,.1); }
.tp .form-text { color: var(--mute); font-size: .76rem; }
.tp .form-section { margin: 4px 0 -4px; padding-top: 12px; border-top: 1px solid var(--line-soft); color: var(--navy); font-size: .8rem; font-weight: 800; }
.tp-image-card { height: 100%; overflow: hidden; border: 1px solid var(--line); border-radius: 12px; background: #fff; }
.tp-image-card > img { display: block; width: 100%; height: 150px; object-fit: cover; background: var(--wash); }

/* ---------- Responsive ---------- */
@media (max-width: 1199.98px) {
    .tp-side { position: static; }
}
@media (max-width: 991.98px) {
    .tp-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .tp-strip-cell:nth-child(3) { border-left: 0; }
    .tp-strip-cell:nth-child(n+3) { border-top: 1px solid var(--line); }
    .tp-hero { grid-template-columns: 1fr; }
    .tp-hero-cover { order: -1; min-height: 210px; }
    .tp-list-head { display: none; }
    .tp-list[data-view="list"] .tp-row { grid-template-columns: 76px minmax(0, 1fr) auto; gap: 10px 14px; align-items: start; }
    .tp-list[data-view="list"] .tp-thumb { grid-row: span 2; }
    .tp-list[data-view="list"] .tp-meta { display: grid; grid-column: 2 / -1; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px 14px; }
    .tp-list[data-view="list"] .tp-cell-label { display: block; }
    .tp-list[data-view="list"] .tp-act { grid-column: 3; grid-row: 1; }
}
@media (max-width: 575.98px) {
    .tp-toolbar { padding: 12px; }
    .tp-search { max-width: none; flex-basis: 100%; }
    .tp-toolbar-end { margin-left: 0; width: 100%; justify-content: space-between; }
    .tp-panel-head, .tp-panel-body { padding: 16px; }
    .tp-hero-main { padding: 20px; }
    .tp-strip-cell { padding: 14px; }
    .tp-strip-value { font-size: 1.5rem; }
    .tp-building-head { flex-wrap: wrap; align-items: flex-start; }
    .tp-building-head > .flex-grow-1 { flex: 1 1 calc(100% - 100px); }
    .tp-building-head > .tp-building-actions { width: 100%; padding-left: 42px; }
    .tp .modal-body, .tp .modal-header, .tp .modal-footer { padding: 16px; }
}
@media (prefers-reduced-motion: reduce) {
    .tp *, .tp *::before, .tp *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; scroll-behavior: auto !important; }
}
</style>

@php
use Illuminate\Support\Str;

/* ---------- Display helpers ---------- */
$pillClass = fn ($v) => [
    'active' => 'is-success', 'available' => 'is-success',
    'under_maintenance' => 'is-warning', 'maintenance' => 'is-warning',
    'occupied' => 'is-navy', 'reserved' => 'is-info',
][$v] ?? '';

$label = fn ($v) => ucfirst(str_replace('_', ' ', (string) $v));
$money = fn ($unit, $amount) => $unit->currency . ' '
    . number_format((float) $amount, $unit->currency === 'RWF' ? 0 : 2);

$location = collect([
    $property->address, $property->village, $property->cell,
    $property->sector, $property->district, $property->province, $property->country,
])->filter()->implode(', ');

/* ---------- Structure numbers ---------- */
$buildings          = $property->buildings;
$activeBuildings    = $buildings->where('status', 'active');
$hasActiveBuildings = $activeBuildings->isNotEmpty();

$buildingCount = $buildings->count();
$floorCount    = $buildings->sum(fn ($b) => $b->floors->count());
$unitCount     = $buildings->sum(fn ($b) => $b->units->count());

$availableUnits = $buildings->sum(fn ($b) => $b->units->where('status', 'available')->count());
$occupiedUnits  = $buildings->sum(fn ($b) => $b->units->where('status', 'occupied')->count());
$inactiveUnits  = $buildings->sum(fn ($b) => $b->units->where('status', 'inactive')->count());

$rentableUnits = max($unitCount - $inactiveUnits, 0);
$occupancy     = $rentableUnits > 0 ? (int) round($occupiedUnits / $rentableUnits * 100) : 0;

$coverImage = $property->images->firstWhere('is_primary', true) ?? $property->images->first();
$managers   = $property->activeManagers;

/* ---------- Image managers & form modals (rendered in the modals partial) ---------- */
$imageRoutes = [
    'building' => ['primary' => 'admin.properties.buildings.images.primary', 'delete' => 'admin.properties.buildings.images.destroy', 'param' => 'building'],
    'floor'    => ['primary' => 'admin.properties.floors.images.primary',    'delete' => 'admin.properties.floors.images.destroy',    'param' => 'floor'],
    'unit'     => ['primary' => 'admin.properties.units.images.primary',     'delete' => 'admin.properties.units.images.destroy',     'param' => 'unit'],
];

$imageTargets = collect();
foreach ($buildings as $b) {
    $imageTargets->push(['type' => 'building', 'parent' => $b, 'title' => $b->name]);
    foreach ($b->floors as $f) {
        $imageTargets->push(['type' => 'floor', 'parent' => $f, 'title' => $f->name . ' · ' . $b->name]);
    }
    foreach ($b->units as $u) {
        $imageTargets->push(['type' => 'unit', 'parent' => $u, 'title' => 'Unit ' . $u->unit_number . ' · ' . $b->name]);
    }
}

$buildingForms = collect([null])->concat($buildings);
$floorForms    = collect([null])->concat($buildings->flatMap->floors);
$unitForms     = collect([null])->concat($buildings->flatMap->units);

$unitTypes = ['apartment' => 'Apartment', 'studio' => 'Studio', 'house' => 'House', 'office' => 'Office', 'shop' => 'Shop', 'warehouse' => 'Warehouse', 'other' => 'Other'];
$unitStatuses = \App\Models\Unit::STATUSES;
@endphp

<div class="tp">

    {{-- ===== Top bar: breadcrumb + actions ===== --}}
    <div class="tp-top">
        <nav class="tp-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <i class="bi bi-chevron-right"></i>
            <a href="{{ route('admin.properties.index') }}">Properties</a>
            <i class="bi bi-chevron-right"></i>
            <span class="text-truncate">{{ $property->name }}</span>
        </nav>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.properties.index') }}" class="tp-btn"><i class="bi bi-arrow-left"></i> All properties</a>
            <a href="{{ route('admin.properties.edit', $property) }}" class="tp-btn is-primary"><i class="bi bi-pencil-square"></i> Edit property</a>

            <div class="dropdown">
                <button type="button" class="tp-btn is-icon" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end tp-menu">
                    @if($property->status !== 'inactive')
                    <li>
                        <form method="POST" action="{{ route('admin.properties.destroy', $property) }}"
                              onsubmit="return confirm('Deactivate this property? Its records will be kept.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="dropdown-item is-danger"><i class="bi bi-pause-circle"></i> Deactivate property</button>
                        </form>
                    </li>
                    @endif
                    <li>
                        <x-purge-button type="property" :id="$property->id" what="this property, its buildings, units"
                                        class="dropdown-item is-danger" />
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- ===== Property showcase (same layout as the public property page) ===== --}}
    <section class="tp-panel mb-4" style="padding:22px;">
        <x-property-showcase :property="$property" mode="admin"
            :edit-url="route('admin.properties.edit', $property)"
            :public-url="route('listings.show', $property)"
            unit-modal-prefix="editUnit" />
    </section>

    {{-- ===== Key numbers ===== --}}
    <div class="tp-strip">
        <div class="tp-strip-cell">
            <div class="tp-strip-label"><i class="bi bi-buildings"></i> Buildings</div>
            <div class="tp-strip-value">{{ $buildingCount }}</div>
            <div class="tp-strip-foot">{{ $activeBuildings->count() }} active</div>
        </div>
        <div class="tp-strip-cell">
            <div class="tp-strip-label"><i class="bi bi-layers"></i> Floors</div>
            <div class="tp-strip-value">{{ $floorCount }}</div>
            <div class="tp-strip-foot">Across all buildings</div>
        </div>
        <div class="tp-strip-cell">
            <div class="tp-strip-label"><i class="bi bi-door-open"></i> Rental units</div>
            <div class="tp-strip-value">{{ $unitCount }}</div>
            <div class="tp-strip-foot">{{ $availableUnits }} available now</div>
        </div>
        <div class="tp-strip-cell">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <div>
                    <div class="tp-strip-label"><i class="bi bi-key"></i> Occupancy</div>
                    <div class="tp-strip-value">{{ $occupancy }}%</div>
                    <div class="tp-strip-foot">{{ $occupiedUnits }} of {{ $rentableUnits }} rentable</div>
                </div>
                <div class="tp-ring d-none d-sm-grid" style="--p: {{ $occupancy }}" role="img" aria-label="{{ $occupancy }} percent occupied">
                    <span>{{ $occupancy }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Section nav ===== --}}
    <nav class="tp-subnav" id="tp-subnav" aria-label="Page sections">
        <a href="#property-overview" class="is-active">Overview</a>
        <a href="#property-gallery">Gallery <span class="is-mute">({{ $property->images->count() }})</span></a>
        <a href="#property-structure">Units &amp; structure <span class="is-mute">({{ $unitCount }})</span></a>
        <a href="#property-team">Team &amp; record</a>
    </nav>

    <div class="row g-4">

        {{-- ================= MAIN ================= --}}
        <div class="col-xl-8">

            {{-- Overview --}}
            <section class="tp-panel" id="property-overview">
                <div class="tp-panel-head">
                    <div>
                        <h2 class="tp-panel-title">Overview</h2>
                        <p class="tp-panel-note">Description and address details</p>
                    </div>
                    <a href="{{ route('admin.properties.edit', $property) }}" class="tp-btn is-sm"><i class="bi bi-pencil"></i> Edit details</a>
                </div>

                <div class="tp-panel-body">
                    <div class="tp-label">Description</div>
                    <p style="line-height:1.75;max-width:75ch;" class="{{ $property->description ? '' : 'is-mute' }}">
                        {{ $property->description ?: 'No description yet. Add one from Edit details so the team knows what makes this property special.' }}
                    </p>

                    <dl class="tp-dl mt-3">
                        @foreach(['province' => 'Province', 'district' => 'District', 'sector' => 'Sector', 'cell' => 'Cell', 'village' => 'Village', 'country' => 'Country'] as $field => $fieldLabel)
                            <div>
                                <dt>{{ $fieldLabel }}</dt>
                                <dd class="{{ $property->$field ? '' : 'is-mute fw-normal' }}">{{ $property->$field ?: 'Not set' }}</dd>
                            </div>
                        @endforeach
                        <div>
                            <dt>Coordinates</dt>
                            <dd class="{{ $property->latitude !== null ? '' : 'is-mute fw-normal' }}">
                                {{ $property->latitude !== null && $property->longitude !== null ? $property->latitude . ', ' . $property->longitude : 'Not set' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            {{-- Gallery --}}
            <section class="tp-panel" id="property-gallery">
                <div class="tp-panel-head">
                    <div>
                        <h2 class="tp-panel-title">Gallery</h2>
                        <p class="tp-panel-note">Photos of the property as a whole</p>
                    </div>
                    <span class="tp-pill no-dot is-navy">{{ $property->images->count() }} {{ Str::plural('photo', $property->images->count()) }}</span>
                </div>

                <div class="tp-panel-body">
                    @if($property->images->isNotEmpty())
                        <div class="tp-gallery">
                            @foreach($property->images as $image)
                                <div>
                                    <button type="button" class="tp-tile"
                                            data-bs-toggle="modal" data-bs-target="#propertyImageModal"
                                            data-image-src="{{ upload_url($image->image_path) }}"
                                            data-image-caption="{{ $image->caption ?: $property->name }}"
                                            aria-label="View image larger">
                                        <img src="{{ upload_url($image->image_path) }}" alt="{{ $image->caption ?? $property->name }}" loading="lazy">
                                        @if($image->is_primary)
                                            <span class="tp-pill no-dot is-orange"><i class="bi bi-star-fill"></i> Cover</span>
                                        @endif
                                    </button>
                                    @if($image->caption)
                                        <div class="small is-mute text-truncate mt-1">{{ $image->caption }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="tp-empty is-inline">
                            <div class="tp-empty-icon"><i class="bi bi-images"></i></div>
                            <h3>No photos yet</h3>
                            <p>Photos make this property easy to recognise at a glance.</p>
                            <a href="{{ route('admin.properties.edit', $property) }}" class="tp-btn is-primary"><i class="bi bi-upload"></i> Add photos</a>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Structure --}}
            <section class="tp-panel" id="property-structure">
                <div class="tp-panel-head">
                    <div>
                        <h2 class="tp-panel-title">Units &amp; structure</h2>
                        <p class="tp-panel-note">Buildings, floors and rental units, with images and availability</p>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="tp-btn is-sm" data-bs-toggle="modal" data-bs-target="#createBuildingModal">
                            <i class="bi bi-plus-lg"></i> Building
                        </button>
                        <button type="button" class="tp-btn is-sm" data-bs-toggle="modal" data-bs-target="#createFloorModal" @disabled(!$hasActiveBuildings)>
                            <i class="bi bi-plus-lg"></i> Floor
                        </button>
                        <button type="button" class="tp-btn is-sm is-primary" data-bs-toggle="modal" data-bs-target="#createUnitModal" @disabled(!$hasActiveBuildings)>
                            <i class="bi bi-plus-lg"></i> Rental unit
                        </button>
                    </div>
                </div>

                <div class="tp-panel-body">
                    @if($buildings->isNotEmpty())
                    {{-- Find a unit quickly --}}
                    <div class="tp-struct-bar">
                        <div class="tp-search">
                            <i class="bi bi-search"></i>
                            <input type="search" id="unit-search" class="tp-input" placeholder="Find a unit or floor" aria-label="Find a unit or floor">
                        </div>

                        <div class="tp-tabs" id="unit-status-filter" role="group" aria-label="Filter units by status">
                            <button type="button" class="tp-tab is-active" data-status="">All</button>
                            @foreach(['available' => 'Available', 'occupied' => 'Occupied', 'reserved' => 'Reserved', 'maintenance' => 'Maintenance'] as $key => $text)
                                <button type="button" class="tp-tab" data-status="{{ $key }}">{{ $text }}</button>
                            @endforeach
                        </div>

                        <div class="d-flex gap-2 ms-auto">
                            <button type="button" class="tp-btn is-sm" data-collapse-all="show"><i class="bi bi-arrows-expand"></i> Expand all</button>
                            <button type="button" class="tp-btn is-sm" data-collapse-all="hide"><i class="bi bi-arrows-collapse"></i> Collapse all</button>
                        </div>
                    </div>
                    @endif

                    @forelse($buildings as $building)
                        @php
                        $buildingImage    = $building->images->firstWhere('is_primary', true) ?? $building->images->first();
                        $buildingIsActive = $building->status === 'active';

                        $bUnits    = $building->units->count();
                        $bRentable = $bUnits - $building->units->where('status', 'inactive')->count();
                        $bOccupied = $building->units->where('status', 'occupied')->count();
                        $bPercent  = $bRentable > 0 ? (int) round($bOccupied / $bRentable * 100) : 0;

                        $groups = $building->floors->map(fn ($f) => ['floor' => $f, 'units' => $f->units]);
                        $looseUnits = $building->units->whereNull('floor_id');
                        if ($looseUnits->isNotEmpty()) {
                            $groups->push(['floor' => null, 'units' => $looseUnits]);
                        }
                        @endphp

                        <div class="tp-building" id="building-{{ $building->id }}" data-building>
                            <div class="tp-building-head">
                                <button type="button" class="tp-toggle" data-bs-toggle="collapse"
                                        data-bs-target="#buildingBody{{ $building->id }}"
                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                        aria-controls="buildingBody{{ $building->id }}"
                                        title="Show or hide {{ $building->name }}">
                                    <i class="bi bi-chevron-down"></i>
                                </button>

                                @if($buildingImage)
                                    <img src="{{ upload_url($buildingImage->image_path) }}" alt="{{ $building->name }}" class="tp-sq">
                                @else
                                    <div class="tp-sq"><i class="bi bi-buildings"></i></div>
                                @endif

                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <h3 class="tp-building-name">{{ $building->name }}</h3>
                                        <span class="tp-pill {{ $buildingIsActive ? 'is-success' : '' }}">{{ ucfirst($building->status) }}</span>
                                    </div>
                                    <div class="tp-occ">
                                        <span>{{ $building->building_code }}</span>
                                        <span>{{ $building->floors->count() }}/{{ $building->number_of_floors }} floors</span>
                                        <span class="tp-occ-bar" aria-hidden="true"><i style="width: {{ $bPercent }}%"></i></span>
                                        <span>{{ $bOccupied }} of {{ $bRentable }} occupied</span>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap gap-2 tp-building-actions">
                                    <button type="button" class="tp-btn is-sm" data-bs-toggle="modal"
                                            data-bs-target="#images-building-{{ $building->id }}" title="Building images">
                                        <i class="bi bi-images"></i> {{ $building->images->count() }}
                                    </button>
                                    <button type="button" class="tp-btn is-sm" data-bs-toggle="modal"
                                            data-bs-target="#editBuilding{{ $building->id }}" title="Edit building">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    @if($buildingIsActive)
                                    <form method="POST" action="{{ route('admin.properties.buildings.destroy', [$property, $building]) }}"
                                          onsubmit="return confirm('Deactivate this building?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="tp-btn is-sm is-icon is-danger" title="Deactivate building" aria-label="Deactivate building">
                                            <i class="bi bi-pause-circle"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>

                            <div class="collapse tp-building-body {{ $loop->first ? 'show' : '' }}" id="buildingBody{{ $building->id }}">
                                <div class="tp-building-inner">

                                    @if($buildingIsActive)
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <button type="button" class="tp-btn is-sm" data-bs-toggle="modal" data-bs-target="#createFloorModal" data-prefill-building="{{ $building->id }}">
                                            <i class="bi bi-plus-lg"></i> Add floor
                                        </button>
                                        <button type="button" class="tp-btn is-sm" data-bs-toggle="modal" data-bs-target="#createUnitModal" data-prefill-building="{{ $building->id }}">
                                            <i class="bi bi-plus-lg"></i> Add unit
                                        </button>
                                    </div>
                                    @endif

                                    @forelse($groups as $group)
                                        @php
                                        $floor = $group['floor'];
                                        $units = $group['units'];
                                        $floorIsActive = $floor ? $floor->status === 'active' : true;
                                        @endphp

                                        <div class="tp-floor" data-floor data-floor-name="{{ Str::lower($floor?->name ?? 'no floor') }}">
                                            <div class="tp-floor-head">
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <span class="tp-sq is-sm"><i class="bi {{ $floor ? 'bi-layers' : 'bi-door-open' }}"></i></span>
                                                    @if($floor)
                                                        <strong>{{ $floor->name }}</strong>
                                                        <span class="small is-mute">Floor {{ $floor->floor_number }}</span>
                                                        <span class="tp-pill {{ $floorIsActive ? 'is-success' : '' }}">{{ ucfirst($floor->status) }}</span>
                                                    @else
                                                        <strong>Units without a floor</strong>
                                                    @endif
                                                    <span class="small is-mute">{{ $units->count() }} {{ Str::plural('unit', $units->count()) }}</span>
                                                </div>

                                                <div class="d-flex flex-wrap gap-2">
                                                    @if($buildingIsActive && $floorIsActive)
                                                    <button type="button" class="tp-btn is-sm" data-bs-toggle="modal" data-bs-target="#createUnitModal"
                                                            data-prefill-building="{{ $building->id }}" data-prefill-floor="{{ $floor?->id }}">
                                                        <i class="bi bi-plus-lg"></i> Unit
                                                    </button>
                                                    @endif

                                                    @if($floor)
                                                    <button type="button" class="tp-btn is-sm" data-bs-toggle="modal" data-bs-target="#images-floor-{{ $floor->id }}" title="Floor images">
                                                        <i class="bi bi-images"></i> {{ $floor->images->count() }}
                                                    </button>
                                                    <button type="button" class="tp-btn is-sm is-icon" data-bs-toggle="modal" data-bs-target="#editFloor{{ $floor->id }}" title="Edit floor" aria-label="Edit floor">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    @if($floorIsActive)
                                                    <form method="POST" action="{{ route('admin.properties.floors.destroy', [$property, $floor]) }}"
                                                          onsubmit="return confirm('Deactivate this floor?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="tp-btn is-sm is-icon is-danger" title="Deactivate floor" aria-label="Deactivate floor">
                                                            <i class="bi bi-pause-circle"></i>
                                                        </button>
                                                    </form>
                                                    @endif
                                                    @endif
                                                </div>
                                            </div>

                                            @if($units->isNotEmpty())
                                            <div class="tp-table-wrap">
                                                <table class="tp-units">
                                                    <thead>
                                                        <tr>
                                                            <th>Unit</th>
                                                            <th>Type &amp; layout</th>
                                                            <th>Rent</th>
                                                            <th>Status</th>
                                                            <th class="t-end">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($units as $unit)
                                                        @php $unitImage = $unit->images->firstWhere('is_primary', true) ?? $unit->images->first(); @endphp

                                                        <tr data-unit data-status="{{ $unit->status }}"
                                                            data-search="{{ Str::lower($unit->unit_number . ' ' . $unit->unit_type) }}">
                                                            <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    @if($unitImage)
                                                                        <img src="{{ upload_url($unitImage->image_path) }}" alt="" class="tp-sq is-sm">
                                                                    @else
                                                                        <div class="tp-sq is-sm"><i class="bi bi-door-closed"></i></div>
                                                                    @endif
                                                                    <span class="tp-unit-no">Unit {{ $unit->unit_number }}</span>
                                                                </div>
                                                            </td>

                                                            <td>
                                                                <div class="fw-bold">{{ $label($unit->unit_type) }}</div>
                                                                <div class="small is-mute">
                                                                    {{ $unit->bedrooms }} bd, {{ $unit->bathrooms }} ba
                                                                    @if($unit->area_sqm) , {{ number_format($unit->area_sqm, 0) }} m² @endif
                                                                </div>
                                                            </td>

                                                            <td>
                                                                <div class="tp-rent">{{ $money($unit, $unit->rent_amount) }} <span class="is-mute fw-normal">/ month</span></div>
                                                                <div class="small is-mute">Deposit {{ $money($unit, $unit->deposit_amount) }}</div>
                                                            </td>

                                                            <td><span class="tp-pill {{ $pillClass($unit->status) }}">{{ $label($unit->status) }}</span></td>

                                                            <td>
                                                                <div class="d-flex justify-content-end gap-2">
                                                                    <button type="button" class="tp-btn is-sm" data-bs-toggle="modal" data-bs-target="#images-unit-{{ $unit->id }}" title="Unit images">
                                                                        <i class="bi bi-images"></i> {{ $unit->images->count() }}
                                                                    </button>
                                                                    <button type="button" class="tp-btn is-sm is-icon" data-bs-toggle="modal" data-bs-target="#editUnit{{ $unit->id }}" title="Edit unit" aria-label="Edit unit {{ $unit->unit_number }}">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </button>
                                                                    @if($unit->status !== 'inactive')
                                                                    <form method="POST" action="{{ route('admin.properties.units.destroy', [$property, $unit]) }}"
                                                                          onsubmit="return confirm('Deactivate this unit? Units with lease history cannot be deactivated using this action.')">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit" class="tp-btn is-sm is-icon is-danger" title="Deactivate unit" aria-label="Deactivate unit {{ $unit->unit_number }}">
                                                                            <i class="bi bi-pause-circle"></i>
                                                                        </button>
                                                                    </form>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @else
                                                <div class="tp-floor-empty" data-empty-floor>No units on this floor yet.</div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="tp-empty is-inline">
                                            <h3>No floors or units yet</h3>
                                            <p class="mb-0">
                                                @if($buildingIsActive) Use <strong>Add floor</strong> or <strong>Add unit</strong> above to get started.
                                                @else Activate this building to add floors and units. @endif
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="tp-empty is-inline">
                            <div class="tp-empty-icon"><i class="bi bi-buildings"></i></div>
                            <h3>No buildings yet</h3>
                            <p>Add a building first, then organise its floors and rental units.</p>
                            <button type="button" class="tp-btn is-primary" data-bs-toggle="modal" data-bs-target="#createBuildingModal">
                                <i class="bi bi-plus-lg"></i> Add first building
                            </button>
                        </div>
                    @endforelse

                    <div class="tp-no-match" id="unit-no-match">
                        <i class="bi bi-search d-block mb-2" style="font-size:1.4rem;"></i>
                        No units match your search.
                        <a href="#" id="unit-reset">Reset filters</a>
                    </div>
                </div>
            </section>
        </div>

        {{-- ================= SIDEBAR ================= --}}
        <div class="col-xl-4" id="property-team">
            <div class="tp-side">

                <section class="tp-panel">
                    <div class="tp-panel-head">
                        <div>
                            <h2 class="tp-panel-title">Managers</h2>
                            <p class="tp-panel-note">Responsible for this property</p>
                        </div>
                        <span class="tp-pill no-dot is-navy">{{ $managers->count() }}</span>
                    </div>

                    <div class="tp-panel-body">
                        @forelse($managers as $manager)
                            @php
                            $managerName = $manager->user?->name ?? 'Unnamed manager';
                            $initials = collect(explode(' ', trim($managerName)))->filter()->take(2)
                                ->map(fn ($p) => strtoupper(substr($p, 0, 1)))->implode('');
                            @endphp

                            <div class="tp-manager">
                                <div class="tp-avatar">{{ $initials ?: 'M' }}</div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-bold" style="color:var(--navy);">{{ $managerName }}</div>
                                    @if($manager->user?->email)
                                        <div class="small is-mute text-break">{{ $manager->user->email }}</div>
                                    @endif
                                    <div class="small is-mute">{{ $manager->job_title ?: 'Property manager' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="tp-empty is-inline">
                                <div class="tp-empty-icon"><i class="bi bi-people"></i></div>
                                <h3>No managers yet</h3>
                                <p class="mb-0">Assign a manager so someone is accountable for this property.</p>
                            </div>
                        @endforelse

                        <a href="{{ route('admin.properties.edit', $property) }}" class="tp-btn w-100 mt-3">
                            <i class="bi bi-person-gear"></i> Manage assignments
                        </a>
                    </div>
                </section>

                <section class="tp-panel">
                    <div class="tp-panel-head">
                        <h2 class="tp-panel-title">Record</h2>
                    </div>
                    <div class="tp-panel-body">
                        <div class="tp-record"><span>Property code</span><span>{{ $property->property_code ?: 'Not assigned' }}</span></div>
                        <div class="tp-record"><span>Status</span><span><span class="tp-pill {{ $pillClass($property->status) }}">{{ $label($property->status) }}</span></span></div>
                        <div class="tp-record"><span>Created</span><span>{{ $property->created_at?->format('d M Y, H:i') ?? 'Unavailable' }}</span></div>
                        <div class="tp-record"><span>Last updated</span><span>{{ $property->updated_at?->format('d M Y, H:i') ?? 'Unavailable' }}</span></div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    {{--
    Modals for the property details page.
    Variables come from show.blade.php: $property, $buildings, $activeBuildings, $buildingForms,
    $floorForms, $unitForms, $unitTypes, $unitStatuses, $imageTargets, $imageRoutes, $label.
--}}

{{-- Property image preview --}}
<div class="modal fade" id="propertyImageModal" tabindex="-1" aria-labelledby="propertyImageCaption" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="propertyImageCaption">Property image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img src="" alt="" id="propertyImagePreview" class="img-fluid rounded" style="max-height:75vh;">
            </div>
        </div>
    </div>
</div>

{{-- Building create / edit --}}
@foreach($buildingForms as $building)
@php
$modalId = $building ? 'editBuilding' . $building->id : 'createBuildingModal';
$v = fn ($field, $default = null) => old('_modal') === $modalId ? old($field, $default) : $default;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST"
              action="{{ $building ? route('admin.properties.buildings.update', [$property, $building]) : route('admin.properties.buildings.store', $property) }}"
              enctype="multipart/form-data" class="modal-content">
            @csrf
            @if($building) @method('PUT') @endif
            <input type="hidden" name="_modal" value="{{ $modalId }}">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ $building ? 'Edit ' . $building->name : 'Add building' }}</h5>
                    <div class="small is-mute">{{ $building ? 'Update the building details and add images.' : 'Register a new building on this property.' }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if(old('_modal') === $modalId && $errors->any())
                <div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Building code *</label>
                        <input name="building_code" class="form-control" required maxlength="50" value="{{ $v('building_code', $building?->building_code) }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Building name *</label>
                        <input name="name" class="form-control" required value="{{ $v('name', $building?->name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Number of floors *</label>
                        <input type="number" name="number_of_floors" class="form-control" min="1" max="200" required value="{{ $v('number_of_floors', $building?->number_of_floors ?? 1) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $optLabel)
                            <option value="{{ $value }}" @selected($v('status', $building?->status ?? 'active') === $value)>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $v('description', $building?->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Building images</label>
                        <input type="file" name="images[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
                        <div class="form-text">JPG, PNG or WebP, up to 5 MB each.@if($building) Manage existing images from the images button on the building.@endif</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="tp-btn" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="tp-btn is-primary">{{ $building ? 'Save changes' : 'Create building' }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- Floor create / edit --}}
@foreach($floorForms as $floor)
@php
$modalId = $floor ? 'editFloor' . $floor->id : 'createFloorModal';
$v = fn ($field, $default = null) => old('_modal') === $modalId ? old($field, $default) : $default;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST"
              action="{{ $floor ? route('admin.properties.floors.update', [$property, $floor]) : route('admin.properties.floors.store', $property) }}"
              enctype="multipart/form-data" class="modal-content">
            @csrf
            @if($floor) @method('PUT') @endif
            <input type="hidden" name="_modal" value="{{ $modalId }}">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ $floor ? 'Edit ' . $floor->name : 'Add floor' }}</h5>
                    <div class="small is-mute">{{ $floor ? 'Update the floor details and add images.' : 'Add a floor to one of your active buildings.' }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if(old('_modal') === $modalId && $errors->any())
                <div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Building *</label>
                        <select name="building_id" class="form-select" required>
                            <option value="">Select building</option>
                            @foreach($activeBuildings as $optBuilding)
                            <option value="{{ $optBuilding->id }}" @selected((string) $v('building_id', $floor?->building_id) === (string) $optBuilding->id)>
                                {{ $optBuilding->name }} ({{ $optBuilding->building_code }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Floor name *</label>
                        <input name="name" class="form-control" required value="{{ $v('name', $floor?->name) }}" placeholder="e.g. Ground Floor">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Floor number *</label>
                        <input type="number" name="floor_number" class="form-control" min="-5" max="300" required value="{{ $v('floor_number', $floor?->floor_number ?? 0) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            @foreach(['active' => 'Active', 'inactive' => 'Inactive'] as $value => $optLabel)
                            <option value="{{ $value }}" @selected($v('status', $floor?->status ?? 'active') === $value)>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $v('description', $floor?->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Floor images</label>
                        <input type="file" name="images[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
                        <div class="form-text">JPG, PNG or WebP, up to 5 MB each.@if($floor) Manage existing images from the images button on the floor.@endif</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="tp-btn" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="tp-btn is-primary">{{ $floor ? 'Save changes' : 'Create floor' }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- Unit create / edit --}}
@foreach($unitForms as $unit)
@php
$modalId = $unit ? 'editUnit' . $unit->id : 'createUnitModal';
$v = fn ($field, $default = null) => old('_modal') === $modalId ? old($field, $default) : $default;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST"
              action="{{ $unit ? route('admin.properties.units.update', [$property, $unit]) : route('admin.properties.units.store', $property) }}"
              enctype="multipart/form-data" class="modal-content">
            @csrf
            @if($unit) @method('PUT') @endif
            <input type="hidden" name="_modal" value="{{ $modalId }}">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ $unit ? 'Edit unit ' . $unit->unit_number : 'Add rental unit' }}</h5>
                    <div class="small is-mute">{{ $unit ? 'Update pricing, availability and details.' : 'Register a unit that can be rented out.' }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if(old('_modal') === $modalId && $errors->any())
                <div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <div class="row g-3">
                    <div class="col-12 form-section">Location in the property</div>

                    <div class="col-md-6">
                        <label class="form-label">Building *</label>
                        <select name="building_id" class="form-select unit-building-select" required>
                            <option value="">Select building</option>
                            @foreach($activeBuildings as $optBuilding)
                            <option value="{{ $optBuilding->id }}" @selected((string) $v('building_id', $unit?->building_id) === (string) $optBuilding->id)>
                                {{ $optBuilding->name }} ({{ $optBuilding->building_code }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Floor</label>
                        <select name="floor_id" class="form-select unit-floor-select">
                            <option value="">No specific floor</option>
                            @foreach($activeBuildings as $optBuilding)
                            @foreach($optBuilding->floors as $optFloor)
                            <option value="{{ $optFloor->id }}" data-building-id="{{ $optBuilding->id }}"
                                @selected((string) $v('floor_id', $unit?->floor_id) === (string) $optFloor->id)>
                                {{ $optFloor->name }} — {{ $optBuilding->name }}
                            </option>
                            @endforeach
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 form-section">Unit details</div>

                    <div class="col-md-4">
                        <label class="form-label">Unit number *</label>
                        <input name="unit_number" class="form-control" required maxlength="50" value="{{ $v('unit_number', $unit?->unit_number) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unit type *</label>
                        <select name="unit_type" class="form-select" required>
                            @foreach($unitTypes as $value => $optLabel)
                            <option value="{{ $value }}" @selected($v('unit_type', $unit?->unit_type ?? 'apartment') === $value)>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Availability *</label>
                        <select name="status" class="form-select" required>
                            @foreach($unitStatuses as $value => $optLabel)
                            <option value="{{ $value }}" @selected($v('status', $unit?->status ?? 'available') === $value)>{{ $optLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Bedrooms *</label>
                        <input type="number" name="bedrooms" class="form-control" min="0" max="50" required value="{{ $v('bedrooms', $unit?->bedrooms ?? 0) }}">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label">Bathrooms *</label>
                        <input type="number" name="bathrooms" class="form-control" min="0" max="50" required value="{{ $v('bathrooms', $unit?->bathrooms ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Area (m²)</label>
                        <input type="number" name="area_sqm" class="form-control" min="0" step="0.01" value="{{ $v('area_sqm', $unit?->area_sqm) }}">
                    </div>

                    <div class="col-12 form-section">Pricing</div>

                    <div class="col-md-4">
                        <label class="form-label">Currency *</label>
                        <select name="currency" class="form-select" required>
                            @foreach(['RWF', 'USD', 'EUR'] as $currency)
                            <option value="{{ $currency }}" @selected($v('currency', $unit?->currency ?? 'RWF') === $currency)>{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Monthly rent *</label>
                        <input type="number" name="rent_amount" class="form-control" min="0" step="0.01" required value="{{ $v('rent_amount', $unit?->rent_amount ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Security deposit *</label>
                        <input type="number" name="deposit_amount" class="form-control" min="0" step="0.01" required value="{{ $v('deposit_amount', $unit?->deposit_amount ?? 0) }}">
                    </div>

                    <div class="col-12 form-section">Extras</div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $v('description', $unit?->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Key features <small class="text-muted fw-normal">(one per line — shown on the property page)</small></label>
                        <textarea name="features" class="form-control" rows="3" placeholder="Spacious living room&#10;Modern kitchen&#10;Balcony with great view">{{ $v('features', implode(PHP_EOL, $unit?->features ?? [])) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Electricity meter no.</label>
                        <input name="electricity_meter" class="form-control" maxlength="60" value="{{ $v('electricity_meter', $unit?->electricity_meter) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Water meter no.</label>
                        <input name="water_meter" class="form-control" maxlength="60" value="{{ $v('water_meter', $unit?->water_meter) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Inventory <small class="text-muted fw-normal">(furniture, appliances and fittings handed over)</small></label>
                        <textarea name="inventory" class="form-control" rows="2">{{ $v('inventory', $unit?->inventory) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Unit images</label>
                        <input type="file" name="images[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
                        <div class="form-text">JPG, PNG or WebP, up to 5 MB each.@if($unit) Manage existing images from the images button on the unit.@endif</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="tp-btn" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="tp-btn is-primary">{{ $unit ? 'Save changes' : 'Create unit' }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- Image managers: buildings, floors, units --}}
@foreach($imageTargets as $target)
@php
$config = $imageRoutes[$target['type']];
$parent = $target['parent'];
@endphp

<div class="modal fade" id="images-{{ $target['type'] }}-{{ $parent->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ $label($target['type']) }} images</h5>
                    <div class="small is-mute">{{ $target['title'] }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    @forelse($parent->images as $image)
                    <div class="col-6 col-md-4">
                        <div class="tp-image-card">
                            <img src="{{ upload_url($image->image_path) }}" alt="{{ $image->caption ?? $target['title'] }}" loading="lazy">

                            <div class="p-2">
                                @if($image->is_primary)
                                <span class="tp-pill no-dot is-orange mb-2"><i class="bi bi-star-fill"></i> Cover</span>
                                @endif

                                @if($image->caption)
                                <div class="small mb-2">{{ $image->caption }}</div>
                                @endif

                                <div class="d-flex flex-wrap gap-2">
                                    @if(!$image->is_primary)
                                    <form method="POST" action="{{ route($config['primary'], ['property' => $property->id, $config['param'] => $parent->id, 'image' => $image->id]) }}">
                                        @csrf @method('PUT')
                                        <button type="submit" class="tp-btn is-sm">Make cover</button>
                                    </form>
                                    @endif

                                    <form method="POST" action="{{ route($config['delete'], ['property' => $property->id, $config['param'] => $parent->id, 'image' => $image->id]) }}"
                                          onsubmit="return confirm('Delete this image permanently?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="tp-btn is-sm is-danger"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="tp-empty is-inline">
                            <div class="tp-empty-icon"><i class="bi bi-images"></i></div>
                            <h3>No images yet</h3>
                            <p class="mb-0">Upload photos from the edit form for this {{ $target['type'] }}.</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="tp-btn" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var page = document.querySelector('.tp');
    if (!page) return;

    /* ---- Unit forms: only offer floors from the chosen building ---- */
    page.querySelectorAll('.unit-building-select').forEach(function (buildingSelect) {
        var modal = buildingSelect.closest('.modal');
        var floorSelect = modal ? modal.querySelector('.unit-floor-select') : null;
        if (!floorSelect) return;

        function filterFloors() {
            Array.from(floorSelect.options).forEach(function (option) {
                if (!option.value) return;
                var matches = option.dataset.buildingId === buildingSelect.value;
                option.hidden = !matches;
                option.disabled = !matches;
                if (!matches && option.selected) floorSelect.value = '';
            });
        }
        buildingSelect.addEventListener('change', filterFloors);
        filterFloors();
    });

    /* ---- Create modals: prefill from the button that opened them ---- */
    ['createFloorModal', 'createUnitModal'].forEach(function (id) {
        var modal = document.getElementById(id);
        if (!modal) return;

        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return; // reopened after a validation error: keep old input

            var form = modal.querySelector('form');
            if (form) form.reset();

            var buildingSelect = modal.querySelector('select[name="building_id"]');
            var floorSelect = modal.querySelector('select[name="floor_id"]');

            if (buildingSelect) {
                if (trigger.dataset.prefillBuilding) buildingSelect.value = trigger.dataset.prefillBuilding;
                buildingSelect.dispatchEvent(new Event('change'));
            }
            if (floorSelect && trigger.dataset.prefillFloor) floorSelect.value = trigger.dataset.prefillFloor;
        });
    });

    /* ---- Expand / collapse all buildings ---- */
    function setAll(show) {
        page.querySelectorAll('.tp-building-body').forEach(function (panel) {
            var instance = bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false });
            show ? instance.show() : instance.hide();
        });
    }
    page.querySelectorAll('[data-collapse-all]').forEach(function (button) {
        button.addEventListener('click', function () { setAll(button.dataset.collapseAll === 'show'); });
    });

    /* ---- Find units: text search + status chips ---- */
    var search = document.getElementById('unit-search');
    var chips = document.querySelectorAll('#unit-status-filter [data-status]');
    var noMatch = document.getElementById('unit-no-match');
    var activeStatus = '';

    function applyUnitFilter() {
        var term = search ? search.value.trim().toLowerCase() : '';
        var filtering = term !== '' || activeStatus !== '';
        var totalVisible = 0;

        page.querySelectorAll('[data-building]').forEach(function (building) {
            var buildingVisible = 0;

            building.querySelectorAll('[data-floor]').forEach(function (floor) {
                var floorHit = term !== '' && (floor.dataset.floorName || '').indexOf(term) !== -1;
                var rows = floor.querySelectorAll('[data-unit]');
                var floorVisible = 0;

                rows.forEach(function (row) {
                    var textOk = term === '' || floorHit || (row.dataset.search || '').indexOf(term) !== -1;
                    var statusOk = activeStatus === '' || row.dataset.status === activeStatus;
                    var show = textOk && statusOk;
                    row.hidden = !show;
                    if (show) floorVisible++;
                });

                // Floors with no units stay visible only when nothing is being filtered.
                floor.hidden = filtering && floorVisible === 0;
                buildingVisible += floorVisible;
            });

            building.hidden = filtering && buildingVisible === 0;
            totalVisible += buildingVisible;
        });

        if (noMatch) noMatch.classList.toggle('is-visible', filtering && totalVisible === 0);
        if (filtering) setAll(true);
    }

    if (search) search.addEventListener('input', applyUnitFilter);
    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            activeStatus = chip.dataset.status;
            chips.forEach(function (c) { c.classList.toggle('is-active', c === chip); });
            applyUnitFilter();
        });
    });

    var reset = document.getElementById('unit-reset');
    if (reset) reset.addEventListener('click', function (e) {
        e.preventDefault();
        if (search) search.value = '';
        activeStatus = '';
        chips.forEach(function (c) { c.classList.toggle('is-active', c.dataset.status === ''); });
        applyUnitFilter();
    });

    /* ---- Image preview modal ---- */
    var preview = document.getElementById('propertyImageModal');
    if (preview) {
        preview.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return;
            var image = document.getElementById('propertyImagePreview');
            image.src = trigger.dataset.imageSrc || '';
            image.alt = trigger.dataset.imageCaption || '';
            document.getElementById('propertyImageCaption').textContent = trigger.dataset.imageCaption || 'Property image';
        });
    }

    /* ---- Section nav highlights the section in view ---- */
    var links = Array.from(document.querySelectorAll('#tp-subnav a'));
    var targets = links.map(function (a) { return document.querySelector(a.getAttribute('href')); });
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                links.forEach(function (a) {
                    a.classList.toggle('is-active', a.getAttribute('href') === '#' + entry.target.id);
                });
            });
        }, { rootMargin: '-25% 0px -65% 0px' });
        targets.forEach(function (t) { if (t) observer.observe(t); });
    }

    /* ---- Reopen the modal that failed validation ---- */
    @if(old('_modal'))
    var failedModal = document.getElementById(@json(old('_modal')));
    if (failedModal) bootstrap.Modal.getOrCreateInstance(failedModal).show();
    @endif
});
</script>
@endsection
