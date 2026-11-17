@php $c = $condition ?? null; @endphp
<span class="ti-cond c-{{ $c ?: 'none' }}">{{ $c ? (\App\Models\Inspection::CONDITIONS[$c] ?? $c) : 'Not rated' }}</span>
