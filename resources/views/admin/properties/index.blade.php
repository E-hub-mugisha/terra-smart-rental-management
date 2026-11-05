@extends('layouts.app')

@section('title', 'Properties')
@section('page_heading', 'Properties')
@section('page_subtitle', 'Manage property records, ownership, location and assigned managers.')



@section('content')
@php
    use Illuminate\Support\Str;

    $statusMap = [
        'active'            => ['pill' => 'is-success', 'label' => 'Active'],
        'under_maintenance' => ['pill' => 'is-warning', 'label' => 'Under maintenance'],
        'inactive'          => ['pill' => '',           'label' => 'Inactive'],
    ];

    $currentStatus = request('status');
    $search        = trim((string) request('search'));
    $hasFilters    = $search !== '' || filled($currentStatus);

    /*
     | Optional: pass $stats from the controller for portfolio-wide numbers:
     |   ['total' => .., 'active' => .., 'under_maintenance' => .., 'inactive' => ..]
     | Without it, the cards fall back to counting the current page only
     | and say so, so the numbers are never misleading.
     */
    $stats = $stats ?? null;
    $page  = $properties->getCollection();

    $count = fn (string $key) => $stats[$key] ?? $page->where('status', $key)->count();
    $scope = $stats ? 'All properties' : 'On this page';

    $tabUrl = fn (?string $status) => route('admin.properties.index', array_filter([
        'search' => $search ?: null,
        'status' => $status,
    ]));
@endphp

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
.tp-crumbs i { font-size: 9px; }
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

