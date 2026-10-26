@extends('layouts.app')

@section('title', 'Edit Tool / Material')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h4 class="m-0">Edit Tool / Material</h4>
        <a href="{{ route('tools-materials.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <form method="POST" action="{{ route('tools-materials.update', $toolsMaterial) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="item_type" class="form-select tool-material-type-select @error('item_type') is-invalid @enderror" @error('item_type') aria-describedby="item_type_error" aria-invalid="true" @enderror required>
                            <option value="material" @selected(old('item_type', $toolsMaterial->item_type) === 'material')>Material</option>
                            <option value="tool" @selected(old('item_type', $toolsMaterial->item_type) === 'tool')>Tool</option>
                        </select>
                        @error('item_type')<div class="invalid-feedback d-block" id="item_type_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SKU / Code</label>
                        <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $toolsMaterial->sku) }}" @error('sku') aria-describedby="sku_error" aria-invalid="true" @enderror>
                        @error('sku')<div class="invalid-feedback d-block" id="sku_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $toolsMaterial->name) }}" @error('name') aria-describedby="name_error" aria-invalid="true" @enderror required>
                        @error('name')<div class="invalid-feedback d-block" id="name_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', $toolsMaterial->date?->toDateString()) }}" @error('date') aria-describedby="date_error" aria-invalid="true" @enderror required>
                        @error('date')<div class="invalid-feedback d-block" id="date_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 material-stock-field">
                        <label class="form-label">Unit <span class="text-danger">*</span></label>
                        <select name="unit" class="form-select material-required-field @error('unit') is-invalid @enderror" @error('unit') aria-describedby="unit_error" aria-invalid="true" @enderror required>
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->code }}" @selected(old('unit', $toolsMaterial->unit) === $unit->code)>
                                    {{ $unit->display_name }}
                                </option>
                            @endforeach
                            @if($toolsMaterial->unit && ! $units->contains('code', $toolsMaterial->unit))
                                <option value="{{ $toolsMaterial->unit }}" selected>{{ $toolsMaterial->unit }}</option>
                            @endif
                        </select>
                        @error('unit')<div class="invalid-feedback d-block" id="unit_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 material-stock-field">
                        <label class="form-label">Opening Quantity <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="opening_quantity" class="form-control material-required-field @error('opening_quantity') is-invalid @enderror" value="{{ old('opening_quantity', $toolsMaterial->opening_quantity) }}" @error('opening_quantity') aria-describedby="opening_quantity_error" aria-invalid="true" @enderror required>
                        @error('opening_quantity')<div class="invalid-feedback d-block" id="opening_quantity_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 material-stock-field">
                        <label class="form-label">Opening Rate <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="opening_rate" class="form-control material-required-field @error('opening_rate') is-invalid @enderror" value="{{ old('opening_rate', $toolsMaterial->opening_rate) }}" @error('opening_rate') aria-describedby="opening_rate_error" aria-invalid="true" @enderror required>
                        @error('opening_rate')<div class="invalid-feedback d-block" id="opening_rate_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 material-stock-field">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" step="0.01" name="reorder_level" class="form-control @error('reorder_level') is-invalid @enderror" value="{{ old('reorder_level', $toolsMaterial->reorder_level) }}" @error('reorder_level') aria-describedby="reorder_level_error" aria-invalid="true" @enderror>
                        @error('reorder_level')<div class="invalid-feedback d-block" id="reorder_level_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 d-flex flex-column justify-content-end">
                        <label class="d-flex align-items-center gap-2 mb-2">
                            <input type="hidden" name="active_status" value="0">
                            <input type="checkbox" name="active_status" class="form-check-input @error('active_status') is-invalid @enderror" value="1" @checked(old('active_status', $toolsMaterial->active_status)) @error('active_status') aria-describedby="active_status_error" aria-invalid="true" @enderror>
                            Active
                        </label>
                        @error('active_status')<div class="invalid-feedback d-block" id="active_status_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*" @error('image') aria-describedby="image_error" aria-invalid="true" @enderror>
                        @error('image')<div class="invalid-feedback d-block" id="image_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        @if($toolsMaterial->image_path)
                            <label class="form-label">Current Image</label>
                            <div><img src="{{ asset('storage/' . $toolsMaterial->image_path) }}" alt="{{ $toolsMaterial->name }}" class="rounded border" style="width: 88px; height: 88px; object-fit: cover;"></div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2" @error('description') aria-describedby="description_error" aria-invalid="true" @enderror>{{ old('description', $toolsMaterial->description) }}</textarea>
                        @error('description')<div class="invalid-feedback d-block" id="description_error">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-primary" type="submit">Update</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.querySelector('.tool-material-type-select');
            const materialFields = document.querySelectorAll('.material-stock-field');
            const requiredFields = document.querySelectorAll('.material-required-field');

            function syncMaterialFields() {
                const isMaterial = typeSelect && typeSelect.value === 'material';
                materialFields.forEach((field) => field.classList.toggle('d-none', !isMaterial));
                requiredFields.forEach((field) => {
                    field.required = isMaterial;
                    field.disabled = !isMaterial;
                });
            }

            typeSelect?.addEventListener('change', syncMaterialFields);
            syncMaterialFields();
        });
    </script>
@endpush
