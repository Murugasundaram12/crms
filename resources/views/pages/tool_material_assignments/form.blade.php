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
                <input type="text" name="reference_no" id="reference_no" class="form-control @error('reference_no') is-invalid @enderror"
                    value="{{ old('reference_no', $assignment?->reference_no) }}" placeholder="Auto generated if empty"
                    @error('reference_no') aria-describedby="reference_no_error" aria-invalid="true" @enderror>
                @error('reference_no')<div class="invalid-feedback d-block" id="reference_no_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror"
                    @error('status') aria-describedby="status_error" aria-invalid="true" @enderror required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback d-block" id="status_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Tool / Material <span class="text-danger">*</span></label>
                <select name="tool_material_id" id="tool_material_id" class="form-select @error('tool_material_id') is-invalid @enderror"
                    @error('tool_material_id') aria-describedby="tool_material_id_error" aria-invalid="true" @enderror required>
                    <option value="">Select Tool / Material</option>
                    @foreach($toolsMaterials as $tool)
                        <option value="{{ $tool->id }}" @selected((string) $selectedToolMaterialId === (string) $tool->id)>
                            {{ $tool->name }} - Balance {{ number_format($tool->stock_quantity, 2) }} {{ $tool->unit }}
                        </option>
                    @endforeach
                </select>
                @error('tool_material_id')<div class="invalid-feedback d-block" id="tool_material_id_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Transaction <span class="text-danger">*</span></label>
                @if($lockTransaction)
                    <input type="hidden" name="transaction_type" value="{{ $selectedTransactionType }}">
                @endif
                <select name="{{ $lockTransaction ? '_transaction_type_display' : 'transaction_type' }}"
                    id="toolTransactionType" class="form-select @error('transaction_type') is-invalid @enderror"
                    @error('transaction_type') aria-describedby="transaction_type_error" aria-invalid="true" @enderror required @disabled($lockTransaction)>
                    @foreach($transactionTypes as $value => $label)
                        <option value="{{ $value }}" @selected($selectedTransactionType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('transaction_type')<div class="invalid-feedback d-block" id="transaction_type_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="fromProjectField">
                <label class="form-label">From Site</label>
                <select name="from_project_id" id="from_project_id" class="form-select @error('from_project_id') is-invalid @enderror"
                    @error('from_project_id') aria-describedby="from_project_id_error" aria-invalid="true" @enderror>
                    <option value="">Select From Site</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) old('from_project_id', $assignment?->from_project_id ?? ($prefill['from_project_id'] ?? null)) === (string) $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('from_project_id')<div class="invalid-feedback d-block" id="from_project_id_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="sourceTypeField">
                <label class="form-label">Source</label>
                <select name="source_type" id="toolSourceType" class="form-select @error('source_type') is-invalid @enderror"
                    @error('source_type') aria-describedby="source_type_error" aria-invalid="true" @enderror>
                    <option value="office" @selected($selectedSourceType === 'office')>Office</option>
                    <option value="site" @selected($selectedSourceType === 'site')>Site</option>
                </select>
                @error('source_type')<div class="invalid-feedback d-block" id="source_type_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="destinationTypeField">
                <label class="form-label">Destination</label>
                <select name="destination_type" id="toolDestinationType" class="form-select @error('destination_type') is-invalid @enderror"
                    @error('destination_type') aria-describedby="destination_type_error" aria-invalid="true" @enderror>
                    <option value="office" @selected($selectedDestinationType === 'office')>Office</option>
                    <option value="site" @selected($selectedDestinationType === 'site')>Site</option>
                </select>
                @error('destination_type')<div class="invalid-feedback d-block" id="destination_type_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="toProjectField">
                <label class="form-label">To Site</label>
                <select name="to_project_id" id="to_project_id" class="form-select @error('to_project_id') is-invalid @enderror"
                    @error('to_project_id') aria-describedby="to_project_id_error" aria-invalid="true" @enderror>
                    <option value="">Select To Site</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) old('to_project_id', $assignment?->to_project_id ?? ($prefill['to_project_id'] ?? null)) === (string) $project->id)>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('to_project_id')<div class="invalid-feedback d-block" id="to_project_id_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="vendorField">
                <label class="form-label">Vendor</label>
                <select name="vendor_id" id="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror"
                    @error('vendor_id') aria-describedby="vendor_id_error" aria-invalid="true" @enderror>
                    <option value="">Select Vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $assignment?->vendor_id ?? ($prefill['vendor_id'] ?? null)) === (string) $vendor->id)>{{ $vendor->name }}</option>
                    @endforeach
                </select>
                @error('vendor_id')<div class="invalid-feedback d-block" id="vendor_id_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="paymentMethodField">
                <label class="form-label">Payment Method</label>
                <select name="payment_method_id" id="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror"
                    @error('payment_method_id') aria-describedby="payment_method_id_error" aria-invalid="true" @enderror>
                    <option value="">Select Payment Method</option>
                    @foreach($paymentMethods as $paymentMethod)
                        <option value="{{ $paymentMethod->id }}" @selected((string) old('payment_method_id', $assignment?->payment_method_id ?? null) === (string) $paymentMethod->id)>
                            {{ $paymentMethod->name }}
                        </option>
                    @endforeach
                </select>
                @error('payment_method_id')<div class="invalid-feedback d-block" id="payment_method_id_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6" id="paidAmountField">
                <label class="form-label">Paid Amount</label>
                <input type="number" step="0.01" name="advance_amount" id="toolPaidAmount" class="form-control @error('advance_amount') is-invalid @enderror"
                    value="{{ old('advance_amount', $assignment?->advance_amount ?? 0) }}"
                    @error('advance_amount') aria-describedby="advance_amount_error" aria-invalid="true" @enderror>
                @error('advance_amount')<div class="invalid-feedback d-block" id="advance_amount_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="quantity" id="toolQuantity" class="form-control @error('quantity') is-invalid @enderror"
                    value="{{ old('quantity', $assignment?->quantity ?? ($prefill['quantity'] ?? 1)) }}"
                    @error('quantity') aria-describedby="quantity_error" aria-invalid="true" @enderror required>
                @error('quantity')<div class="invalid-feedback d-block" id="quantity_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Rate <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="rate" id="toolRate" class="form-control @error('rate') is-invalid @enderror"
                    value="{{ old('rate', $assignment?->rate ?? ($prefill['rate'] ?? 0)) }}"
                    @error('rate') aria-describedby="rate_error" aria-invalid="true" @enderror required>
                @error('rate')<div class="invalid-feedback d-block" id="rate_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" name="amount" id="toolAmount" class="form-control @error('amount') is-invalid @enderror"
                    value="{{ old('amount', $assignment?->amount ?? ($prefill['amount'] ?? 0)) }}"
                    @error('amount') aria-describedby="amount_error" aria-invalid="true" @enderror>
                @error('amount')<div class="invalid-feedback d-block" id="amount_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Receiver Name</label>
                <select name="receiver_name" id="receiver_name" class="form-select @error('receiver_name') is-invalid @enderror"
                    @error('receiver_name') aria-describedby="receiver_name_error" aria-invalid="true" @enderror>
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
                @error('receiver_name')<div class="invalid-feedback d-block" id="receiver_name_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Vehicle No</label>
                <input type="text" name="vehicle_no" id="vehicle_no" class="form-control @error('vehicle_no') is-invalid @enderror"
                    value="{{ old('vehicle_no', $assignment?->vehicle_no ?? ($prefill['vehicle_no'] ?? null)) }}"
                    @error('vehicle_no') aria-describedby="vehicle_no_error" aria-invalid="true" @enderror>
                @error('vehicle_no')<div class="invalid-feedback d-block" id="vehicle_no_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label">Purpose</label>
                <input type="text" name="purpose" id="purpose" class="form-control @error('purpose') is-invalid @enderror"
                    value="{{ old('purpose', $assignment?->purpose ?? ($prefill['purpose'] ?? null)) }}"
                    @error('purpose') aria-describedby="purpose_error" aria-invalid="true" @enderror>
                @error('purpose')<div class="invalid-feedback d-block" id="purpose_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Date & Time <span class="text-danger">*</span></label>
                <input type="datetime-local" name="transferred_at" id="transferred_at" class="form-control @error('transferred_at') is-invalid @enderror"
                    value="{{ old('transferred_at', $assignment?->transferred_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                    @error('transferred_at') aria-describedby="transferred_at_error" aria-invalid="true" @enderror required>
                @error('transferred_at')<div class="invalid-feedback d-block" id="transferred_at_error">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Notes</label>
                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror"
                    rows="2" @error('notes') aria-describedby="notes_error" aria-invalid="true" @enderror>{{ old('notes', $assignment?->notes ?? ($prefill['notes'] ?? null)) }}</textarea>
                @error('notes')<div class="invalid-feedback d-block" id="notes_error">{{ $message }}</div>@enderror
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
            const paymentMethodField = document.getElementById('paymentMethodField');
            const paidAmountField = document.getElementById('paidAmountField');
            const paidAmount = document.getElementById('toolPaidAmount');
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
                const paymentMethodVisible = ['purchase', 'return_to_vendor'].includes(type);

                if (sourceTypeField) sourceTypeField.style.display = sourceChoiceVisible ? '' : 'none';
                if (destinationTypeField) destinationTypeField.style.display = destinationChoiceVisible ? '' : 'none';
                if (fromProjectField) fromProjectField.style.display = fromSiteVisible ? '' : 'none';
                if (toProjectField) toProjectField.style.display = toSiteVisible ? '' : 'none';
                if (vendorField) vendorField.style.display = vendorVisible ? '' : 'none';
                if (paymentMethodField) paymentMethodField.style.display = paymentMethodVisible ? '' : 'none';
                if (paidAmountField) paidAmountField.style.display = paymentMethodVisible ? '' : 'none';
                if (!paymentMethodVisible && paidAmount) paidAmount.value = '0';
            }

            let amountTouched = false;
            let paidTouched = Number(paidAmount?.value || 0) > 0;

            function syncAmount() {
                const calculated = (Number(quantity?.value || 0) * Number(rate?.value || 0)).toFixed(2);
                if (amount && !amountTouched) {
                    amount.value = calculated;
                }
                if (paidAmount && !paidTouched && transactionType?.value === 'purchase') {
                    paidAmount.value = calculated;
                }
            }

            transactionType?.addEventListener('change', syncTransactionFields);
            sourceType?.addEventListener('change', syncTransactionFields);
            destinationType?.addEventListener('change', syncTransactionFields);
            quantity?.addEventListener('input', syncAmount);
            rate?.addEventListener('input', syncAmount);
            amount?.addEventListener('input', function () {
                amountTouched = true;
                if (paidAmount && !paidTouched && transactionType?.value === 'purchase') {
                    paidAmount.value = Number(amount.value || 0).toFixed(2);
                }
            });
            paidAmount?.addEventListener('input', function () {
                paidTouched = true;
            });
            syncTransactionFields();
            syncAmount();
        });
    </script>
@endpush
