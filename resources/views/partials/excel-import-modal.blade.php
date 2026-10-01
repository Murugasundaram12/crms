@php
    $importTitle = $title ?? ucwords(str_replace(['_', '-'], ' ', $module));
    $importResult = session('excel_import_result_'.$module);
@endphp
<a href="#excelImportModal_{{ $module }}" data-bs-toggle="modal" class="btn btn-outline-primary">Import</a>
<div class="modal fade" id="excelImportModal_{{ $module }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
        <div class="modal-header"><h5 class="modal-title">Import {{ $importTitle }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><form action="{{ $action }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label class="form-label">Excel / CSV file</label><input class="form-control mb-2" type="file" name="file" accept=".xlsx,.xls,.csv" required>
            <div class="form-text mb-3">Invalid rows are skipped and reported with their row number and reason.</div>
            <a href="{{ $sampleUrl }}" class="btn btn-link px-0">Download sample Excel</a>
            <div class="d-flex justify-content-end gap-2 mt-3"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Import</button></div>
        </form></div>
    </div></div>
</div>
@if ($importResult)
    <div class="alert alert-{{ ($importResult['failed'] ?? 0) ? 'warning' : 'success' }} border-0 shadow-sm mt-3">
        <div class="fw-semibold mb-1">{{ $importTitle }} import {{ str_replace('_', ' ', $importResult['status']) }}</div>
        <div>Imported: {{ $importResult['imported'] }} &nbsp; Updated: {{ $importResult['updated'] }} &nbsp; Skipped: {{ $importResult['skipped'] }} &nbsp; Failed: {{ $importResult['failed'] }}</div>
        @if (!empty($importResult['errors']))<details class="mt-2"><summary>Show row issues</summary><ul class="mb-0 mt-2">@foreach ($importResult['errors'] as $error)<li>Row {{ $error['row'] ?? '-' }}: {{ $error['message'] }}</li>@endforeach</ul></details>@endif
    </div>
@endif
