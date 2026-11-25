{{--
    "Add to HR" for a platform user (manager, accountant, agent, field staff).
    Opens their HR record if they are already an employee of that employer.

    <x-hr-add-button :user="$member->user" />                         (Terra staff)
    <x-hr-add-button :user="$a->user" :property-id="$a->property_id" />
--}}
@props(['user', 'propertyId' => null, 'label' => 'Add to HR'])

@php
    // Pages often load the user with only a few columns; look the role up if it is missing.
    $hrRole = $user ? ($user->getAttributes()['role'] ?? \App\Models\User::whereKey($user->id)->value('role')) : null;
@endphp

@if($user && array_key_exists((string) $hrRole, \App\Models\Employee::LINKABLE_ROLES))
    @php
        $existing = \App\Models\Employee::where('user_id', $user->id)
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId), fn ($q) => $q->whereNull('property_id'))
            ->first();
    @endphp
    @if($existing)
        <a href="{{ route('hr.employees.show', $existing) }}" {{ $attributes->merge(['class' => 'btn btn-sm btn-light']) }}>
            <i class="bi bi-person-badge me-1"></i>HR record
        </a>
    @else
        <a href="{{ route('hr.employees.create', ['user_id' => $user->id, 'property_id' => $propertyId ?? '']) }}" {{ $attributes->merge(['class' => 'btn btn-sm btn-light']) }}>
            <i class="bi bi-person-plus me-1"></i>{{ $label }}
        </a>
    @endif
@endif
