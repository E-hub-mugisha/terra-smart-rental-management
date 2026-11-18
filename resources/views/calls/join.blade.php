@extends('layouts.app')

@section('title', 'Terra call')
@section('page_heading', $isAdmin ? 'Call with ' . $call->subscriber?->name : 'Call with Terra')
@section('page_subtitle', $call->subject ?: 'In-platform audio and video call')

@use('App\Models\SubscriberCall')
@include('partials.sx-styles')

@section('content')
@php
    $base = rtrim((string) config('services.calls.meet_url'), '/');
    $src = $base . '/' . rawurlencode($call->room)
        . '#userInfo.displayName=' . rawurlencode('"' . $displayName . '"')
        . '&config.prejoinPageEnabled=false&config.startWithVideoMuted=true&config.disableDeepLinking=true';
@endphp
<div class="row g-3">
    <div class="{{ $isAdmin ? 'col-xl-9' : 'col-12' }}">
        <div class="sx-card overflow-hidden">
            <div class="sx-card-head">
                <h2><i class="bi bi-camera-video me-1"></i> {{ $isAdmin ? $call->subscriber?->name : 'Terra · ' . $call->caller?->name }}</h2>
                <a href="{{ $call->room_url }}" target="_blank" rel="noopener" class="small">Open in a new tab</a>
            </div>
            <iframe src="{{ $src }}" title="Terra call" allow="camera; microphone; display-capture; autoplay; clipboard-write; fullscreen"
                    style="width:100%;height:min(70vh,640px);border:0;display:block;background:#111"></iframe>
        </div>
        <p class="small sx-muted mt-2 mb-0"><i class="bi bi-shield-lock"></i> This call room is private to this conversation and its link expires after 12 hours. Allow microphone access when your browser asks.</p>
    </div>

    @if($isAdmin)
        <div class="col-xl-3">
            <form method="POST" action="{{ route('calls.end', $call) }}" class="sx-card">
                @csrf
                <div class="sx-card-head"><h2>Finish & log</h2></div>
                <div class="sx-card-body">
                    <label class="form-label small mb-1" for="eOutcome">Outcome</label>
                    <select id="eOutcome" name="outcome" class="form-select form-select-sm mb-2">
                        @foreach(SubscriberCall::OUTCOMES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                    </select>
                    <label class="form-label small mb-1" for="eMin">Duration (minutes)</label>
                    <input type="number" id="eMin" name="minutes" min="0" max="600" class="form-control form-control-sm mb-2" placeholder="Auto">
                    <label class="form-label small mb-1" for="eNotes">Notes</label>
                    <textarea id="eNotes" name="notes" rows="5" maxlength="3000" class="form-control form-control-sm mb-3"></textarea>
                    <button class="sx-btn sx-btn-primary w-100 justify-content-center"><i class="bi bi-telephone-x"></i> End call & save</button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
