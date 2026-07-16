@extends('layouts.app')

@section('title', 'Edit Tool Transfer')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h4 class="m-0">Edit Tool Transfer</h4>
        <a href="{{ route('tools-material-assignments.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    <form method="POST" action="{{ route('tools-material-assignments.update', $assignment) }}">
        @csrf
        @method('PUT')
        @include('pages.tool_material_assignments.form', [
            'assignment' => $assignment,
            'buttonText' => 'Update',
        ])
    </form>
@endsection
