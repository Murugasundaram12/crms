@props([
    'editRoute' => null,
    'deleteRoute' => null,
    'deleteTitle' => 'Confirm Delete',
    'deleteMessage' => null,
    'editPermission' => null,
    'deletePermission' => null,
    'editAttributes' => [],
])

@php
$hasEdit = $editRoute && (!$editPermission || auth()->user()?->hasPermission($editPermission));
$hasDelete = $deleteRoute && (!$deletePermission || auth()->user()?->hasPermission($deletePermission));
$hasExtra = !empty($slot->toHtml());
@endphp

@if($hasEdit || $hasDelete || $hasExtra)
<div class="table-action d-inline-flex align-items-center justify-content-end gap-1 flex-wrap" aria-label="Row actions">
    @if($hasEdit)
        <a class="btn btn-sm btn-outline-primary" href="{{ $editRoute }}" title="Edit" aria-label="Edit"
            @foreach($editAttributes as $key => $value)
                {{ $key }}="{{ $value }}"
            @endforeach
        >
            <i class="ti ti-edit"></i>
        </a>
    @endif

    {{ $slot }}

    @if($hasDelete)
        <button type="button" class="btn btn-sm btn-outline-danger crm-delete-trigger" title="Delete" aria-label="Delete"
            data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
            data-delete-action="{{ $deleteRoute }}"
            data-delete-title="{{ $deleteTitle }}"
            data-delete-message="{{ $deleteMessage ?? 'Are you sure you want to delete this record?' }}">
            <i class="ti ti-trash"></i>
        </button>
    @endif
</div>
@endif
