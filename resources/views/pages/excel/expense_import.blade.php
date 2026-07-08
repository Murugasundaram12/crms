@extends('layouts.app')

@section('title', 'Expense Import')

@push('styles')
    <style>
        .import-shell {
            max-width: 1120px;
            margin: 0 auto;
        }

        .import-panel {
            background: #fff;
            border: 1px solid #e9edf3;
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        }

        .import-dropzone {
            position: relative;
            display: flex;
            min-height: 230px;
            align-items: center;
            justify-content: center;
            border: 1px dashed #b8c3d6;
            border-radius: 8px;
            background: #f8fafc;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .import-dropzone:hover,
        .import-dropzone.is-active {
            border-color: #0d6efd;
            background: #f3f7ff;
        }

        .import-file-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .import-file-name {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .import-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .import-meta-item {
            border: 1px solid #edf1f6;
            border-radius: 8px;
            padding: 14px;
            background: #fff;
        }

        .import-format {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            border-radius: 6px;
            padding: 0 10px;
            background: #eef4ff;
            color: #1e4fd8;
            font-weight: 600;
            font-size: 12px;
        }

        @media (max-width: 767.98px) {
            .import-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @include('partials.alerts')

    <div class="import-shell">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1">Expense Import</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('expenses.history') }}">Expenses</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Import</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('expenses.history') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="ti ti-arrow-left me-1"></i>Expenses
            </a>
        </div>

        <div class="row g-4 align-items-stretch">
            <div class="col-lg-8">
                <div class="import-panel h-100">
                    <div class="p-4 border-bottom">
                        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                            <div>
                                <p class="text-muted mb-1">Bulk Upload</p>
                                <h5 class="mb-0">Import expense records</h5>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="import-format">XLSX</span>
                                <span class="import-format">XLS</span>
                                <span class="import-format">CSV</span>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('excel.import') }}" method="POST" enctype="multipart/form-data" class="p-4">
                        @csrf

                        <label class="form-label fw-semibold" for="expenseImportFile">Import File</label>
                        <div class="import-dropzone mb-3" id="expenseImportDropzone">
                            <input
                                type="file"
                                name="file"
                                id="expenseImportFile"
                                class="import-file-input @error('file') is-invalid @enderror"
                                accept=".xlsx,.xls,.csv"
                                required
                            >
                            <div class="text-center px-3">
                                <span class="avatar avatar-xl bg-primary-transparent text-primary mb-3">
                                    <i class="ti ti-file-spreadsheet fs-30"></i>
                                </span>
                                <h6 class="mb-1">Choose spreadsheet</h6>
                                <p class="text-muted mb-2">Maximum file size: 10 MB</p>
                                <div class="import-file-name fw-semibold text-dark" id="expenseImportFileName">
                                    No file selected
                                </div>
                            </div>
                        </div>
                        @error('file')
                            <div class="invalid-feedback d-block mb-3">{{ $message }}</div>
                        @enderror

                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div class="text-muted small">
                                <i class="ti ti-clock-hour-4 me-1"></i>Processing continues in the background
                            </div>
                            <div class="d-flex gap-2">
                                <button type="reset" class="btn btn-outline-secondary" id="expenseImportReset">
                                    <i class="ti ti-refresh me-1"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-upload me-1"></i>Import
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="import-panel h-100 p-4">
                    <p class="text-muted mb-1">File Checklist</p>
                    <h5 class="mb-3">Ready to import</h5>

                    <div class="import-meta">
                        <div class="import-meta-item">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="ti ti-calendar-event text-primary fs-18"></i>
                                <span class="fw-semibold">Date</span>
                            </div>
                            <p class="text-muted mb-0 small">Expense date column should contain valid dates.</p>
                        </div>
                        <div class="import-meta-item">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="ti ti-currency-rupee text-success fs-18"></i>
                                <span class="fw-semibold">Amount</span>
                            </div>
                            <p class="text-muted mb-0 small">Amount and paid amount should be numeric.</p>
                        </div>
                        <div class="import-meta-item">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="ti ti-tags text-info fs-18"></i>
                                <span class="fw-semibold">Category</span>
                            </div>
                            <p class="text-muted mb-0 small">Category names should match existing records.</p>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-4 mb-0">
                        <div class="d-flex gap-2">
                            <i class="ti ti-shield-check text-success fs-18 mt-1"></i>
                            <div>
                                <h6 class="mb-1">Permission protected</h6>
                                <p class="mb-0 text-muted small">Only users with expense create access can upload imports.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('expenseImportFile');
            const fileName = document.getElementById('expenseImportFileName');
            const dropzone = document.getElementById('expenseImportDropzone');
            const resetButton = document.getElementById('expenseImportReset');

            if (!fileInput || !fileName || !dropzone) {
                return;
            }

            fileInput.addEventListener('change', function () {
                fileName.textContent = fileInput.files.length ? fileInput.files[0].name : 'No file selected';
                dropzone.classList.toggle('is-active', fileInput.files.length > 0);
            });

            resetButton?.addEventListener('click', function () {
                window.setTimeout(function () {
                    fileName.textContent = 'No file selected';
                    dropzone.classList.remove('is-active');
                }, 0);
            });
        });
    </script>
@endpush
