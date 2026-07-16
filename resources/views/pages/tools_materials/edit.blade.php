@extends('layouts.app')

@section('title', 'Edit Tool / Material')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h4 class="m-0">Edit Tool / Material</h4>
        <a href="{{ route('tools-materials.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <form method="POST" action="{{ route('tools-materials.update', $toolsMaterial) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <select name="item_type" class="form-select" required>
                            <option value="material" @selected(old('item_type', $toolsMaterial->item_type) === 'material')>Material</option>
                            <option value="tool" @selected(old('item_type', $toolsMaterial->item_type) === 'tool')>Tool</option>
                        </select>
                        @error('item_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">SKU / Code</label>
                        <input type="text" name="sku" class="form-control" value="{{ old('sku', $toolsMaterial->sku) }}">
                        @error('sku')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $toolsMaterial->name) }}" required>
                        @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', $toolsMaterial->date?->toDateString()) }}" required>
                        @error('date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" class="form-control" value="{{ old('unit', $toolsMaterial->unit) }}" required>
                        @error('unit')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Opening Quantity</label>
                        <input type="number" step="0.01" name="opening_quantity" class="form-control" value="{{ old('opening_quantity', $toolsMaterial->opening_quantity) }}">
                        @error('opening_quantity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Opening Rate</label>
                        <input type="number" step="0.01" name="opening_rate" class="form-control" value="{{ old('opening_rate', $toolsMaterial->opening_rate) }}">
                        @error('opening_rate')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" step="0.01" name="reorder_level" class="form-control" value="{{ old('reorder_level', $toolsMaterial->reorder_level) }}">
                        @error('reorder_level')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <label class="d-flex align-items-center gap-2 mb-2">
                            <input type="hidden" name="active_status" value="0">
                            <input type="checkbox" name="active_status" value="1" @checked(old('active_status', $toolsMaterial->active_status))>
                            Active
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        @error('image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        @if($toolsMaterial->image_path)
                            <label class="form-label">Current Image</label>
                            <div><img src="{{ asset('storage/' . $toolsMaterial->image_path) }}" alt="{{ $toolsMaterial->name }}" class="rounded border" style="width: 88px; height: 88px; object-fit: cover;"></div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $toolsMaterial->description) }}</textarea>
                        @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-primary" type="submit">Update</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
