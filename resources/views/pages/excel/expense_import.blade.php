@extends('layouts.app')

@section('title', 'Create Employee Salary')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Expense Import</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Expense Import</li>
                </ol>
            </nav>
        </div>
        {{-- <a href="{{ route('employee-salaries.index') }}" class="btn btn-outline-light shadow-sm">Back to Employee
            Salaries</a> --}}
    </div>
    {{-- <div class="alert alert-info">
        Upload request 60 seconds-kulla finish aagum. Data import background queue-la continue aagum.
    </div> --}}

    <form action="{{ route('excel.import') }}" method="POST" enctype="multipart/form-data">

        @csrf

        <input type="file" name="file" required>

        <button type="submit" class="btn btn-primary">
            Import
        </button>

    </form>



@endsection
