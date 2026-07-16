@php($selectedTransferType = old('transfer_type', $assignment?->transfer_type ?? 'site_to_office'))

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tool Name</label>
                <select name="tool_material_id" class="form-select" required>
                    <option value="">Select Tool</option>
                    @foreach($toolsMaterials as $tool)
                        <option value="{{ $tool->id }}" @selected((string) old('tool_material_id', $assignment?->tool_material_id) === (string) $tool->id)>{{ $tool->name }}</option>
                    @endforeach
                </select>
                @error('tool_material_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Site Name</label>
                <select name="from_project_id" class="form-select" required>
                    <option value="">Select Site</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) old('from_project_id', $assignment?->from_project_id) === (string) $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('from_project_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Transfer</label>
                <select name="transfer_type" id="toolTransferType" class="form-select" required>
                    <option value="site_to_office" @selected($selectedTransferType === 'site_to_office')>Site to Office</option>
                    <option value="site_to_site" @selected($selectedTransferType === 'site_to_site')>Site to Site</option>
                </select>
                @error('transfer_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="toProjectField">
                <label class="form-label">To Site</label>
                <select name="to_project_id" class="form-select">
                    <option value="">Select To Site</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) old('to_project_id', $assignment?->to_project_id) === (string) $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('to_project_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Date & Time</label>
                <input type="datetime-local" name="transferred_at" class="form-control"
                    value="{{ old('transferred_at', $assignment?->transferred_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" required>
                @error('transferred_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">{{ $buttonText }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const transferType = document.getElementById('toolTransferType');
            const toProjectField = document.getElementById('toProjectField');

            function syncToProjectField() {
                if (!transferType || !toProjectField) {
                    return;
                }

                toProjectField.style.display = transferType.value === 'site_to_site' ? '' : 'none';
            }

            transferType?.addEventListener('change', syncToProjectField);
            syncToProjectField();
        });
    </script>
@endpush
