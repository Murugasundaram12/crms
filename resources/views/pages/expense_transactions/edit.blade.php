@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')
@include('partials.alerts')

<div class="card border shadow-sm">
    <div class="card-body">
        <h4 class="mb-3">Edit Expense</h4>

        <form action="{{ route('expense-transactions.update', $expenseTransaction) }}" method="POST"
            enctype="multipart/form-data" class="row g-3" novalidate>
            @csrf
            @method('PUT')

            <div class="col-md-6">
                <label class="form-label">Main Category <span class="text-danger">*</span></label>
                <select name="main_category_id" class="form-select @error('main_category_id') is-invalid @enderror" @error('main_category_id') aria-describedby="edit_expense_main_category_id_error" aria-invalid="true" @enderror required>
                    @foreach($mainCategories as $mc)
                        <option value="{{ $mc->id }}" @selected(old('main_category_id', $expenseTransaction->main_category_id) == $mc->id)>{{ $mc->name }}</option>
                    @endforeach
                </select>
                @error('main_category_id')<div class="invalid-feedback d-block" id="edit_expense_main_category_id_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" @error('category_id') aria-describedby="edit_expense_category_id_error" aria-invalid="true" @enderror required>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id', $expenseTransaction->category_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<div class="invalid-feedback d-block" id="edit_expense_category_id_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*" @error('image') aria-describedby="edit_expense_image_error" aria-invalid="true" @enderror>
                @if($expenseTransaction->image_path)
                    <div class="mt-2">
                        <a href="{{ asset('storage/' . $expenseTransaction->image_path) }}" target="_blank"
                            class="small">Current image</a>
                    </div>
                @endif
                @error('image')<div class="invalid-feedback d-block" id="edit_expense_image_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label">Project</label>
                <select name="project_id" class="form-select @error('project_id') is-invalid @enderror" @error('project_id') aria-describedby="edit_expense_project_id_error" aria-invalid="true" @enderror>
                    <option value="">None</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected(old('project_id', $expenseTransaction->project_id) == $p->id)>
                            {{ $p->name }}</option>
                    @endforeach
                </select>
                @error('project_id')<div class="invalid-feedback d-block" id="edit_expense_project_id_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                    value="{{ old('description', $expenseTransaction->description) }}" @error('description') aria-describedby="edit_expense_description_error" aria-invalid="true" @enderror>
                @error('description')<div class="invalid-feedback d-block" id="edit_expense_description_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Paid Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror"
                    value="{{ old('paid_amount', $expenseTransaction->paid_amount) }}" @error('paid_amount') aria-describedby="edit_expense_paid_amount_error" aria-invalid="true" @enderror required>
                @error('paid_amount')<div class="invalid-feedback d-block" id="edit_expense_paid_amount_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                <select name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror" @error('payment_method_id') aria-describedby="edit_expense_payment_method_id_error" aria-invalid="true" @enderror required>
                    @foreach($paymentMethods as $pm)
                        <option value="{{ $pm->id }}" @selected((string) old('payment_method_id', $expenseTransaction->payment_method_id) === (string) $pm->id)>
                            {{ $pm->name }}</option>
                    @endforeach
                </select>
                @error('payment_method_id')<div class="invalid-feedback d-block" id="edit_expense_payment_method_id_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                @php($currentDate = \Carbon\Carbon::parse($expenseTransaction->current_date)->format('d/m/Y'))
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="text" name="current_date" class="form-control @error('current_date') is-invalid @enderror"
                    value="{{ old('current_date', $currentDate) }}" @error('current_date') aria-describedby="edit_expense_current_date_error" aria-invalid="true" @enderror required>
                <div class="form-text">Format: dd/mm/yyyy</div>
                @error('current_date')<div class="invalid-feedback d-block" id="edit_expense_current_date_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Time <span class="text-danger">*</span></label>
                <input type="text" name="current_time" class="form-control @error('current_time') is-invalid @enderror"
                    value="{{ old('current_time', $expenseTransaction->current_time) }}" @error('current_time') aria-describedby="edit_expense_current_time_error" aria-invalid="true" @enderror required>
                <div class="form-text">Example: 01:14:20 AM</div>
                @error('current_time')<div class="invalid-feedback d-block" id="edit_expense_current_time_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="{{ route('expense-transactions.index') }}" class="btn btn-light">Back</a>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>

        </form>
    </div>
</div>
@endsection