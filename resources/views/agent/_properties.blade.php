<div class="row g-3">
    @forelse($properties as $p)
        @php $img = $p->images->first(); $link = $p->status === 'active' ? route('listings.show', $p) : null; @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius:14px;">
                <div style="aspect-ratio:16/9;background:#e7ebf3;">
                    @if($img)<img src="{{ asset('storage/' . $img->image_path) }}" alt="" style="width:100%;height:100%;object-fit:cover;" loading="lazy">@endif
                </div>
                <div class="card-body">
                    <div class="small text-secondary"><i class="bi bi-geo-alt-fill"></i> {{ $p->location_line ?: 'Rwanda' }}</div>
                    <div class="fw-bold" style="color:var(--terra-navy);">{{ $p->name }}</div>
                    <div class="small mb-2">{{ $p->units_available }} of {{ $p->units_total }} units available
                        @if($p->rent_from) · from RWF {{ number_format((float) $p->rent_from) }}@endif</div>
                    @if($link)
                        <div class="d-flex gap-2">
                            <a href="{{ $link }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill">Public page</a>
                            <a href="https://wa.me/?text={{ rawurlencode($p->name . ' — units available: ' . $link) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success rounded-pill"><i class="bi bi-whatsapp"></i> Share</a>
                            <button type="button" class="btn btn-sm btn-light rounded-pill" onclick="navigator.clipboard && navigator.clipboard.writeText('{{ $link }}'); this.textContent='Copied ✓'">Copy link</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center text-secondary py-5">No properties assigned to you yet. A Terra administrator assigns agents to properties.</div>
    @endforelse
</div>
