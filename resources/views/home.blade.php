@extends('layouts.base')

@section('title', 'Terra Smart Rental Management | Find a Rental, Manage Your Property')

@section('meta_description', 'Browse available rental homes and apply online, or manage leases, rent, expenses and maintenance for your properties with Terra Smart Rental Management.')
@section('og_title', 'Terra Smart Rental Management')
@section('og_description', 'Find a rental home and apply online, or manage your properties, rent and maintenance in one place.')
@section('twitter_title', 'Terra Smart Rental Management')
@section('twitter_description', 'Find a rental home and apply online, or manage your properties, rent and maintenance in one place.')
@section('canonical_url', url('/'))
@section('og_url', url('/'))
@section('og_type', 'website')

@push('styles')
<style>
    .th-home {
        color: var(--tr-ink);
        overflow-x: clip;
    }

    .th-home a {
        text-decoration: none;
    }

    .th-block {
        padding: 88px 0;
    }

    .th-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--tr-orange);
        font-size: .74rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        margin-bottom: 14px;
    }

    .th-eyebrow::before {
        content: '';
        width: 26px;
        height: 2px;
        background: var(--tr-orange);
        border-radius: 4px;
    }

    .th-title {
        color: var(--tr-navy);
        font-size: clamp(1.7rem, 3vw, 2.5rem);
        font-weight: 800;
        letter-spacing: -.05em;
        line-height: 1.18;
        margin: 0 0 12px;
    }

    .th-lead {
        max-width: 60ch;
        color: var(--tr-muted);
        font-size: .96rem;
        line-height: 1.85;
        margin: 0;
    }

    /* Extra button variants (base provides .tr-btn / .tr-btn-line) */
    .tr-btn-light,
    .tr-btn-ghost {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 11px;
        padding: 12px 22px;
        font-weight: 700;
        font-size: .9rem;
    }

    .tr-btn-light {
        background: #fff;
        border: 1px solid #fff;
        color: var(--tr-navy);
        box-shadow: 0 8px 22px rgba(0, 0, 0, .14);
    }

    .tr-btn-light:hover {
        background: var(--tr-orange-soft);
        color: var(--tr-orange-dark);
        transform: translateY(-2px);
    }

    .tr-btn-ghost {
        background: transparent;
        border: 1.5px solid rgba(255, 255, 255, .45);
        color: #fff;
    }

    .tr-btn-ghost:hover {
        background: rgba(255, 255, 255, .12);
        color: #fff;
        transform: translateY(-2px);
    }

    .th-home .tr-btn,
    .th-home .tr-btn-line {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: .9rem;
    }

    .th-home .btn i.bi-arrow-right,
    .th-home .btn i.bi-arrow-up-right {
        transition: transform .25s var(--tr-ease);
    }

    .th-home .btn:hover i.bi-arrow-right {
        transform: translateX(5px);
    }

    .th-home .btn:hover i.bi-arrow-up-right {
        transform: translate(3px, -3px);
    }

    /* ---------- Hero ---------- */
    .th-hero {
        position: relative;
        padding: 72px 0 96px;
        /* background:
            radial-gradient(ellipse at 0% 0%, #ffe8d8 0, transparent 45%),
            radial-gradient(ellipse at 100% 100%, #e7ebfb 0, transparent 50%),
            #fbfbfe; */
        overflow: hidden;
    }

    .th-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        /* background-image: radial-gradient(rgba(25,38,93,.09) 1px, transparent 1px); */
        background-size: 26px 26px;
        mask-image: linear-gradient(180deg, #000 0%, transparent 75%);
        -webkit-mask-image: linear-gradient(180deg, #000 0%, transparent 75%);
        pointer-events: none;
    }

    .th-hero .container {
        position: relative;
        z-index: 2;
    }

    .th-hero h1 {
        max-width: 680px;
        color: var(--tr-navy);
        font-size: clamp(2.3rem, 4.8vw, 3.9rem);
        font-weight: 800;
        line-height: 1.08;
        letter-spacing: -.06em;
        margin: 0 0 20px;
    }

    .th-hero h1 em {
        font-style: normal;
        background: linear-gradient(100deg, var(--tr-orange), #f28a4b);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .th-hero-copy {
        max-width: 520px;
        color: #687187;
        font-size: 1.02rem;
        line-height: 1.85;
        margin-bottom: 28px;
    }

    .th-search {
        background: rgba(255, 255, 255, .92);
        backdrop-filter: blur(10px);
        border: 1px solid var(--tr-line);
        border-radius: 20px;
        padding: 22px;
        box-shadow: var(--tr-shadow-lg);
        transition: box-shadow .3s ease, transform .3s var(--tr-ease);
    }

    .th-search:focus-within {
        box-shadow: 0 28px 70px rgba(208, 82, 8, .18);
        transform: translateY(-2px);
    }

    .th-search label {
        display: block;
        color: var(--tr-navy);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .th-search .form-control,
    .th-search .form-select {
        min-height: 46px;
        font-size: .88rem;
    }

    .th-field {
        position: relative;
    }

    .th-field>i {
        position: absolute;
        left: 14px;
        bottom: 14px;
        color: var(--tr-orange);
        pointer-events: none;
    }

    .th-field>.form-control {
        padding-left: 40px;
    }

    /* Hero visual */
    .th-visual {
        position: relative;
    }

    .th-visual-frame {
        position: relative;
        border-radius: 26px;
        overflow: hidden;
        box-shadow: 0 34px 80px rgba(25, 38, 93, .22);
        aspect-ratio: 5 / 5.3;
        background: #e6e9f2;
        transform: rotate(1.2deg);
        transition: transform .6s var(--tr-ease);
    }

    .th-visual:hover .th-visual-frame {
        transform: rotate(0deg) scale(1.01);
    }

    .th-visual-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 1.2s var(--tr-ease);
    }

    .th-visual:hover .th-visual-frame img {
        transform: scale(1.06);
    }

    .th-visual::before {
        content: "";
        position: absolute;
        inset: 26px -20px -20px 26px;
        border-radius: 26px;
        background: linear-gradient(135deg, var(--tr-orange), #f4a06b);
        opacity: .9;
        z-index: -1;
    }

    .th-float {
        position: absolute;
        background: #fff;
        border-radius: 16px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: var(--tr-shadow-lg);
        font-size: .8rem;
        z-index: 3;
        animation: thBob 6s ease-in-out infinite;
    }

    .th-float strong {
        display: block;
        color: var(--tr-navy);
        font-size: .86rem;
        line-height: 1.2;
    }

    .th-float span {
        color: var(--tr-muted);
        font-size: .72rem;
    }

    .th-float .ico {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--tr-orange-soft);
        color: var(--tr-orange);
        font-size: 1.1rem;
    }

    .th-float.one {
        left: -26px;
        bottom: 56px;
    }

    .th-float.two {
        right: -14px;
        top: 34px;
        animation-delay: -3s;
    }

    .th-float.two .ico {
        background: #e6f7ee;
        color: #0f8a4f;
    }

    @keyframes thBob {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    @media (max-width: 991.98px) {
        .th-float.one {
            left: 10px;
        }

        .th-float.two {
            right: 10px;
        }

        .th-visual::before {
            display: none;
        }
    }

    @media (max-width: 575.98px) {
        .th-float {
            display: none;
        }
    }

    /* ---------- Trust strip ---------- */
    .th-strip {
        position: relative;
        z-index: 4;
        margin-top: -48px;
    }

    .th-strip-box {
        background: var(--tr-navy);
        border-radius: 20px;
        padding: 26px 30px;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        box-shadow: 0 24px 60px rgba(25, 38, 93, .3);
        color: #fff;
    }

    .th-strip-item {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .th-strip-item i {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: rgba(255, 255, 255, .1);
        color: #ffa46b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
        transition: background .3s ease, transform .3s var(--tr-ease);
    }

    .th-strip-item:hover i {
        background: var(--tr-orange);
        color: #fff;
        transform: rotate(-8deg) scale(1.08);
    }

    .th-strip-item strong {
        display: block;
        font-size: .92rem;
        line-height: 1.25;
    }

    .th-strip-item span {
        font-size: .75rem;
        color: #b9c1e0;
    }

    @media (max-width: 991.98px) {
        .th-strip-box {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .th-strip-box {
            grid-template-columns: 1fr;
            padding: 22px;
        }
    }

    /* ---------- Rental cards ---------- */
    .th-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid var(--tr-line);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: var(--tr-shadow-sm);
        transition: transform .4s var(--tr-ease), box-shadow .4s var(--tr-ease), border-color .3s ease;
    }

    .th-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--tr-shadow-lg);
        border-color: rgba(208, 82, 8, .35);
    }

    .th-media {
        position: relative;
        aspect-ratio: 5 / 3.4;
        overflow: hidden;
        background: #e9ecf4;
    }

    .th-media>a {
        display: block;
        width: 100%;
        height: 100%;
    }

    .th-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .9s var(--tr-ease);
    }

    .th-card:hover .th-media img {
        transform: scale(1.08);
    }

    .th-media::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(180deg, rgba(17, 27, 72, .3), transparent 35%, transparent 60%, rgba(17, 27, 72, .5));
    }

    .th-nophoto {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        color: #8b93ac;
        font-size: .78rem;
        background: repeating-linear-gradient(45deg, #eef0f7, #eef0f7 12px, #e8ebf3 12px, #e8ebf3 24px);
    }

    .th-nophoto i {
        font-size: 1.9rem;
    }

    .th-tag {
        position: absolute;
        z-index: 2;
        font-size: .66rem;
        font-weight: 700;
        letter-spacing: .03em;
        padding: 4px 10px;
        border-radius: 999px;
        pointer-events: none;
    }

    .th-tag.type {
        top: 10px;
        left: 10px;
        background: rgba(255, 255, 255, .95);
        color: var(--tr-navy);
        text-transform: uppercase;
    }

    .th-tag.count {
        right: 10px;
        bottom: 10px;
        background: rgba(17, 27, 72, .75);
        backdrop-filter: blur(4px);
        color: #fff;
    }

    .th-pricebadge {
        position: absolute;
        left: 10px;
        bottom: 10px;
        z-index: 2;
        background: var(--tr-orange);
        color: #fff;
        font-weight: 800;
        font-size: .84rem;
        letter-spacing: -.02em;
        padding: 5px 11px;
        border-radius: 9px;
        box-shadow: 0 8px 18px rgba(208, 82, 8, .4);
        pointer-events: none;
    }

    .th-pricebadge small {
        font-weight: 600;
        opacity: .85;
        font-size: .66rem;
        letter-spacing: 0;
    }

    .th-body {
        flex: 1;
        padding: 15px 16px 6px;
    }

    .th-card-title {
        color: var(--tr-navy);
        font-size: .96rem;
        font-weight: 750;
        line-height: 1.4;
        margin: 0;
    }

    .th-card-title a {
        color: inherit;
        background: linear-gradient(var(--tr-orange), var(--tr-orange)) 0 100% / 0 2px no-repeat;
        transition: background-size .4s var(--tr-ease), color .2s ease;
    }

    .th-card-title a:hover {
        color: var(--tr-orange);
        background-size: 100% 2px;
    }

    .th-unit {
        color: var(--tr-muted);
        font-size: .76rem;
        margin-top: 2px;
    }

    .th-specs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin: 12px 0 10px;
    }

    .th-specs span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: var(--tr-navy-soft);
        color: var(--tr-navy);
        font-size: .72rem;
        font-weight: 600;
        padding: 4px 9px;
        border-radius: 7px;
        transition: background .2s ease;
    }

    .th-specs i {
        color: var(--tr-orange);
    }

    .th-card:hover .th-specs span {
        background: var(--tr-orange-soft);
    }

    .th-desc {
        display: -webkit-box;
        overflow: hidden;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        color: var(--tr-muted);
        font-size: .78rem;
        line-height: 1.6;
        margin: 0;
    }

    .th-foot {
        padding: 12px 16px 16px;
    }

    .th-foot .btn {
        min-height: 40px;
        padding: 9px 12px;
        font-size: .8rem;
    }

    .th-empty {
        padding: 56px 22px;
        background: #fff;
        border: 2px dashed #cfd5e6;
        border-radius: 18px;
        text-align: center;
    }

    .th-empty i {
        color: var(--tr-orange);
        font-size: 2.6rem;
        display: inline-block;
        animation: thBob 3.5s ease-in-out infinite;
    }

    /* ---------- Audience panels ---------- */
    .th-soft {
        background: var(--tr-ground);
    }

    .th-panel {
        position: relative;
        height: 100%;
        overflow: hidden;
        padding: 38px;
        background: #fff;
        border: 1px solid var(--tr-line);
        border-radius: 24px;
        box-shadow: var(--tr-shadow-sm);
        transition: transform .4s var(--tr-ease), box-shadow .4s var(--tr-ease);
    }

    .th-panel:hover {
        transform: translateY(-6px);
        box-shadow: var(--tr-shadow-lg);
    }

    .th-panel::after {
        content: "";
        position: absolute;
        right: -70px;
        top: -70px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        /* background: radial-gradient(circle, rgba(208, 82, 8, .14), transparent 68%); */
        transition: transform .6s var(--tr-ease);
    }

    .th-panel:hover::after {
        transform: scale(1.4);
    }

    .th-panel.dark {
        background: linear-gradient(135deg, var(--tr-navy-deep), var(--tr-navy));
        border-color: var(--tr-navy);
        color: #fff;
    }

    .th-panel.dark::after {
        /* background: radial-gradient(circle, rgba(208, 82, 8, .45), transparent 68%); */
    }

    .th-panel>* {
        position: relative;
        z-index: 1;
    }

    .th-panel .badge-ico {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-bottom: 18px;
        background: var(--tr-orange-soft);
        color: var(--tr-orange);
    }

    .th-panel.dark .badge-ico {
        background: rgba(255, 255, 255, .12);
        color: #ffb68e;
    }

    .th-panel h3 {
        color: var(--tr-navy);
        font-size: 1.35rem;
        font-weight: 800;
        margin-bottom: 8px;
    }

    .th-panel.dark h3 {
        color: #fff;
    }

    .th-panel p {
        color: var(--tr-muted);
        line-height: 1.75;
        margin-bottom: 18px;
    }

    .th-panel.dark p {
        color: #d3d9f0;
    }

    .th-list {
        list-style: none;
        padding: 0;
        margin: 0 0 26px;
    }

    .th-list li {
        position: relative;
        padding: 11px 0 11px 30px;
        border-top: 1px solid var(--tr-line);
        font-size: .9rem;
        font-weight: 500;
        transition: transform .25s var(--tr-ease), color .2s ease;
    }

    .th-list li:hover {
        transform: translateX(6px);
    }

    .th-list li::before {
        content: "\F26A";
        font-family: "bootstrap-icons";
        position: absolute;
        left: 0;
        top: 11px;
        color: var(--tr-orange);
    }

    .th-panel.dark .th-list li {
        border-top-color: rgba(255, 255, 255, .14);
        color: #f1f3fa;
    }

    .th-panel.dark .th-list li::before {
        color: #ffb68e;
    }

    /* ---------- Capabilities ---------- */
    @media (min-width: 992px) {
        .th-sticky {
            position: sticky;
            top: 110px;
        }
    }

    .th-cap {
        display: flex;
        gap: 20px;
        padding: 24px 22px;
        border-radius: 18px;
        border: 1px solid transparent;
        transition: background .3s ease, border-color .3s ease, transform .35s var(--tr-ease), box-shadow .35s ease;
    }

    .th-cap+.th-cap {
        margin-top: 6px;
    }

    .th-cap:hover {
        background: #fff;
        border-color: var(--tr-line);
        transform: translateX(8px);
        box-shadow: var(--tr-shadow-md);
    }

    .th-cap-ico {
        width: 52px;
        height: 52px;
        flex-shrink: 0;
        border-radius: 15px;
        background: var(--tr-orange-soft);
        color: var(--tr-orange);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        transition: background .3s ease, color .3s ease, transform .4s var(--tr-ease);
    }

    .th-cap:hover .th-cap-ico {
        background: var(--tr-orange);
        color: #fff;
        transform: rotate(-8deg) scale(1.06);
    }

    .th-cap h3 {
        color: var(--tr-navy);
        font-size: 1.02rem;
        font-weight: 800;
        margin: 2px 0 5px;
    }

    .th-cap p {
        color: var(--tr-muted);
        font-size: .89rem;
        line-height: 1.75;
        margin: 0;
        max-width: 58ch;
    }

    /* ---------- Steps ---------- */
    .th-steps {
        position: relative;
    }

    @media (min-width: 768px) {
        .th-steps::before {
            content: "";
            position: absolute;
            left: 12%;
            right: 12%;
            top: 34px;
            height: 2px;
            background: repeating-linear-gradient(90deg, var(--tr-orange) 0 8px, transparent 8px 16px);
            opacity: .45;
        }
    }

    .th-step {
        position: relative;
        text-align: center;
        padding: 0 14px;
    }

    .th-step .n {
        position: relative;
        z-index: 1;
        width: 68px;
        height: 68px;
        margin: 0 auto 18px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid var(--tr-orange);
        color: var(--tr-orange);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.15rem;
        box-shadow: 0 0 0 8px var(--tr-ground);
        transition: background .3s ease, color .3s ease, transform .4s var(--tr-ease);
    }

    .th-step:hover .n {
        background: var(--tr-orange);
        color: #fff;
        transform: scale(1.1) rotate(-6deg);
    }

    .th-step h3 {
        color: var(--tr-navy);
        font-size: 1.02rem;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .th-step p {
        color: var(--tr-muted);
        font-size: .88rem;
        line-height: 1.7;
        margin: 0 auto;
        max-width: 30ch;
    }

    /* ---------- Closing CTA ---------- */
    .th-close {
        position: relative;
        overflow: hidden;
        padding: 72px 0;
        color: #fff;
        background: linear-gradient(115deg, var(--tr-navy-deep) 0%, var(--tr-navy) 55%, #2b3d95 100%);
    }

    .th-close::before,
    .th-close::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    .th-close::before {
        width: 460px;
        height: 460px;
        right: -120px;
        top: -200px;
        background: radial-gradient(circle, rgba(208, 82, 8, .55), transparent 68%);
        animation: thBob 9s ease-in-out infinite;
    }

    .th-close::after {
        width: 320px;
        height: 320px;
        left: -100px;
        bottom: -190px;
        background: radial-gradient(circle, rgba(255, 255, 255, .1), transparent 70%);
    }

    .th-close .container {
        position: relative;
        z-index: 1;
    }

    .th-close h2 {
        color: #fff;
        font-size: clamp(1.6rem, 2.8vw, 2.3rem);
        font-weight: 800;
        letter-spacing: -.045em;
        margin: 0 0 10px;
    }

    .th-close p {
        max-width: 56ch;
        color: #d3d9f0;
        line-height: 1.8;
        margin: 0;
    }

    @media (max-width: 991.98px) {
        .th-close-actions {
            justify-content: flex-start !important;
        }
    }

    #rentals {
        background: #fff;
    }

    .th-home .alert-success {
        border-radius: 12px;
    }

    .th-home a:focus-visible,
    .th-home button:focus-visible {
        outline: 3px solid rgba(208, 82, 8, .35);
        outline-offset: 3px;
    }

    @media (max-width: 767.98px) {
        .th-block {
            padding: 56px 0;
        }

        .th-hero {
            padding: 44px 0 80px;
        }

        .th-search {
            padding: 16px;
            border-radius: 16px;
        }

        .th-panel {
            padding: 26px;
        }

        .th-close {
            padding: 52px 0;
        }
    }

    @media (prefers-reduced-motion: reduce) {

        .th-float,
        .th-empty i,
        .th-close::before {
            animation: none;
        }

        .th-card:hover,
        .th-panel:hover,
        .th-cap:hover {
            transform: none;
        }
    }
</style>
@endpush

@section('content')
<div class="th-home">

    {{-- Hero and rental search --}}
    <section class="th-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="th-eyebrow" data-aos="fade-down">A smarter rental experience</div>
                    <h1 data-aos="fade-up" data-aos-delay="60">Find your next home. <em>Manage it smarter.</em></h1>
                    <p class="th-hero-copy" data-aos="fade-up" data-aos-delay="120">
                        Terra Smart Rental Management helps tenants discover available homes and apply online,
                        while owners and managers organise properties, leases, rent and maintenance in one place.
                    </p>

                    <form method="GET" action="{{ route('rentals.index') }}" class="th-search" data-aos="fade-up" data-aos-delay="200">
                        <div class="mb-3 th-field">
                            <label for="home-search">Location, building or unit</label>
                            <i class="bi bi-geo-alt"></i>
                            <input type="search" id="home-search" name="search" class="form-control"
                                value="{{ request('search') }}" placeholder="Where would you like to live?">
                        </div>
                        <div class="row g-3 align-items-end">
                            <div class="col-6 col-md-4">
                                <label for="home-min">Minimum rent</label>
                                <input type="number" id="home-min" name="min_rent" min="0" class="form-control"
                                    value="{{ request('min_rent') }}" placeholder="Any">
                            </div>
                            <div class="col-6 col-md-4">
                                <label for="home-max">Maximum rent</label>
                                <input type="number" id="home-max" name="max_rent" min="0" class="form-control"
                                    value="{{ request('max_rent') }}" placeholder="Any">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="home-bed">Bedrooms</label>
                                <select id="home-bed" name="bedrooms" class="form-select">
                                    <option value="">Any</option>
                                    @for($i = 0; $i <= 10; $i++)
                                        <option value="{{ $i }}" @selected(request('bedrooms') !==null && (string) request('bedrooms')===(string) $i)>
                                        {{ $i === 0 ? 'Studio' : $i . ' bedroom' . ($i === 1 ? '' : 's') }}
                                        </option>
                                        @endfor
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn tr-btn w-100 mt-3">
                            <i class="bi bi-search"></i> Search rentals
                        </button>
                    </form>

                    <p class="small tr-muted mt-3 mb-0" data-aos="fade-up" data-aos-delay="260">
                        Own or manage property? <a href="#property-management" class="fw-bold">Explore Terra for owners <i class="bi bi-arrow-down-short"></i></a>
                    </p>
                </div>

                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="150">
                    <div class="th-visual">
                        <div class="th-visual-frame">
                            <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1100&q=85"
                                alt="Modern residential home available for rent" fetchpriority="high">
                        </div>
                        <div class="th-float one">
                            <div class="ico"><i class="bi bi-house-heart"></i></div>
                            <div><strong>Apply online</strong><span>Track every application</span></div>
                        </div>
                        <div class="th-float two">
                            <div class="ico"><i class="bi bi-shield-check"></i></div>
                            <div><strong>One tenant portal</strong><span>Lease, rent, repairs</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Trust strip --}}
    <div class="container th-strip" data-aos="fade-up">
        <div class="th-strip-box">
            <div class="th-strip-item"><i class="bi bi-search-heart"></i>
                <div><strong>Browse homes</strong><span>Photos, rent and details</span></div>
            </div>
            <div class="th-strip-item"><i class="bi bi-pencil-square"></i>
                <div><strong>Apply online</strong><span>No paperwork queues</span></div>
            </div>
            <div class="th-strip-item"><i class="bi bi-receipt"></i>
                <div><strong>Rent and invoices</strong><span>Clear payment records</span></div>
            </div>
            <div class="th-strip-item"><i class="bi bi-tools"></i>
                <div><strong>Maintenance</strong><span>Requests tracked to done</span></div>
            </div>
        </div>
    </div>

    {{-- Available rentals --}}
    <section class="th-block" id="rentals">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-5" data-aos="fade-up">
                <div>
                    <div class="th-eyebrow">Discover your next home</div>
                    <h2 class="th-title">Homes available to rent</h2>
                    <p class="th-lead">Explore available units, compare rental prices and apply online with Terra.</p>
                </div>
                <a href="{{ route('rentals.index') }}" class="btn tr-btn-line">View all rentals <i class="bi bi-arrow-up-right"></i></a>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(isset($units) && $units->count())
            <div class="row g-3 g-xl-4">
                @foreach($units->take(8) as $unit)
                @php
                $buildingName = $unit->building?->name ?? 'Rental property';
                $detailUrl = route('rentals.show', $unit);
                $bedrooms = (int) ($unit->bedrooms ?? 0);
                $bathrooms = (int) ($unit->bathrooms ?? 0);
                $unitImages = $unit->images ?? collect();
                @endphp
                <div class="col-6 col-lg-4 col-xl-3" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 4) * 90 }}">
                    <article class="th-card">
                        <div class="th-media">
                            @if($unit->unit_type)
                            <span class="th-tag type">{{ ucfirst(str_replace('_', ' ', $unit->unit_type)) }}</span>
                            @endif

                            <span class="th-pricebadge">
                                {{ $unit->currency ?? 'RWF' }} {{ number_format((float) ($unit->rent_amount ?? $unit->rent ?? 0)) }}
                                <small>/ mo</small>
                            </span>

                            @if($unitImages->isNotEmpty())
                            <a href="{{ $detailUrl }}" aria-label="View {{ $buildingName }}, unit {{ $unit->unit_number }}">
                                <img src="{{ upload_url($unitImages->first()->image_path) }}"
                                    alt="{{ $buildingName }}, unit {{ $unit->unit_number }}" loading="lazy">
                            </a>
                            @if($unitImages->count() > 1)
                            <span class="th-tag count" style="right:10px;bottom:10px;"><i class="bi bi-images me-1"></i>{{ $unitImages->count() }}</span>
                            @endif
                            @else
                            <div class="th-nophoto"><i class="bi bi-image"></i><span>No photos yet</span></div>
                            @endif
                        </div>

                        <div class="th-body">
                            <h3 class="th-card-title"><a href="{{ $detailUrl }}">{{ $buildingName }}</a></h3>
                            <div class="th-unit">Unit {{ $unit->unit_number }}</div>
                            <div class="th-specs">
                                <span><i class="bi bi-door-open"></i>{{ $bedrooms === 0 ? 'Studio' : $bedrooms . ' bed' . ($bedrooms === 1 ? '' : 's') }}</span>
                                <span><i class="bi bi-droplet"></i>{{ $bathrooms }} bath{{ $bathrooms === 1 ? '' : 's' }}</span>
                                @if($unit->area_sqm)
                                <span><i class="bi bi-rulers"></i>{{ $unit->area_sqm }} m²</span>
                                @endif
                            </div>
                            @if($unit->description)
                            <p class="th-desc">{{ $unit->description }}</p>
                            @endif
                        </div>
                        <div class="th-foot">
                            <a href="{{ $detailUrl }}" class="btn tr-btn-line w-100">View details &amp; apply <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </article>
                </div>
                @endforeach
            </div>

            <div class="text-center mt-5" data-aos="fade-up">
                <a href="{{ route('rentals.index') }}" class="btn tr-btn">See all available homes <i class="bi bi-arrow-right"></i></a>
            </div>
            @else
            <div class="th-empty" data-aos="zoom-in">
                <i class="bi bi-houses"></i>
                <h3 class="h5 fw-bold mt-3">No units are listed right now</h3>
                <p class="tr-muted mb-4">Check the rental directory for the latest availability.</p>
                <a href="{{ route('rentals.index') }}" class="btn tr-btn">Browse rentals</a>
            </div>
            @endif
        </div>
    </section>

    {{-- Tenant and owner panels --}}
    <section class="th-block th-soft">
        <div class="container">
            <div class="text-center mx-auto mb-5" style="max-width: 640px;" data-aos="fade-up">
                <div class="th-eyebrow">Built for your rental journey</div>
                <h2 class="th-title">One platform for both sides of a tenancy</h2>
                <p class="th-lead mx-auto">Tenants get a clear way to find and apply for a home. Owners and managers get the tools to run it.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="th-panel">
                        <div class="badge-ico"><i class="bi bi-house-door"></i></div>
                        <h3>For tenants</h3>
                        <p>Find a unit, apply online and keep your tenancy details close.</p>
                        <ul class="th-list">
                            <li>Search available rentals by budget and bedrooms</li>
                            <li>Apply online and follow your application</li>
                            <li>Access your lease information</li>
                            <li>Send maintenance requests</li>
                        </ul>
                        <a href="{{ route('rentals.index') }}" class="btn tr-btn-line">Find a home <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="th-panel dark">
                        <div class="badge-ico"><i class="bi bi-buildings"></i></div>
                        <h3>For owners and managers</h3>
                        <p>Keep buildings, tenants and finances organised in one place.</p>
                        <ul class="th-list">
                            <li>Manage buildings, units and leases</li>
                            <li>Review applications and coordinate move-ins</li>
                            <li>Track rent, invoices and expenses</li>
                            <li>Follow up on repairs and maintenance costs</li>
                        </ul>
                        <a href="{{ route('register') }}" class="btn tr-btn-light">Create an account <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Platform capabilities --}}
    <section class="th-block" id="property-management">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4" data-aos="fade-right">
                    <div class="th-sticky">
                        <div class="th-eyebrow">Rental operations</div>
                        <h2 class="th-title">Everything a rental business needs, in one system</h2>
                        <p class="th-lead mb-4">Spend less time on paperwork and more time looking after your properties and tenants.</p>
                        <a href="{{ route('register') }}" class="btn tr-btn">Get started <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-8">
                    @php
                    $caps = [
                    ['bi-buildings', 'Properties and units', 'Organise properties, buildings, floors and units, and see which units are available to rent.'],
                    ['bi-people', 'Tenants and applications', "Keep tenant records, review rental applications, coordinate move-ins and maintain each tenant's history."],
                    ['bi-file-earmark-text', 'Leases and agreements', 'Store lease terms, rental dates, deposits and renewal information together.'],
                    ['bi-receipt', 'Rent and invoicing', 'Generate rent invoices, record payments, allocate receipts and monitor outstanding balances.'],
                    ['bi-tools', 'Maintenance', 'Record repair requests, assign work, follow progress and track maintenance costs.'],
                    ['bi-graph-up-arrow', 'Expenses and reports', 'Record property expenses and review rental income, payment history and operating costs.'],
                    ];
                    @endphp
                    @foreach($caps as [$icon, $name, $text])
                    <div class="th-cap" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3) * 70 }}">
                        <div class="th-cap-ico"><i class="bi {{ $icon }}"></i></div>
                        <div>
                            <h3>{{ $name }}</h3>
                            <p>{{ $text }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="th-block th-soft">
        <div class="container">
            <div class="text-center mx-auto mb-5" style="max-width: 560px;" data-aos="fade-up">
                <div class="th-eyebrow">Simple to get started</div>
                <h2 class="th-title">How it works</h2>
                <p class="th-lead mx-auto">Three steps, whether you are renting or managing.</p>
            </div>
            <div class="row g-4 th-steps">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="th-step">
                        <div class="n">01</div>
                        <h3>Create your account</h3>
                        <p>Register and access the tools that fit your role.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="120">
                    <div class="th-step">
                        <div class="n">02</div>
                        <h3>Find a unit or add your property</h3>
                        <p>Browse available units and apply, or set up your buildings and units.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="240">
                    <div class="th-step">
                        <div class="n">03</div>
                        <h3>Keep everything in one place</h3>
                        <p>Leases, rent and maintenance records stay organised and up to date.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Closing call to action --}}
    <!-- <section class="th-close">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-8" data-aos="fade-right">
                    <h2>Ready to simplify your rentals?</h2>
                    <p>Create an account to apply for a home or start managing your properties with Terra.</p>
                </div>
                <div class="col-lg-4" data-aos="fade-left">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end th-close-actions">
                        <a href="{{ route('register') }}" class="btn tr-btn-light">Create an account</a>
                        <a href="{{ route('rentals.index') }}" class="btn tr-btn-ghost">Browse rentals</a>
                    </div>
                </div>
            </div>
        </div>
    </section> -->
</div>
@endsection