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
<div class="dropdown table-action">
    <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ti ti-dots-vertical"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        @if($hasEdit)
            <a class="dropdown-item" href="{{ $editRoute }}"
                @foreach($editAttributes as $key => $value)
                    {{ $key }}="{{ $value }}"
                @endforeach
            >
                <i class="ti ti-edit text-blue"></i> Edit
            </a>
        @endif

        {{ $slot }}

        @if($hasDelete)
            <button type="button" class="dropdown-item text-danger crm-delete-trigger"
                data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                data-delete-action="{{ $deleteRoute }}"
                data-delete-title="{{ $deleteTitle }}"
                data-delete-message="{{ $deleteMessage ?? 'Are you sure you want to delete this record?' }}">
                <i class="ti ti-trash me-1"></i> Delete
            </button>
        @endif
    </div>
</div>
@endif

