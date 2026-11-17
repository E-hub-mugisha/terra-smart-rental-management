@php $mine = $message->user_id === $me->id; @endphp
<div class="tm-msg {{ $mine ? 'is-mine' : '' }}" id="m{{ $message->id }}" data-message-id="{{ $message->id }}">
    @unless($mine)
        <span class="tm-avatar" aria-hidden="true">{{ strtoupper(mb_substr($message->sender?->name ?? '?', 0, 1)) }}</span>
    @endunless
    <div class="tm-bubble-wrap">
        <div class="tm-meta">
            <strong>{{ $mine ? 'You' : ($message->sender?->name ?? 'Deleted user') }}</strong>
            <time datetime="{{ $message->created_at?->toIso8601String() }}" title="{{ $message->created_at?->format('d M Y, H:i') }}">
                {{ $message->created_at?->isToday() ? $message->created_at->format('H:i') : $message->created_at?->format('d M, H:i') }}
            </time>
        </div>
        @if($message->body)
            <div class="tm-bubble">{!! nl2br(e($message->body)) !!}</div>
        @endif
        @if($message->attachments->isNotEmpty())
            <div class="tm-files">
                @foreach($message->attachments as $file)
                    @if($file->isImage())
                        <a href="{{ route('messages.attachments.show', $file) }}" target="_blank" rel="noopener" class="tm-photo">
                            <img src="{{ route('messages.attachments.show', $file) }}" alt="{{ $file->original_name }}" loading="lazy">
                        </a>
                    @else
                        <a href="{{ route('messages.attachments.show', $file) }}" target="_blank" rel="noopener" class="tm-file">
                            <i class="bi {{ $file->mime_type === 'application/pdf' ? 'bi-file-earmark-pdf' : 'bi-file-earmark-text' }}"></i>
                            <span><strong>{{ \Illuminate\Support\Str::limit($file->original_name, 40) }}</strong><small>{{ $file->human_size }}</small></span>
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
