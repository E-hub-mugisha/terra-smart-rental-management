{{-- Property profile extras (brief §5): UPI, media, amenities and utilities. --}}
@php
    $profileProperty = $property ?? null;
    $selectedAmenities = old('amenities', $profileProperty?->amenities ?? []);
    $selectedUtilities = old('utilities', $profileProperty?->utilities ?? []);
@endphp

<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-header bg-white border-bottom p-4">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                 style="width:46px;height:46px;background:#fff2e9;color:#D05208;">
                <i class="bi bi-stars fs-4"></i>
            </div>
            <div>
                <h2 class="h5 fw-bold mb-1" style="color:#19265d;">Amenities, utilities &amp; references</h2>
                <p class="small text-secondary mb-0">These appear on the public property page next to the photos.</p>
            </div>
        </div>
    </div>

    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="upi" class="form-label fw-semibold">UPI / land reference</label>
                <input type="text" name="upi" id="upi" maxlength="60"
                       class="form-control @error('upi') is-invalid @enderror"
                       value="{{ old('upi', $profileProperty?->upi) }}" placeholder="e.g. 1/02/03/04/1234">
                @error('upi')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="video_url" class="form-label fw-semibold">Video tour link</label>
                <input type="url" name="video_url" id="video_url" maxlength="255"
                       class="form-control @error('video_url') is-invalid @enderror"
                       value="{{ old('video_url', $profileProperty?->video_url) }}" placeholder="https://youtube.com/...">
                @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <div class="form-label fw-semibold">Amenities</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach(\App\Models\Property::AMENITIES as $key => [$text, $icon])
                        <input type="checkbox" class="btn-check" name="amenities[]" value="{{ $key }}" id="amenity-{{ $key }}"
                               autocomplete="off" @checked(in_array($key, (array) $selectedAmenities, true))>
                        <label class="btn btn-sm btn-outline-secondary rounded-pill" for="amenity-{{ $key }}">
                            <i class="bi {{ $icon }} me-1"></i>{{ $text }}
                        </label>
                    @endforeach
                </div>
                @error('amenities.*')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <div class="form-label fw-semibold">Utilities</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach(\App\Models\Property::UTILITIES as $key => [$text, $icon])
                        <input type="checkbox" class="btn-check" name="utilities[]" value="{{ $key }}" id="utility-{{ $key }}"
                               autocomplete="off" @checked(in_array($key, (array) $selectedUtilities, true))>
                        <label class="btn btn-sm btn-outline-secondary rounded-pill" for="utility-{{ $key }}">
                            <i class="bi {{ $icon }} me-1"></i>{{ $text }}
                        </label>
                    @endforeach
                </div>
                @error('utilities.*')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
