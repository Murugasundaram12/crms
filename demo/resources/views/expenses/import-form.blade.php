<form action="{{ route('expenses.import') }}" method="POST" enctype="multipart/form-data" class="d-inline-flex align-items-center gap-2">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required style="max-width: 220px;">
    <button type="submit" class="btn btn-info btn-sm text-white">
        Import Excel
    </button>
</form>
