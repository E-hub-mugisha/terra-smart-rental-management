@extends('layouts.app')

@section('title', $mode === 'announcement' ? 'New announcement' : 'New message')
@section('page_heading', $mode === 'announcement' ? 'New announcement' : 'New message')
@section('page_subtitle', $mode === 'announcement'
    ? 'Send a notice to every tenant of a property. Tenants can read it but not reply.'
    : 'Messages are kept with the property and unit they relate to.')

@push('styles')
<style>
    .tmc { max-width: 820px; background: var(--terra-surface); border: 1px solid var(--terra-border); border-radius: var(--terra-radius); box-shadow: var(--terra-shadow); }
    .tmc-head { padding: 16px 22px; border-bottom: 1px solid var(--terra-border); display: flex; gap: 8px; flex-wrap: wrap; }
    .tmc-head a { font-weight: 700; font-size: 13px; padding: 7px 14px; border-radius: 999px; text-decoration: none; color: var(--terra-navy); border: 1px solid var(--terra-border); }
    .tmc-head a.is-active { background: var(--terra-navy); color: #fff; border-color: var(--terra-navy); }
    .tmc-body { padding: 22px; }
    .tmc .form-control, .tmc .form-select { border-radius: 10px; min-height: 44px; }
    .tmc-drop { border: 2px dashed var(--terra-border); border-radius: 12px; padding: 16px; text-align: center; color: var(--terra-muted); cursor: pointer; display: block; }
    .tmc-drop:hover { border-color: var(--terra-orange); color: var(--terra-orange); }
    .tmc-context { font-size: 12.5px; color: var(--terra-muted); margin-top: 6px; min-height: 18px; }
</style>
@endpush

@section('content')
<div class="tmc">
    @if($properties->isNotEmpty())
        <div class="tmc-head">
            <a href="{{ route('messages.create') }}" class="{{ $mode === 'direct' ? 'is-active' : '' }}"><i class="bi bi-chat-dots me-1"></i> Direct message</a>
            <a href="{{ route('messages.create', ['mode' => 'announcement']) }}" class="{{ $mode === 'announcement' ? 'is-active' : '' }}"><i class="bi bi-megaphone me-1"></i> Announcement</a>
        </div>
    @endif

    <form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data" class="tmc-body">
        @csrf
        <input type="hidden" name="type" value="{{ $mode }}">

        @if($mode === 'announcement')
            <div class="mb-3">
                <label for="property_id" class="form-label fw-semibold">Property <span class="text-danger">*</span></label>
                <select name="property_id" id="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                    <option value="">Choose a property</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @selected((int) old('property_id') === $property->id)>{{ $property->name }}</option>
                    @endforeach
                </select>
                @error('property_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="tmc-context">Goes to every tenant with an active or pending lease at this property, plus its owner and managers.</div>
            </div>
        @else
            <div class="mb-3">
                <label for="recipient_id" class="form-label fw-semibold">To <span class="text-danger">*</span></label>
                @if($contacts->isEmpty())
                    <div class="alert alert-light border mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        You don't have any contacts yet. Contacts appear once you have a lease, or once tenants hold leases on properties you manage.
                    </div>
                @else
                    <select name="recipient_id" id="recipient_id" class="form-select @error('recipient_id') is-invalid @enderror" required>
                        <option value="">Choose who to message</option>
                        @foreach($contacts->groupBy('group') as $group => $items)
                            <optgroup label="{{ $group }}">
                                @foreach($items as $c)
                                    <option value="{{ $c['user']->id }}" data-context="{{ $c['context'] }}"
                                        @selected((int) old('recipient_id', $preselect) === $c['user']->id)>
                                        {{ $c['user']->name }} — {{ $c['context'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('recipient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @endif
            </div>
        @endif

        <div class="mb-3">
            <label for="subject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
            <input type="text" name="subject" id="subject" maxlength="150" required
                   class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}"
                   placeholder="{{ $mode === 'announcement' ? 'e.g. Water interruption on Saturday' : 'e.g. Question about my rent invoice' }}">
            @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="body" class="form-label fw-semibold">Message</label>
            <textarea name="body" id="body" rows="7" maxlength="5000" class="form-control @error('body') is-invalid @enderror"
                      placeholder="Write your message…">{{ old('body') }}</textarea>
            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
            <label class="tmc-drop" for="attachments">
                <i class="bi bi-paperclip fs-4 d-block"></i>
                <span id="tmcPicked">Attach photos or documents (up to 5 files, 10 MB each)</span>
                <input type="file" name="attachments[]" id="attachments" multiple hidden
                       accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
            </label>
            @error('attachments')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
            @error('attachments.*')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('messages.index') }}" class="btn btn-light rounded-pill px-4">Cancel</a>
            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" style="background:var(--terra-orange);border-color:var(--terra-orange);"
                    @if($mode === 'direct' && $contacts->isEmpty()) disabled @endif>
                <i class="bi bi-send-fill me-1"></i> {{ $mode === 'announcement' ? 'Send announcement' : 'Send message' }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('attachments'), label = document.getElementById('tmcPicked');
    if (input) input.addEventListener('change', function () {
        var names = Array.prototype.map.call(input.files, function (f) { return f.name; });
        label.textContent = names.length ? names.join(', ') : 'Attach photos or documents (up to 5 files, 10 MB each)';
    });
})();
</script>
@endpush