<div class="tp">

    {{-- Heading --}}
    <div class="tp-head">
        <div>
            <h1 class="tp-title">Properties</h1>
            <p class="tp-sub">Every property in your portfolio, with its owner, location and the managers looking after it.</p>
        </div>

        <a href="{{ route('admin.properties.create') }}" class="tp-btn is-primary">
            <i class="bi bi-plus-lg"></i> Add property
        </a>
    </div>

    {{-- Summary: each cell doubles as a status filter --}}
    <div class="tp-strip">
        <a href="{{ $tabUrl(null) }}" class="tp-strip-cell is-link {{ !$currentStatus ? 'is-current' : '' }}">
            <div class="tp-strip-label"><i class="bi bi-buildings"></i> {{ $hasFilters ? 'Matching' : 'Total' }}</div>
            <div class="tp-strip-value">{{ number_format($stats['total'] ?? $properties->total()) }}</div>
            <div class="tp-strip-foot">{{ $hasFilters && !$stats ? 'Results for your filters' : 'Registered properties' }}</div>
        </a>

        <a href="{{ $tabUrl('active') }}" class="tp-strip-cell is-link {{ $currentStatus === 'active' ? 'is-current' : '' }}">
            <div class="tp-strip-label"><i class="bi bi-check-circle"></i> Active</div>
            <div class="tp-strip-value">{{ number_format($count('active')) }}</div>
            <div class="tp-strip-foot">{{ $scope }}</div>
        </a>

        <a href="{{ $tabUrl('under_maintenance') }}" class="tp-strip-cell is-link {{ $currentStatus === 'under_maintenance' ? 'is-current' : '' }}">
            <div class="tp-strip-label"><i class="bi bi-tools"></i> Under maintenance</div>
            <div class="tp-strip-value">{{ number_format($count('under_maintenance')) }}</div>
            <div class="tp-strip-foot">{{ $scope }}</div>
        </a>

        <a href="{{ $tabUrl('inactive') }}" class="tp-strip-cell is-link {{ $currentStatus === 'inactive' ? 'is-current' : '' }}">
            <div class="tp-strip-label"><i class="bi bi-pause-circle"></i> Inactive</div>
            <div class="tp-strip-value">{{ number_format($count('inactive')) }}</div>
            <div class="tp-strip-foot">{{ $scope }}</div>
        </a>
    </div>

    <section class="tp-panel">

        {{-- Toolbar: search + status tabs + view switch --}}
        <form method="GET" action="{{ route('admin.properties.index') }}" class="tp-toolbar" role="search">
            @if($currentStatus)
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif

            <div class="tp-search">
                <i class="bi bi-search"></i>
                <input type="search" name="search" id="property-search" class="tp-input"
                       value="{{ $search }}" placeholder="Search name, code, owner or district"
                       aria-label="Search properties">
                @if($search !== '')
                    <a href="{{ route('admin.properties.index', array_filter(['status' => $currentStatus])) }}"
                       class="tp-search-clear" title="Clear search" aria-label="Clear search">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </div>

            <nav class="tp-tabs" aria-label="Filter by status">
                <a href="{{ $tabUrl(null) }}" class="tp-tab {{ !$currentStatus ? 'is-active' : '' }}">All</a>
                @foreach($statusMap as $key => $meta)
                    <a href="{{ $tabUrl($key) }}" class="tp-tab {{ $currentStatus === $key ? 'is-active' : '' }}">
                        {{ $meta['label'] }}
                        @if($stats) <small>{{ number_format($stats[$key] ?? 0) }}</small> @endif
                    </a>
                @endforeach
            </nav>

            <div class="tp-toolbar-end">
                <span class="tp-count">
                    {{ number_format($properties->total()) }} {{ Str::plural('property', $properties->total()) }}
                </span>

                <div class="tp-tabs" role="group" aria-label="Change layout">
                    <button type="button" class="tp-tab" data-view-btn="list" title="List view" aria-label="List view">
                        <i class="bi bi-list-ul"></i>
                    </button>
                    <button type="button" class="tp-tab" data-view-btn="grid" title="Grid view" aria-label="Grid view">
                        <i class="bi bi-grid"></i>
                    </button>
                </div>
            </div>
        </form>

        @if($hasFilters)
            <div class="tp-filter-note">
                <i class="bi bi-funnel"></i>
                <span>
                    Showing
                    @if($search !== '') results for <strong>“{{ $search }}”</strong> @endif
                    @if($search !== '' && $currentStatus) in @endif
                    @if($currentStatus) <strong>{{ $statusMap[$currentStatus]['label'] ?? Str::headline($currentStatus) }}</strong> @endif
                </span>
                <a href="{{ route('admin.properties.index') }}">Clear all</a>
            </div>
        @endif

        {{-- Directory --}}
        @if($properties->count())
            <div class="tp-list" id="property-list" data-view="list">
                <div class="tp-list-head" aria-hidden="true">
                    <span></span>
                    <span>Property</span>
                    <span>Owner</span>
                    <span>Location</span>
                    <span>Managers</span>
                    <span>Status</span>
                    <span class="t-end">Actions</span>
                </div>

                @foreach($properties as $property)
                    @php
                        $status = $property->status ?? 'inactive';
                        $meta   = $statusMap[$status] ?? $statusMap['inactive'];

                        $managerCount = $property->activeManagers->count();
                        $image        = $property->images->firstWhere('is_primary', true) ?? $property->images->first();
                        $imageCount   = $property->images->count();
                        $name         = $property->name ?: 'Unnamed property';
                    @endphp

                    <article class="tp-row">
                        <a href="{{ route('admin.properties.show', $property) }}" class="tp-thumb" tabindex="-1" aria-hidden="true">
                            @if($image)
                                <img src="{{ asset('storage/' . $image->image_path) }}"
                                     alt="" loading="lazy">
                                @if($imageCount > 1)
                                    <span class="tp-thumb-count"><i class="bi bi-images"></i> {{ $imageCount }}</span>
                                @endif
                            @else
                                <i class="bi bi-building"></i>
                            @endif
                        </a>

                        <div class="tp-main min-w-0">
                            <a href="{{ route('admin.properties.show', $property) }}" class="tp-name">{{ $name }}</a>
                            <div class="tp-sub-line">
                                {{ $property->property_code ?: 'No code' }}
                                &nbsp;&bull;&nbsp;
                                {{ Str::headline($property->property_type ?? 'Property') }}
                            </div>
                        </div>

                        <div class="tp-meta">
                            <div class="tp-cell">
                                <div class="tp-cell-label">Owner</div>
                                <div class="tp-cell-strong">{{ $property->owner?->name ?? 'Owner unavailable' }}</div>
                            </div>

                            <div class="tp-cell">
                                <div class="tp-cell-label">Location</div>
                                <div class="tp-cell-strong">{{ $property->district ?: 'District not set' }}</div>
                                <div class="tp-sub-line">{{ $property->province ?: 'Province not set' }}</div>
                            </div>

                            <div class="tp-cell">
                                <div class="tp-cell-label">Managers</div>
                                <span class="tp-pill no-dot {{ $managerCount ? 'is-navy' : '' }}">
                                    <i class="bi bi-people"></i>
                                    {{ $managerCount ?: 'None' }}
                                </span>
                            </div>

                            <div class="tp-cell">
                                <div class="tp-cell-label">Status</div>
                                <span class="tp-pill {{ $meta['pill'] }}">{{ $meta['label'] }}</span>
                            </div>
                        </div>

                        <div class="tp-act">
                            <a href="{{ route('admin.properties.show', $property) }}" class="tp-btn is-sm">View</a>

                            <div class="dropdown">
                                <button type="button" class="tp-btn is-sm is-icon"
                                        data-bs-toggle="dropdown" data-bs-boundary="viewport"
                                        aria-expanded="false"
                                        aria-label="More actions for {{ $name }}">
                                    <i class="bi bi-three-dots"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end tp-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.properties.edit', $property) }}">
                                            <i class="bi bi-pencil-square"></i> Edit property
                                        </a>
                                    </li>

                                    @if($status !== 'inactive')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST"
                                                  action="{{ route('admin.properties.destroy', $property) }}"
                                                  onsubmit="return confirm('Deactivate {{ e(addslashes($name)) }}? Its records will be kept.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item is-danger">
                                                    <i class="bi bi-pause-circle"></i> Deactivate
                                                </button>
                                            </form>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="tp-foot">
                <div class="tp-count">
                    Showing <strong>{{ $properties->firstItem() }}</strong>–<strong>{{ $properties->lastItem() }}</strong>
                    of <strong>{{ number_format($properties->total()) }}</strong>
                </div>

                @if($properties->hasPages())
                    {{ $properties->withQueryString()->links('pagination::bootstrap-5') }}
                @endif
            </div>
        @else
            <div class="tp-empty">
                <div class="tp-empty-icon"><i class="bi {{ $hasFilters ? 'bi-search' : 'bi-buildings' }}"></i></div>

                <h3>{{ $hasFilters ? 'No properties match your filters' : 'No properties yet' }}</h3>
                <p>
                    {{ $hasFilters
                        ? 'Try a different keyword, or clear the filters to see everything.'
                        : 'Add your first property to start organising buildings, floors and rental units.' }}
                </p>

                @if($hasFilters)
                    <a href="{{ route('admin.properties.index') }}" class="tp-btn">
                        <i class="bi bi-arrow-counterclockwise"></i> Clear filters
                    </a>
                @else
                    <a href="{{ route('admin.properties.create') }}" class="tp-btn is-primary">
                        <i class="bi bi-plus-lg"></i> Add property
                    </a>
                @endif
            </div>
        @endif
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var list = document.getElementById('property-list');
    var buttons = document.querySelectorAll('[data-view-btn]');
    if (!list || !buttons.length) return;

    var KEY = 'terra.properties.view';

    function apply(view) {
        list.dataset.view = view;
        buttons.forEach(function (b) {
            var on = b.dataset.viewBtn === view;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    var saved = 'list';
    try { saved = localStorage.getItem(KEY) || 'list'; } catch (e) {}
    apply(saved === 'grid' ? 'grid' : 'list');

    buttons.forEach(function (b) {
        b.addEventListener('click', function () {
            apply(b.dataset.viewBtn);
            try { localStorage.setItem(KEY, b.dataset.viewBtn); } catch (e) {}
        });
    });
});
</script>
@endsection