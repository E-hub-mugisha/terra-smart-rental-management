@extends('layouts.app')

@section('title', $active ? $active->subject . ' | Messages' : 'Messages')
@section('page_heading', 'Messages')
@section('page_subtitle', 'Conversations and announcements linked to your properties and lease.')

@push('styles')
<style>
    .tm { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 0; background: var(--terra-surface); border: 1px solid var(--terra-border);
        border-radius: var(--terra-radius); box-shadow: var(--terra-shadow); overflow: hidden; height: calc(100vh - var(--terra-topbar-height) - 150px); min-height: 520px; }
    @media (max-width: 991.98px) {
        .tm { grid-template-columns: 1fr; height: auto; min-height: 0; }
        .tm.has-active .tm-list { display: none; }
        .tm:not(.has-active) .tm-thread { display: none; }
    }
    .tm-list { display: flex; flex-direction: column; border-right: 1px solid var(--terra-border); min-height: 0; }
    .tm-list-head { padding: 14px; border-bottom: 1px solid var(--terra-border); display: grid; gap: 10px; }
    .tm-search { position: relative; }
    .tm-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--terra-muted); }
    .tm-search input { padding-left: 34px; border-radius: 10px; min-height: 40px; }
    .tm-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
    .tm-tabs a { font-size: 12px; font-weight: 700; padding: 5px 11px; border-radius: 999px; color: var(--terra-navy); text-decoration: none; border: 1px solid var(--terra-border); }
    .tm-tabs a.is-active { background: var(--terra-navy); border-color: var(--terra-navy); color: #fff; }
    .tm-tabs .badge { font-size: 11px; }
    .tm-convs { overflow-y: auto; flex: 1; }
    .tm-conv { display: flex; gap: 11px; padding: 13px 14px; border-bottom: 1px solid #eff1f6; text-decoration: none; color: inherit; position: relative; }
    .tm-conv:hover { background: #f8f9fc; color: inherit; }
    .tm-conv.is-active { background: var(--terra-orange-light); }
    .tm-conv.is-active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--terra-orange); }
    .tm-conv-icon { width: 40px; height: 40px; border-radius: 12px; flex: none; display: grid; place-items: center; background: #eef1fb; color: var(--terra-navy); font-weight: 800; }
    .tm-conv-icon.is-announcement { background: #fff3dc; color: #8f5a08; }
    .tm-conv-body { min-width: 0; flex: 1; }
    .tm-conv-top { display: flex; justify-content: space-between; gap: 8px; }
    .tm-conv-name { font-weight: 700; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--terra-navy); }
    .tm-conv-time { font-size: 11px; color: var(--terra-muted); white-space: nowrap; }
    .tm-conv-subject { font-size: 12.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tm-conv-preview { font-size: 12px; color: var(--terra-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tm-conv.is-unread .tm-conv-subject, .tm-conv.is-unread .tm-conv-preview { color: var(--terra-text); font-weight: 700; }
    .tm-unread-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--terra-orange); flex: none; margin-top: 6px; }
    .tm-empty { padding: 40px 20px; text-align: center; color: var(--terra-muted); }
    .tm-empty i { font-size: 2.2rem; display: block; margin-bottom: 8px; opacity: .6; }

    .tm-thread { display: flex; flex-direction: column; min-height: 0; }
    .tm-thread-head { padding: 14px 18px; border-bottom: 1px solid var(--terra-border); display: flex; align-items: center; gap: 12px; }
    .tm-thread-title { font-weight: 800; color: var(--terra-navy); font-size: 1.02rem; margin: 0; line-height: 1.3; }
    .tm-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; padding: 3px 9px; border-radius: 999px; background: #f2f4f9; color: var(--terra-navy); }
    .tm-body { flex: 1; overflow-y: auto; padding: 18px; background: #fafbfd; display: flex; flex-direction: column; gap: 14px; }
    @media (max-width: 991.98px) { .tm-body { max-height: 60vh; } }
    .tm-msg { display: flex; gap: 10px; max-width: 78%; }
    .tm-msg.is-mine { align-self: flex-end; flex-direction: row-reverse; }
    .tm-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--terra-navy); color: #fff; font-size: 13px; font-weight: 700; display: grid; place-items: center; flex: none; }
    .tm-meta { font-size: 11.5px; color: var(--terra-muted); display: flex; gap: 8px; margin-bottom: 3px; }
    .tm-msg.is-mine .tm-meta { justify-content: flex-end; }
    .tm-meta strong { color: var(--terra-text); }
    .tm-bubble { background: #fff; border: 1px solid var(--terra-border); padding: 10px 14px; border-radius: 4px 14px 14px 14px; line-height: 1.55; word-wrap: break-word; overflow-wrap: anywhere; }
    .tm-msg.is-mine .tm-bubble { background: var(--terra-navy); color: #fff; border-color: var(--terra-navy); border-radius: 14px 4px 14px 14px; }
    .tm-files { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
    .tm-msg.is-mine .tm-files { justify-content: flex-end; }
    .tm-photo img { width: 160px; height: 120px; object-fit: cover; border-radius: 10px; border: 1px solid var(--terra-border); display: block; }
    .tm-file { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border: 1px solid var(--terra-border); border-radius: 10px; background: #fff; text-decoration: none; color: var(--terra-text); }
    .tm-file i { font-size: 1.4rem; color: var(--terra-orange); }
    .tm-file strong { display: block; font-size: 12.5px; }
    .tm-file small { display: block; color: var(--terra-muted); font-size: 11px; }
    .tm-compose { border-top: 1px solid var(--terra-border); padding: 12px 14px; background: #fff; }
    .tm-compose textarea { resize: none; border-radius: 12px; min-height: 46px; max-height: 160px; }
    .tm-compose-row { display: flex; gap: 8px; align-items: flex-end; }
    .tm-attach { width: 44px; height: 44px; flex: none; border-radius: 12px; border: 1px solid var(--terra-border); display: grid; place-items: center; cursor: pointer; color: var(--terra-navy); background: #fff; margin: 0; }
    .tm-attach:hover { border-color: var(--terra-orange); color: var(--terra-orange); }
    .tm-send { height: 44px; border-radius: 12px; padding: 0 18px; font-weight: 700; background: var(--terra-orange); border-color: var(--terra-orange); }
    .tm-send:hover { background: var(--terra-orange-dark); border-color: var(--terra-orange-dark); }
    .tm-picked { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .tm-picked span { font-size: 11.5px; background: #f2f4f9; border-radius: 999px; padding: 3px 10px; }
    .tm-readonly { padding: 14px 18px; border-top: 1px solid var(--terra-border); color: var(--terra-muted); font-size: 13px; background: #fff; }
    .tm-placeholder { flex: 1; display: grid; place-items: center; text-align: center; color: var(--terra-muted); padding: 30px; }
    .tm-placeholder i { font-size: 3rem; color: #c9cfdd; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
    @if($canAnnounce)
        <a href="{{ route('messages.create', ['mode' => 'announcement']) }}" class="btn btn-outline-secondary rounded-pill fw-semibold">
            <i class="bi bi-megaphone me-1"></i> New announcement
        </a>
    @endif
    <a href="{{ route('messages.create') }}" class="btn btn-primary rounded-pill fw-semibold" style="background:var(--terra-orange);border-color:var(--terra-orange);">
        <i class="bi bi-pencil-square me-1"></i> New message
    </a>
</div>

<div class="tm {{ $active ? 'has-active' : '' }}">
    {{-- ============ Conversation list ============ --}}
    <div class="tm-list">
        <div class="tm-list-head">
            <form method="GET" action="{{ route('messages.index') }}" class="tm-search" role="search">
                <i class="bi bi-search"></i>
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search messages or people" aria-label="Search messages">
            </form>
            <nav class="tm-tabs" aria-label="Filter conversations">
                @foreach(['inbox' => 'Inbox', 'unread' => 'Unread', 'announcements' => 'Announcements', 'archived' => 'Archived'] as $key => $label)
                    <a href="{{ route('messages.index', array_filter(['filter' => $key, 'q' => $search])) }}" class="{{ $filter === $key ? 'is-active' : '' }}">
                        {{ $label }}
                        @if($key === 'unread' && $unreadTotal)<span class="badge rounded-pill text-bg-warning ms-1">{{ $unreadTotal }}</span>@endif
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="tm-convs">
            @forelse($conversations as $conv)
                @php
                    $mineP = $conv->participants->first();
                    $last = $conv->latestMessage;
                    $unread = $last && $last->user_id !== $me->id && $last->id > (int) $mineP?->last_read_message_id;
                    $title = $conv->titleFor($me);
                @endphp
                <a href="{{ route('messages.show', array_filter(['conversation' => $conv->id, 'filter' => $filter !== 'inbox' ? $filter : null, 'q' => $search ?: null])) }}"
                   class="tm-conv {{ $active && $active->id === $conv->id ? 'is-active' : '' }} {{ $unread ? 'is-unread' : '' }}">
                    <span class="tm-conv-icon {{ $conv->isAnnouncement() ? 'is-announcement' : '' }}">
                        @if($conv->isAnnouncement())<i class="bi bi-megaphone-fill"></i>@else{{ strtoupper(mb_substr($title, 0, 1)) }}@endif
                    </span>
                    <span class="tm-conv-body">
                        <span class="tm-conv-top">
                            <span class="tm-conv-name">{{ $title }}</span>
                            <span class="tm-conv-time">{{ $conv->last_message_at?->isToday() ? $conv->last_message_at->format('H:i') : $conv->last_message_at?->format('d M') }}</span>
                        </span>
                        @unless($conv->isAnnouncement())
                            <span class="tm-conv-subject d-block">{{ $conv->subject }}</span>
                        @endunless
                        <span class="tm-conv-preview d-block">
                            @if($last){{ $last->user_id === $me->id ? 'You: ' : '' }}{{ $last->body ? \Illuminate\Support\Str::limit($last->body, 60) : '📎 Attachment' }}@endif
                        </span>
                    </span>
                    @if($unread)<span class="tm-unread-dot" aria-label="Unread"></span>@endif
                </a>
            @empty
                <div class="tm-empty">
                    <i class="bi bi-chat-square-text"></i>
                    {{ $search ? 'No conversations match your search.' : 'No conversations yet.' }}
                </div>
            @endforelse

            @if($conversations->hasPages())
                <div class="p-3">{{ $conversations->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ============ Thread ============ --}}
    <section class="tm-thread" aria-label="Conversation">
        @if($active)
            <header class="tm-thread-head">
                <a href="{{ route('messages.index') }}" class="btn btn-sm btn-light d-lg-none" aria-label="Back to conversations"><i class="bi bi-arrow-left"></i></a>
                <div class="flex-grow-1 min-w-0">
                    <h2 class="tm-thread-title text-truncate">{{ $active->subject }}</h2>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        @if($active->isAnnouncement())
                            <span class="tm-chip" style="background:#fff3dc;color:#8f5a08;"><i class="bi bi-megaphone"></i> Announcement · {{ $active->users->count() - 1 }} recipients</span>
                        @else
                            <span class="tm-chip"><i class="bi bi-people"></i> {{ $active->users->pluck('name')->implode(', ') }}</span>
                        @endif
                        @if($active->property)<span class="tm-chip"><i class="bi bi-buildings"></i> {{ $active->property->name }}</span>@endif
                        @if($active->unit)<span class="tm-chip"><i class="bi bi-door-open"></i> Unit {{ $active->unit->unit_number }}</span>@endif
                    </div>
                </div>
                <form method="POST" action="{{ route('messages.archive', $active) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-light" title="{{ $myParticipant?->archived_at ? 'Move to inbox' : 'Archive' }}">
                        <i class="bi {{ $myParticipant?->archived_at ? 'bi-inbox' : 'bi-archive' }}"></i>
                    </button>
                </form>
            </header>

            <div class="tm-body" id="tmBody" data-poll="{{ route('messages.poll', $active) }}" data-last="{{ $thread->last()?->id ?? 0 }}">
                @foreach($thread as $message)
                    @include('messages._message', ['message' => $message, 'me' => $me])
                @endforeach
            </div>

            @if($myParticipant?->can_reply)
                <form method="POST" action="{{ route('messages.reply', $active) }}" enctype="multipart/form-data" class="tm-compose" id="tmCompose">
                    @csrf
                    <div class="tm-compose-row">
                        <label class="tm-attach" title="Attach photos or documents">
                            <i class="bi bi-paperclip fs-5"></i>
                            <input type="file" name="attachments[]" multiple hidden
                                   accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt" id="tmFiles">
                        </label>
                        <textarea name="body" rows="1" class="form-control" placeholder="Write a reply…" aria-label="Reply" id="tmText" maxlength="5000">{{ old('body') }}</textarea>
                        <button type="submit" class="btn btn-primary tm-send"><i class="bi bi-send-fill"></i><span class="d-none d-sm-inline ms-1">Send</span></button>
                    </div>
                    <div class="tm-picked" id="tmPicked"></div>
                    @error('body')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                    @error('attachments.*')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                </form>
            @else
                <div class="tm-readonly"><i class="bi bi-info-circle me-1"></i> This is an announcement. Replies are turned off — start a new message to contact your property manager.</div>
            @endif
        @else
            <div class="tm-placeholder">
                <div>
                    <i class="bi bi-chat-dots d-block mb-2"></i>
                    <div class="fw-bold" style="color:var(--terra-navy);">Select a conversation</div>
                    <div class="small">Or start a new message to your landlord, property manager or tenants.</div>
                </div>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var body = document.getElementById('tmBody');
    if (!body) return;
    body.scrollTop = body.scrollHeight;

    var form = document.getElementById('tmCompose');
    var text = document.getElementById('tmText');
    var files = document.getElementById('tmFiles');
    var picked = document.getElementById('tmPicked');

    function nearBottom() { return body.scrollHeight - body.scrollTop - body.clientHeight < 120; }
    function append(html, last) {
        if (!html) return;
        var stick = nearBottom();
        var tmp = document.createElement('div'); tmp.innerHTML = html;
        Array.prototype.forEach.call(tmp.children, function (el) {
            if (!document.getElementById(el.id)) body.appendChild(el);
        });
        if (last) body.dataset.last = last;
        if (stick) body.scrollTop = body.scrollHeight;
    }

    if (text) {
        var grow = function () { text.style.height = 'auto'; text.style.height = Math.min(text.scrollHeight, 160) + 'px'; };
        text.addEventListener('input', grow);
        text.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); form.requestSubmit(); }
        });
    }
    if (files) files.addEventListener('change', function () {
        picked.innerHTML = '';
        Array.prototype.forEach.call(files.files, function (f) {
            var s = document.createElement('span'); s.textContent = '📎 ' + f.name; picked.appendChild(s);
        });
    });

    // Send without a full page reload; fall back to normal submit on any error.
    if (form && window.fetch && window.FormData) {
        form.addEventListener('submit', function (e) {
            if (!text.value.trim() && !(files && files.files.length)) { e.preventDefault(); text.focus(); return; }
            e.preventDefault();
            var btn = form.querySelector('.tm-send'); btn.disabled = true;
            fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw r; return r.json(); })
                .then(function (data) {
                    append(data.html, data.id);
                    body.scrollTop = body.scrollHeight;
                    form.reset(); picked.innerHTML = ''; text.style.height = '';
                })
                .catch(function () { form.submit(); })
                .finally(function () { btn.disabled = false; text.focus(); });
        });
    }

    // Poll for new messages every 15s while the tab is visible.
    var url = body.dataset.poll;
    setInterval(function () {
        if (document.hidden) return;
        fetch(url + '?after=' + (body.dataset.last || 0), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) { if (data && data.last_id) append(data.html, data.last_id); })
            .catch(function () {});
    }, 15000);
})();
</script>
@endpush
