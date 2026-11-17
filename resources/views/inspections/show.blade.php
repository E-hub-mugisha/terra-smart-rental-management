@extends('layouts.app')

@section('title', $inspection->type_label . ' ' . $inspection->inspection_number)
@section('page_heading', $inspection->type_label)
@section('page_subtitle', 'Unit ' . $inspection->unit?->unit_number . ' · ' . $inspection->unit?->building?->property?->name)

@include('inspections._styles')

@php
    $moveOut = $inspection->isMoveOut();
    $currency = $inspection->currency ?: 'RWF';
    $isStaff = $role === 'staff';
    $worse = $moveOut ? $inspection->items->filter(fn ($i) => $i->changeFromBaseline() === 'worse')->count() : 0;
    $issues = $inspection->items->whereIn('condition', ['poor', 'damaged', 'missing'])->count();
    $steps = ['draft' => 'Recorded', 'awaiting_tenant' => 'Sent to tenant', 'completed' => 'Confirmed'];
    $reached = ['draft' => 0, 'awaiting_tenant' => 1, 'disputed' => 1, 'completed' => 2][$inspection->status] ?? 0;
@endphp

@section('content')
{{-- ===== Header ===== --}}
<div class="ti-card">
    <div class="ti-card-body">
        <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="ti-pill ty-{{ $inspection->type }}">{{ $inspection->type_label }}</span>
                    <span class="ti-pill st-{{ $inspection->status }}">{{ $inspection->status_label }}</span>
                    <span class="ti-pill st-draft">{{ $inspection->inspection_number }}</span>
                </div>
                <ol class="ti-steps" aria-label="Progress">
                    @foreach(array_values($steps) as $i => $label)
                        <li class="{{ $i < $reached || $inspection->status === 'completed' ? 'is-done' : ($i === $reached ? 'is-current' : '') }}">
                            <i class="bi {{ $i < $reached || $inspection->status === 'completed' ? 'bi-check-circle-fill' : 'bi-circle' }}"></i> {{ $label }}
                        </li>
                    @endforeach
                </ol>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('inspections.report', $inspection) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-printer me-1"></i> Print / PDF</a>
                @if($isStaff && $inspection->isEditable())
                    <a href="{{ route('inspections.edit', $inspection) }}" class="btn btn-sm ti-btn-primary rounded-pill"><i class="bi bi-pencil-square me-1"></i> {{ $inspection->status === 'disputed' ? 'Edit & resubmit' : 'Continue editing' }}</a>
                @endif
                @if($isStaff && $inspection->isMoveIn() && $inspection->status === 'completed' && ! $inspection->moveOut
                    && in_array($inspection->lease?->status, ['active', 'terminated', 'expired'], true))
                    <form method="POST" action="{{ route('inspections.store') }}">
                        @csrf
                        <input type="hidden" name="lease_id" value="{{ $inspection->lease_id }}">
                        <input type="hidden" name="type" value="move_out">
                        <button type="submit" class="btn btn-sm btn-dark rounded-pill"><i class="bi bi-box-arrow-right me-1"></i> Start move-out inspection</button>
                    </form>
                @endif
                @if($inspection->moveOut)
                    <a href="{{ route('inspections.show', $inspection->moveOut) }}" class="btn btn-sm btn-outline-secondary rounded-pill">View move-out <i class="bi bi-arrow-right"></i></a>
                @endif
                @if($inspection->baseline)
                    <a href="{{ route('inspections.show', $inspection->baseline) }}" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-arrow-left"></i> Move-in report</a>
                @endif
            </div>
        </div>

        <dl class="ti-facts mt-3">
            <div><dt>Tenant</dt><dd>{{ $inspection->lease?->tenant?->full_name ?? '—' }}</dd></div>
            <div><dt>Lease</dt><dd>{{ $inspection->lease?->lease_number }}</dd></div>
            <div><dt>Date</dt><dd>{{ $inspection->inspection_date?->format('d M Y') }}</dd></div>
            <div><dt>Inspected by</dt><dd>{{ $inspection->inspector?->name ?? '—' }}</dd></div>
            <div><dt>Electricity meter</dt><dd>{{ $inspection->electricity_reading ?? '—' }}
                @if($moveOut && $inspection->baseline?->electricity_reading !== null && $inspection->electricity_reading !== null)
                    <small class="text-secondary d-block">+{{ number_format((float) $inspection->electricity_reading - (float) $inspection->baseline->electricity_reading, 2) }} since move-in</small>
                @endif</dd></div>
            <div><dt>Water meter</dt><dd>{{ $inspection->water_reading ?? '—' }}
                @if($moveOut && $inspection->baseline?->water_reading !== null && $inspection->water_reading !== null)
                    <small class="text-secondary d-block">+{{ number_format((float) $inspection->water_reading - (float) $inspection->baseline->water_reading, 2) }} since move-in</small>
                @endif</dd></div>
            <div><dt>Keys</dt><dd>{{ $inspection->keys_handed ?? '—' }}
                @if($moveOut && $inspection->baseline?->keys_handed !== null)<small class="text-secondary"> (of {{ $inspection->baseline->keys_handed }})</small>@endif</dd></div>
            <div><dt>{{ $moveOut ? 'Worse than move-in' : 'Defects noted' }}</dt><dd class="{{ ($moveOut ? $worse : $issues) ? 'text-danger' : '' }}">{{ $moveOut ? $worse : $issues }} {{ \Illuminate\Support\Str::plural('item', $moveOut ? $worse : $issues) }}</dd></div>
        </dl>
        @if($inspection->general_notes)
            <p class="mt-3 mb-0"><strong>Notes:</strong> {{ $inspection->general_notes }}</p>
        @endif
    </div>
