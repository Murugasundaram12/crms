@extends('layouts.app')

@section('title', 'Add Expense')

@section('content')
@include('partials.alerts')

<div class="card border shadow-sm">
    <div class="card-body">
        <h4 class="mb-3">Add Expense</h4>

        <form action="{{ route('expense-transactions.store') }}" method="POST" enctype="multipart/form-data"
            class="row g-3">
            @csrf

            <div class="col-md-6">
                <label class="form-label">Main Category <span class="text-danger">*</span></label>
                <select name="main_category_id" id="main_category_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($mainCategories as $mc)
                        <option value="{{ $mc->id }}" @selected(old('main_category_id') == $mc->id)>{{ $mc->name }}</option>
                    @endforeach
                </select>
                @error('main_category_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select name="category_id" id="category_id" class="form-select" required>
                    <option value="">Select</option>
                    @foreach($categories ?? [] as $c)
                        <option value="{{ $c->id }}" data-main-ids='@json($c->mainCategories->pluck('id')->values())' @selected(old('category_id') == $c->id) hidden>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                @error('image')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label">Project</label>
                <select name="project_id" class="form-select">
                    <option value="">None</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
                @error('project_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" value="{{ old('description') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Paid Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="paid_amount" class="form-control"
                    value="{{ old('paid_amount') }}" required>
                @error('paid_amount')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                <select name="payment_mode" class="form-select" required>
                    <option value="">Select</option>
                    @foreach(['Cash', 'HDFC', 'SBI', 'Gpay', 'PhonePe', 'KVBL', 'Kotak Mahindra', 'TMB', 'Equitas'] as $pm)
                        <option value="{{ $pm }}" @selected(old('payment_mode') === $pm)>{{ $pm }}</option>
                    @endforeach
                </select>
                @error('payment_mode')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                @php($today = \Carbon\Carbon::now()->format('d/m/Y'))
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="text" name="current_date" class="form-control" value="{{ old('current_date', $today) }}"
                    required>
                <div class="form-text">Format: dd/mm/yyyy</div>
                @error('current_date')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                @php($now = \Carbon\Carbon::now()->format('h:i:s A'))
                <label class="form-label">Time <span class="text-danger">*</span></label>
                <input type="text" name="current_time" class="form-control" value="{{ old('current_time', $now) }}"
                    required>
                <div class="form-text">Example: 01:14:20 AM</div>
                @error('current_time')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="{{ route('expense-transactions.index') }}" class="btn btn-light">Back</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mainCategorySelect = document.getElementById('main_category_id');
        const categorySelect = document.getElementById('category_id');

        if (!mainCategorySelect || !categorySelect) {
            return;
        }

        const categoryOptions = Array.from(categorySelect.options).filter((option) => option.value !== '');

        const filterCategories = () => {
            const selectedMainCategoryId = mainCategorySelect.value;

            categoryOptions.forEach((option) => {
                let mainIds = [];
                try {
                    mainIds = JSON.parse(option.dataset.mainIds || '[]');
                } catch (e) {
                    mainIds = [];
                }

                const isMatch = selectedMainCategoryId && mainIds.includes(Number(selectedMainCategoryId));
                option.hidden = !isMatch;

                if (!isMatch && option.selected) {
                    option.selected = false;
                }
            });

            if (!selectedMainCategoryId) {
                categorySelect.value = '';
            }
        };

        mainCategorySelect.addEventListener('change', filterCategories);
        filterCategories();
    });
</script>
@endpush
