
@php
    $imageRoutes = [
        'building' => [
            'primary' => 'admin.properties.buildings.images.primary',
            'delete' => 'admin.properties.buildings.images.destroy',
            'param' => 'building',
        ],
        'floor' => [
            'primary' => 'admin.properties.floors.images.primary',
            'delete' => 'admin.properties.floors.images.destroy',
            'param' => 'floor',
        ],
        'unit' => [
            'primary' => 'admin.properties.units.images.primary',
            'delete' => 'admin.properties.units.images.destroy',
            'param' => 'unit',
        ],
    ];

    $config = $imageRoutes[$imageType];
@endphp

<div class="row g-3">
    @forelse($images as $image)
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card h-100">
                <img
                    src="{{ upload_url($image->image_path) }}"
                    class="card-img-top"
                    alt="{{ $image->caption ?? 'Property image' }}"
                    style="height: 160px; object-fit: cover;"
                >

                <div class="card-body p-2">
                    @if($image->is_primary)
                        <span class="badge bg-success mb-2">
                            Primary image
                        </span>
                    @endif

                    @if($image->caption)
                        <p class="small mb-2">
                            {{ $image->caption }}
                        </p>
                    @endif

                    <div class="d-flex flex-wrap gap-2">
                        @if(!$image->is_primary)
                            <form method="POST"
                                action="{{ route($config['primary'], [
                                    'property' => $property->id,
                                    $config['param'] => $parent->id,
                                    'image' => $image->id,
                                ]) }}">
                                @csrf
                                @method('PUT')

                                <button class="btn btn-sm btn-outline-primary">
                                    Set primary
                                </button>
                            </form>
                        @endif

                        <form method="POST"
                            action="{{ route($config['delete'], [
                                'property' => $property->id,
                                $config['param'] => $parent->id,
                                'image' => $image->id,
                            ]) }}"
                            onsubmit="return confirm('Delete this image permanently?')">
                            @csrf
                            @method('DELETE')

                            <button class="btn btn-sm btn-outline-danger">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <p class="text-muted mb-0">No images uploaded yet.</p>
        </div>
    @endforelse
</div>
