
@extends('layouts.app')

@section('title', 'Edit Property Manager')
@section('page_heading', 'Edit Property Manager')
@section('page_subtitle', 'Update the manager profile, account credentials and employment details.')

@section('content')
<div class="manager-page">

    <style>
        .manager-page {
            --mp-orange: #D05208;
            --mp-navy: #19265d;
            --mp-muted: #687386;
        }

        .manager-page .mp-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--mp-muted);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 20px;
        }

        .manager-page .mp-back:hover {
            color: var(--mp-orange);
        }

        .manager-page .mp-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 28px;
        }

        .manager-page .mp-eyebrow {
            color: var(--mp-orange);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .12em;
            margin-bottom: 9px;
        }

        .manager-page .mp-title {
            color: var(--mp-navy);
            font-size: clamp(23px, 3vw, 30px);
            font-weight: 800;
            letter-spacing: -.7px;
            margin-bottom: 8px;
        }

        .manager-page .mp-description {
            color: var(--mp-muted);
            font-size: 14px;
            line-height: 1.7;
            margin: 0;
            max-width: 620px;
        }

        .manager-page .mp-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            padding: 10px 14px;
            border: 1px solid #e0e5f1;
            border-radius: 12px;
            background: #f5f7fc;
            color: var(--mp-navy);
            font-size: 12px;
            font-weight: 750;
        }

        .manager-page .mp-progress {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            border: 1px solid #e6eaf2;
            border-radius: 14px;
            background: #fff;
        }

        .manager-page .mp-progress-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            border-radius: 11px;
            background: #edf0fa;
            color: var(--mp-navy);
        }

        .manager-page .mp-progress-title {
            color: var(--mp-navy);
            font-size: 13px;
            font-weight: 750;
            margin-bottom: 3px;
        }

        .manager-page .mp-progress-text {
            color: var(--mp-muted);
            font-size: 12px;
            margin: 0;
        }

        @media (max-width: 575.98px) {
            .manager-page .mp-heading {
                flex-direction: column;
            }

            .manager-page .mp-badge {
                align-self: flex-start;
                max-width: 100%;
                overflow-wrap: anywhere;
            }

            .manager-page .mp-progress {
                padding: 14px;
            }
        }
    </style>

    <a href="{{ route('admin.managers.index') }}" class="mp-back">
        <i class="bi bi-arrow-left"></i>
        Back to property managers
    </a>

    <div class="mp-heading">
        <div>
            <div class="mp-eyebrow">Team administration</div>
            <h1 class="mp-title">Edit property manager</h1>
            <p class="mp-description">
                Update {{ $manager->user?->name ?? 'this manager' }}'s profile,
                employment details and account status.
            </p>
        </div>

        <div class="mp-badge">
            <i class="bi bi-person-badge"></i>
            {{ $manager->manager_number ?: 'Manager profile' }}
        </div>
    </div>

    <div class="mp-progress">
        <div class="mp-progress-icon">
            <i class="bi bi-pencil-square fs-5"></i>
        </div>
        <div>
            <div class="mp-progress-title">Update manager information</div>
            <p class="mp-progress-text">
                Review the existing details and save any changes you make.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.managers.update', $manager) }}">
        @csrf
        @method('PUT')

        @include('admin.managers._form')
    </form>

</div>
@endsection
