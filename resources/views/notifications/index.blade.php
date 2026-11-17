@extends('layouts.app')

@section('title', 'Notifications')
@section('page_heading', 'Notifications')
@section('page_subtitle', 'Rent, payment, lease, maintenance and message alerts in one place.')

@push('styles')
<style>
    .tn-card { background: var(--terra-surface); border: 1px solid var(--terra-border); border-radius: var(--terra-radius); box-shadow: var(--terra-shadow); }
    .tn-head { padding: 14px 18px; border-bottom: 1px solid var(--terra-border); display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
    .tn-tabs { display: flex; flex-wrap: wrap; gap: 6px; }
    .tn-tabs a { font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 999px; color: var(--terra-navy); text-decoration: none; border: 1px solid var(--terra-border); }
    .tn-tabs a.is-active { background: var(--terra-navy); border-color: var(--terra-navy); color: #fff; }
    .tn-item { display: flex; gap: 14px; padding: 14px 18px; border-bottom: 1px solid #eff1f6; align-items: flex-start; }
    .tn-item:last-child { border-bottom: 0; }
    .tn-item.is-unread { background: #fffaf6; }
    .tn-icon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; flex: none; font-size: 1.1rem; }
    .tn-icon.tone-info { background: #e7f4fd; color: #116a9c; }
    .tn-icon.tone-success { background: #e8f7ee; color: #147847; }
    .tn-icon.tone-warning { background: #fff3dc; color: #8f5a08; }
    .tn-icon.tone-danger { background: #fff1f0; color: #b42318; }
    .tn-title { font-weight: 700; color: var(--terra-navy); text-decoration: none; }
    .tn-title:hover { color: var(--terra-orange); }
    .tn-text { color: var(--terra-text); font-size: 13.5px; margin: 2px 0 4px; }
    .tn-time { font-size: 11.5px; color: var(--terra-muted); }
    .tn-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--terra-orange); display: inline-block; margin-left: 6px; vertical-align: middle; }
    .tn-actions .btn { padding: 2px 8px; }
    .tn-empty { padding: 50px 20px; text-align: center; color: var(--terra-muted); }
    .tn-empty i { font-size: 2.4rem; display: block; margin-bottom: 8px; opacity: .6; }
    .tn-pref { padding: 18px; }
    .tn-pref .form-check { padding: 12px 12px 12px 44px; border: 1px solid var(--terra-border); border-radius: 12px; margin-bottom: 10px; }
    .tn-pref .form-check-input { margin-left: -32px; margin-top: .3em; }
</style>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="tn-card">
            <div class="tn-head">
                <nav class="tn-tabs" aria-label="Filter notifications">
                    <a href="{{ route('notifications.index', array_filter(['category' => $category])) }}" class="{{ $filter !== 'unread' ? 'is-active' : '' }}">All</a>
                    <a href="{{ route('notifications.index', array_filter(['filter' => 'unread', 'category' => $category])) }}" class="{{ $filter === 'unread' ? 'is-active' : '' }}">
                        Unread @if($unreadCount)<span class="badge rounded-pill text-bg-warning ms-1">{{ $unreadCount }}</span>@endif
                    </a>
                    <span class="mx-1 border-start"></span>
                    @foreach($categories as $key => [$label])
                        <a href="{{ route('notifications.index', array_filter(['filter' => $filter !== 'all' ? $filter : null, 'category' => $category === $key ? null : $key])) }}"
                           class="{{ $category === $key ? 'is-active' : '' }}">{{ $label }}</a>
                    @endforeach
                </nav>
                @if($unreadCount)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light fw-semibold"><i class="bi bi-check2-all me-1"></i> Mark all read</button>
                    </form>
                @endif
            </div>

            @forelse($notifications as $n)
                @php $d = (array) $n->data; @endphp
                <div class="tn-item {{ $n->read_at ? '' : 'is-unread' }}">
                    <span class="tn-icon tone-{{ $d['tone'] ?? 'info' }}"><i class="bi {{ \App\Http\Controllers\NotificationController::iconFor($d) }}"></i></span>
                    <div class="flex-grow-1 min-w-0">
                        <a href="{{ route('notifications.open', $n->id) }}" class="tn-title">{{ $d['title'] ?? 'Notification' }}</a>
                        @unless($n->read_at)<span class="tn-dot" aria-label="Unread"></span>@endunless
                        <div class="tn-text">{{ $d['message'] ?? '' }}</div>
                        <div class="tn-time">{{ $n->created_at->diffForHumans() }} · {{ $n->created_at->format('d M Y, H:i') }}</div>
                    </div>
                    <form method="POST" action="{{ route('notifications.destroy', $n->id) }}" class="tn-actions">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-light" title="Remove" aria-label="Remove notification"><i class="bi bi-x-lg"></i></button>
                    </form>
                </div>
            @empty
                <div class="tn-empty">
                    <i class="bi bi-bell-slash"></i>
                    {{ $filter === 'unread' ? 'You are all caught up.' : 'No notifications yet.' }}
                </div>
            @endforelse

            @if($notifications->hasPages())
                <div class="p-3 border-top">{{ $notifications->links() }}</div>
            @endif
        </div>
    </div>

    <div class="col-xl-4">
        <div class="tn-card">
            <div class="tn-head"><strong style="color:var(--terra-navy);"><i class="bi bi-sliders me-1"></i> How we contact you</strong></div>
            <form method="POST" action="{{ route('notifications.preferences') }}" class="tn-pref">
                @csrf @method('PUT')
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" checked disabled id="prefApp">
                    <label class="form-check-label" for="prefApp"><strong>In-app</strong><br><small class="text-secondary">Always on — the bell at the top of every page.</small></label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="notify_email" value="1" id="prefEmail" @checked(auth()->user()->notify_email)>
                    <label class="form-check-label" for="prefEmail"><strong>Email</strong><br><small class="text-secondary">Sent to {{ auth()->user()->email }}</small></label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="notify_sms" value="1" id="prefSms" @checked(auth()->user()->notify_sms) @disabled(! $smsAvailable)>
                    <label class="form-check-label" for="prefSms"><strong>SMS</strong><br>
                        <small class="text-secondary">{{ $smsAvailable ? 'Rent due, overdue and payment confirmations.' : 'Coming soon.' }}</small></label>
                </div>
                <button type="submit" class="btn btn-primary rounded-pill w-100 fw-semibold mt-1" style="background:var(--terra-orange);border-color:var(--terra-orange);">Save preferences</button>
            </form>
        </div>
    </div>
</div>
@endsection