</div>

{{-- ===== Response / sign-off ===== --}}
@if($inspection->status === 'disputed')
    <div class="alert alert-danger mt-3 mb-0">
        <strong><i class="bi bi-exclamation-octagon me-1"></i> Disputed by the tenant {{ $inspection->tenant_responded_at?->diffForHumans() }}:</strong>
        “{{ $inspection->tenant_comment }}”
        @unless($isStaff)<div class="small mt-1">Your property manager will review the report and send it to you again.</div>@endunless
    </div>
@elseif($inspection->status === 'completed' && $inspection->tenant_comment)
    <div class="alert alert-light border mt-3 mb-0"><i class="bi bi-chat-quote me-1"></i> {{ $inspection->tenant_comment }}</div>
@endif

@if($inspection->status === 'awaiting_tenant')
    <div class="ti-card" style="border-color:#f4d3bf;">
        <div class="ti-card-head" style="background:#fff8f2;">
            <h2 class="ti-card-title"><i class="bi bi-pen me-1"></i> {{ $isStaff ? 'Waiting for the tenant' : 'Please review and confirm' }}</h2>
        </div>
        <div class="ti-card-body">
            @if($role === 'tenant')
                <p>Check each item and photo below{{ $moveOut ? ', and the deposit settlement' : '' }}. Confirm if the report is accurate, or tell us what you disagree with.</p>
                @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('inspections.respond', $inspection) }}" class="border rounded-3 p-3 h-100">
                            @csrf
                            <input type="hidden" name="action" value="confirm">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="agree" value="1" id="agree" required>
                                <label class="form-check-label" for="agree">I have checked this report and it accurately describes the unit{{ $moveOut ? ' and the deposit settlement' : '' }}.</label>
                            </div>
                            <button type="submit" class="btn btn-success rounded-pill w-100"><i class="bi bi-check2-circle me-1"></i> Confirm report</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('inspections.respond', $inspection) }}" class="border rounded-3 p-3 h-100">
                            @csrf
                            <input type="hidden" name="action" value="dispute">
                            <textarea name="comment" rows="3" maxlength="3000" class="form-control mb-2" required placeholder="What is wrong? e.g. The bedroom window crack was already there at move-in.">{{ old('comment') }}</textarea>
                            <button type="submit" class="btn btn-outline-danger rounded-pill w-100"><i class="bi bi-flag me-1"></i> I disagree</button>
                        </form>
                    </div>
                </div>
            @else
                <p class="mb-3">Sent {{ $inspection->submitted_at?->diffForHumans() }}.
                    @if($tenantHasLogin) The tenant has been notified and can confirm or dispute from their account.
                    @else <strong>This tenant has no Terra login</strong>, so print the report, have it signed on site, then record the confirmation here. @endif
                </p>
                @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
                <form method="POST" action="{{ route('inspections.respond', $inspection) }}" class="d-flex flex-wrap gap-2">
                    @csrf
                    <input type="hidden" name="action" value="confirm_in_person">
                    <input type="text" name="comment" class="form-control" style="flex:1 1 280px" required maxlength="3000" placeholder="How was it confirmed? e.g. Paper copy signed on site by the tenant">
                    <button type="submit" class="btn btn-outline-success rounded-pill"><i class="bi bi-person-check me-1"></i> Record in-person confirmation</button>
                </form>
            @endif
        </div>
    </div>
