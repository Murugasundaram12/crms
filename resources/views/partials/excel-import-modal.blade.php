@php
    $importTitle = $title ?? ucwords(str_replace(['_', '-'], ' ', $module));
    $importResult = session('excel_import_result_'.$module);
@endphp
<a href="#excelImportModal_{{ $module }}" data-bs-toggle="modal" class="btn btn-outline-primary">Import</a>
<div class="modal fade" id="excelImportModal_{{ $module }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
        <div class="modal-header"><h5 class="modal-title">Import {{ $importTitle }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><form action="{{ $action }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="_modal_target" value="#excelImportModal_{{ $module }}">
            <label class="form-label">Excel / CSV file <span class="text-danger">*</span></label>
            <input class="form-control mb-2 @error('file') is-invalid @enderror" type="file" name="file" accept=".xlsx,.xls,.csv" @error('file') aria-describedby="excel_import_{{ $module }}_file_error" aria-invalid="true" @enderror required>
            @error('file')<div class="invalid-feedback d-block mb-2" id="excel_import_{{ $module }}_file_error">{{ $message }}</div>@enderror
            <div class="form-text mb-3">Invalid rows are skipped and reported with their row number and reason.</div>
            <a href="{{ $sampleUrl }}" class="btn btn-link px-0">Download sample Excel</a>
            <div class="d-flex justify-content-end gap-2 mt-3"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Import</button></div>
        </form></div>
    </div></div>
</div>
@if ($errors->any() && old('_modal_target') === '#excelImportModal_'.$module)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.getElementById('excelImportModal_{{ $module }}');
            if (el && window.bootstrap) (new bootstrap.Modal(el)).show();
        });
    </script>
@endif
@if ($importResult)
    @if (!empty($importResult['failed']) || !empty($importResult['errors']))
        <div class="alert alert-warning border-0 shadow-sm mt-3">
            <div class="fw-semibold mb-1">{{ $importTitle }} import {{ str_replace('_', ' ', $importResult['status']) }}</div>
            <div>Imported: {{ $importResult['imported'] }} &nbsp; Updated: {{ $importResult['updated'] }} &nbsp; Skipped: {{ $importResult['skipped'] }} &nbsp; Failed: {{ $importResult['failed'] }}</div>
            @if (!empty($importResult['errors']))<details class="mt-2"><summary>Show row issues</summary><ul class="mb-0 mt-2">@foreach ($importResult['errors'] as $error)<li>Row {{ $error['row'] ?? '-' }}: {{ $error['message'] }}</li>@endforeach</ul></details>@endif
        </div>
    @else
        @php
            $toastMsg = ($importTitle ?? 'Excel') . ' import ' . str_replace('_', ' ', $importResult['status'] ?? 'completed') . ' — Imported: ' . ($importResult['imported'] ?? 0) . ', Updated: ' . ($importResult['updated'] ?? 0) . ', Skipped: ' . ($importResult['skipped'] ?? 0) . ', Failed: ' . ($importResult['failed'] ?? 0);
        @endphp
        <script>
            window.__crmQueuedToasts = window.__crmQueuedToasts || [];
            window.__crmQueuedToasts.push(@json($toastMsg));
        </script>
    @endif
@endif
