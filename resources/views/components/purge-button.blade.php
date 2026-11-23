{{--
    "Delete permanently" for demo/test data. Removes the record and everything
    linked to it (and their files). The admin must type DELETE to confirm.

    <x-purge-button type="lease" :id="$lease->id" what="this lease" />
    <x-purge-button type="tenant" :id="$tenant->id" what="this tenant" class="dropdown-item item-danger" />
--}}
@props(['type', 'id', 'what' => 'this record', 'label' => 'Delete permanently'])

@php
    $warning = "Permanently delete {$what} and ALL of its linked data (leases, invoices, payments, files and login accounts)? "
        . "This cannot be undone. Use it only for demo or test data.\n\nType DELETE to confirm.";
@endphp

<form method="POST" action="{{ route('admin.purge', ['type' => $type, 'id' => $id]) }}" class="d-inline m-0"
      onsubmit="var v = window.prompt({{ \Illuminate\Support\Js::from($warning) }}); if (v !== 'DELETE') { return false; } this.querySelector('[name=confirm]').value = v; return true;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="confirm" value="">
    <button type="submit" {{ $attributes->merge(['class' => 'btn btn-danger']) }}>
        <i class="bi bi-trash3-fill me-1"></i>{{ $label }}
    </button>
</form>
