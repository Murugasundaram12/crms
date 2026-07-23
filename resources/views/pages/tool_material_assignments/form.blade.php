@php($prefill = $prefill ?? [])
@php($selectedTransactionType = old('transaction_type', $assignment?->transaction_type ?? ($prefill['transaction_type'] ?? 'issue_to_site')))
@php($selectedSourceType = old('source_type', $assignment?->source_type ?? ($prefill['source_type'] ?? 'office')))
@php($selectedDestinationType = old('destination_type', $assignment?->destination_type ?? ($prefill['destination_type'] ?? 'site')))
@php($selectedToolMaterialId = old('tool_material_id', $assignment?->tool_material_id ?? ($selectedToolMaterialId ?? null)))
@php($selectedStatus = old('status', $assignment?->status ?? ($prefill['status'] ?? 'transferred')))
@php($lockTransaction = filter_var($prefill['lock_transaction'] ?? false, FILTER_VALIDATE_BOOLEAN))

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Reference No</label>
                <input type="text" name="reference_no" class="form-control"
                    value="{{ old('reference_no', $assignment?->reference_no) }}" placeholder="Auto generated if empty">
                @error('reference_no')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Tool / Material</label>
                <select name="tool_material_id" class="form-select" required>
                    <option value="">Select Tool / Material</option>
                    @foreach($toolsMaterials as $tool)
                        <option value="{{ $tool->id }}" @selected((string) $selectedToolMaterialId === (string) $tool->id)>
                            {{ $tool->name }} - Balance {{ number_format($tool->stock_quantity, 2) }} {{ $tool->unit }}
                        </option>
                    @endforeach
                </select>
                @error('tool_material_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Transaction</label>
                @if($lockTransaction)
                    <input type="hidden" name="transaction_type" value="{{ $selectedTransactionType }}">
                @endif
                <select name="{{ $lockTransaction ? '_transaction_type_display' : 'transaction_type' }}"
                    id="toolTransactionType" class="form-select" required @disabled($lockTransaction)>
                    @foreach($transactionTypes as $value => $label)
                        <option value="{{ $value }}" @selected($selectedTransactionType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('transaction_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="fromProjectField">
                <label class="form-label">From Site</label>
                <select name="from_project_id" class="form-select">
                    <option value="">Select From Site</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) old('from_project_id', $assignment?->from_project_id ?? ($prefill['from_project_id'] ?? null)) === (string) $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('from_project_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="sourceTypeField">
                <label class="form-label">Source</label>
                <select name="source_type" id="toolSourceType" class="form-select">
                    <option value="office" @selected($selectedSourceType === 'office')>Office</option>
                    <option value="site" @selected($selectedSourceType === 'site')>Site</option>
                </select>
                @error('source_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="destinationTypeField">
                <label class="form-label">Destination</label>
                <select name="destination_type" id="toolDestinationType" class="form-select">
                    <option value="office" @selected($selectedDestinationType === 'office')>Office</option>
                    <option value="site" @selected($selectedDestinationType === 'site')>Site</option>
                </select>
                @error('destination_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="toProjectField">
                <label class="form-label">To Site</label>
                <select name="to_project_id" class="form-select">
                    <option value="">Select To Site</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) old('to_project_id', $assignment?->to_project_id ?? ($prefill['to_project_id'] ?? null)) === (string) $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('to_project_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="vendorField">
                <label class="form-label">Vendor</label>
                <select name="vendor_id" class="form-select">
                    <option value="">Select Vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $assignment?->vendor_id ?? ($prefill['vendor_id'] ?? null)) === (string) $vendor->id)>{{ $vendor->name }}</option>
                    @endforeach
                </select>
                @error('vendor_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Quantity</label>
                <input type="number" step="0.01" name="quantity" id="toolQuantity" class="form-control"
                    value="{{ old('quantity', $assignment?->quantity ?? ($prefill['quantity'] ?? 1)) }}" required>
                @error('quantity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Rate</label>
                <input type="number" step="0.01" name="rate" id="toolRate" class="form-control"
                    value="{{ old('rate', $assignment?->rate ?? ($prefill['rate'] ?? 0)) }}" required>
                @error('rate')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" name="amount" id="toolAmount" class="form-control"
                    value="{{ old('amount', $assignment?->amount ?? ($prefill['amount'] ?? 0)) }}">
                @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Receiver Name</label>
                <select name="receiver_name" class="form-select">
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->name }}" @selected((string) old('receiver_name', $assignment?->receiver_name ?? ($prefill['receiver_name'] ?? null)) === (string) $employee->name)>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                    @if($assignment?->receiver_name && !$employees->contains('name', $assignment->receiver_name))
                        <option value="{{ $assignment->receiver_name }}" selected>{{ $assignment->receiver_name }}</option>
                    @endif
                </select>
                @error('receiver_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Vehicle No</label>
                <input type="text" name="vehicle_no" class="form-control"
                    value="{{ old('vehicle_no', $assignment?->vehicle_no ?? ($prefill['vehicle_no'] ?? null)) }}">
                @error('vehicle_no')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Purpose</label>
                <input type="text" name="purpose" class="form-control"
                    value="{{ old('purpose', $assignment?->purpose ?? ($prefill['purpose'] ?? null)) }}">
                @error('purpose')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Date & Time</label>
                <input type="datetime-local" name="transferred_at" class="form-control"
                    value="{{ old('transferred_at', $assignment?->transferred_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                    required>
                @error('transferred_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control"
                    rows="2">{{ old('notes', $assignment?->notes ?? ($prefill['notes'] ?? null)) }}</textarea>
                @error('notes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
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
            const transactionType = document.getElementById('toolTransactionType');
            const sourceType = document.getElementById('toolSourceType');
            const destinationType = document.getElementById('toolDestinationType');
            const sourceTypeField = document.getElementById('sourceTypeField');
            const destinationTypeField = document.getElementById('destinationTypeField');
            const fromProjectField = document.getElementById('fromProjectField');
            const toProjectField = document.getElementById('toProjectField');
            const vendorField = document.getElementById('vendorField');
            const quantity = document.getElementById('toolQuantity');
            const rate = document.getElementById('toolRate');
            const amount = document.getElementById('toolAmount');

            function syncTransactionFields() {
                if (!transactionType) {
                    return;
                }

                const type = transactionType.value;
                const sourceChoiceVisible = ['return_to_vendor', 'damage_wastage'].includes(type);
                const destinationChoiceVisible = type === 'purchase';
                const fromSiteVisible = ['return_to_office', 'site_to_site'].includes(type) || (sourceChoiceVisible && sourceType?.value === 'site');
                const toSiteVisible = ['issue_to_site', 'site_to_site'].includes(type) || (destinationChoiceVisible && destinationType?.value === 'site');
                const vendorVisible = ['purchase', 'return_to_vendor'].includes(type);

                if (sourceTypeField) sourceTypeField.style.display = sourceChoiceVisible ? '' : 'none';
                if (destinationTypeField) destinationTypeField.style.display = destinationChoiceVisible ? '' : 'none';
                if (fromProjectField) fromProjectField.style.display = fromSiteVisible ? '' : 'none';
                if (toProjectField) toProjectField.style.display = toSiteVisible ? '' : 'none';
                if (vendorField) vendorField.style.display = vendorVisible ? '' : 'none';
            }

            let amountTouched = false;

            function syncAmount() {
                const calculated = (Number(quantity?.value || 0) * Number(rate?.value || 0)).toFixed(2);
                if (amount && !amountTouched) {
                    amount.value = calculated;
                }
            }

            transactionType?.addEventListener('change', syncTransactionFields);
            sourceType?.addEventListener('change', syncTransactionFields);
            destinationType?.addEventListener('change', syncTransactionFields);
            quantity?.addEventListener('input', syncAmount);
            rate?.addEventListener('input', syncAmount);
            amount?.addEventListener('input', function () {
                amountTouched = true;
            });
            syncTransactionFields();
            syncAmount();
        });
    </script>
@endpush