@endif

<div class="row g-4 mt-0">
    {{-- ===== Checklist ===== --}}
    <div class="{{ $moveOut ? 'col-xl-8' : 'col-12' }}">
        <div class="ti-card">
            <div class="ti-card-head">
                <h2 class="ti-card-title">{{ $moveOut ? 'Move-in vs move-out comparison' : 'Condition report' }}</h2>
                @if($moveOut)
                    <span class="small text-secondary"><span class="ti-change worse">▲ worse</span> · <span class="ti-change same">= same</span> · <span class="ti-change better">▼ better</span></span>
                @endif
            </div>

            @php $cols = $moveOut ? 'minmax(0,1.2fr) 120px 120px 70px minmax(0,1.5fr) 110px' : 'minmax(0,1.2fr) 130px 60px minmax(0,2fr)'; @endphp
            <div class="ti-row ti-hide-sm small fw-bold text-secondary" style="grid-template-columns: {{ $cols }};">
                <span>Item</span>
                @if($moveOut)<span>Move-in</span><span>Move-out</span><span>Change</span>@else<span>Condition</span><span>Qty</span>@endif
                <span>Notes</span>
                @if($moveOut)<span class="text-end">Deduction</span>@endif
            </div>

            @foreach($areas as $area => $items)
                <div class="ti-area">{{ $area }}</div>
                @foreach($items as $item)
                    @php $change = $moveOut ? $item->changeFromBaseline() : null; @endphp
                    <div class="ti-row {{ $change === 'worse' ? 'is-worse' : '' }}" style="grid-template-columns: {{ $cols }};">
                        <div>
                            <span class="fw-semibold">{{ $item->item }}</span>
                            @if($item->media->isNotEmpty())
                                <div class="ti-thumbs mt-1">
                                    @foreach($item->media as $m)
                                        <a href="{{ route('inspections.media.show', $m) }}" target="_blank" class="ti-thumb is-sm">
                                            @if($m->isVideo())<span class="ti-play"><i class="bi bi-play-fill"></i></span>@else<img src="{{ route('inspections.media.show', $m) }}" alt="{{ $m->caption }}" loading="lazy">@endif
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @if($moveOut)
                            <div>@include('inspections._condition', ['condition' => $item->baseline?->condition])</div>
                            <div>@include('inspections._condition', ['condition' => $item->condition])</div>
                            <div>
                                @if($change === 'worse')<span class="ti-change worse">▲ Worse</span>
                                @elseif($change === 'better')<span class="ti-change better">▼ Better</span>
                                @elseif($change === 'same')<span class="ti-change same">= Same</span>
                                @else<span class="text-secondary small">—</span>@endif
                            </div>
                        @else
                            <div>@include('inspections._condition', ['condition' => $item->condition])</div>
                            <div class="small">{{ $item->quantity ?? '' }}</div>
                        @endif
                        <div class="small">
                            {{ $item->notes }}
                            @if($moveOut && $item->baseline?->notes)<div class="text-secondary">Move-in: {{ $item->baseline->notes }}</div>@endif
                        </div>
                        @if($moveOut)
                            <div class="text-end small fw-semibold {{ (float) $item->deduction_amount > 0 ? 'text-danger' : 'text-secondary' }}">
                                {{ (float) $item->deduction_amount > 0 ? $currency . ' ' . number_format((float) $item->deduction_amount) : '—' }}
                            </div>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>

        @if($generalMedia->isNotEmpty())
            <div class="ti-card">
                <div class="ti-card-head"><h2 class="ti-card-title"><i class="bi bi-images me-1"></i> General photos &amp; videos</h2></div>
                <div class="ti-card-body ti-thumbs">
                    @foreach($generalMedia as $m)
                        <a href="{{ route('inspections.media.show', $m) }}" target="_blank" class="ti-thumb" title="{{ $m->caption }}">
                            @if($m->isVideo())<span class="ti-play"><i class="bi bi-play-circle"></i></span>@else<img src="{{ route('inspections.media.show', $m) }}" alt="{{ $m->caption }}" loading="lazy">@endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ===== Deposit settlement (move-out) ===== --}}
    @if($moveOut)
        <div class="col-xl-4">
            <div class="ti-card">
                <div class="ti-card-head">
                    <h2 class="ti-card-title"><i class="bi bi-safe me-1"></i> Deposit settlement</h2>
                    @if($inspection->deposit_settled_at)<span class="ti-pill st-completed">Settled</span>@endif
                </div>
                <div class="ti-card-body">
                    <div class="ti-money"><span>Deposit held</span><strong>{{ $currency }} {{ number_format((float) $inspection->deposit_held) }}</strong></div>
                    <div class="ti-money"><span>Deductions</span><strong class="text-danger">− {{ $currency }} {{ number_format((float) $inspection->deposit_deductions) }}</strong></div>
                    <div class="ti-money"><span>Refund to tenant</span><span>{{ $currency }} {{ number_format((float) $inspection->deposit_refund) }}</span></div>

                    @if($inspection->deposit_settled_at)
                        <div class="alert alert-success small mt-3 mb-0">
                            Settled {{ $inspection->deposit_settled_at->format('d M Y') }}
                            @if($inspection->deposit_refund_reference)<br>Reference: <strong>{{ $inspection->deposit_refund_reference }}</strong>@endif
                            @if($inspection->settlement_notes)<br>{{ $inspection->settlement_notes }}@endif
                        </div>
                    @elseif($isStaff && $inspection->status === 'completed')
                        <form method="POST" action="{{ route('inspections.settle', $inspection) }}" class="mt-3">
                            @csrf
                            <input type="text" name="deposit_refund_reference" maxlength="255" class="form-control form-control-sm mb-2" placeholder="Refund reference (MoMo / bank transfer ID)">
                            <textarea name="settlement_notes" rows="2" maxlength="3000" class="form-control form-control-sm mb-2" placeholder="Notes (optional)"></textarea>
                            <button type="submit" class="btn btn-sm ti-btn-primary rounded-pill w-100" onclick="return confirm('Record the deposit as settled? The tenant will be notified.')">
                                <i class="bi bi-check2-all me-1"></i> Record deposit as settled
                            </button>
                            <div class="form-text">Terra records the settlement; pay the refund through your usual Mobile Money or bank channel.</div>
                        </form>
                    @else
                        <p class="small text-secondary mt-3 mb-0">The deposit is settled once the tenant has confirmed this move-out report.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
