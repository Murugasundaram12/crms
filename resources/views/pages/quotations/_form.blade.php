@php
    $isEdit = isset($quotation);
    $selectedClientId = old('client_id', $quotation->client_id ?? '');
    $selectedProjectId = old('project_id', $quotation->project_id ?? '');
    $initialGroups = old('items');

    if (!$initialGroups) {
        $initialGroups = $groupedItems ?? [
            [
                'main_title' => old('main_title', ''),
                'rows' => [
                    [
                        'description' => old('sub_title', ''),
                        'nos' => null,
                        'length' => null,
                        'breadth' => null,
                        'depth' => null,
                        'quantity' => 0,
                        'unit' => '',
                        'price' => 0,
                        'amount' => 0,
                    ],
                ],
            ],
        ];
    }

    $clientsPayload = $clients->mapWithKeys(function ($client) {
        $fullAddress = collect([$client->address, $client->city, $client->state, $client->country])
            ->filter()
            ->implode(', ');

        return [
            $client->id => [
                'id' => $client->id,
                'name' => $client->name,
                'company_name' => $client->company_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'address' => $client->address,
                'city' => $client->city,
                'state' => $client->state,
                'country' => $client->country,
                'full_address' => $fullAddress,
            ],
        ];
    });

    $currentClient = $selectedClientId ? $clients->firstWhere('id', (int) $selectedClientId) : null;
    $currentAddress = old('client_address', $quotation->client_address ?? collect([
        optional($currentClient)->address,
        optional($currentClient)->city,
        optional($currentClient)->state,
        optional($currentClient)->country,
    ])->filter()->implode(', '));
    $currentTerms = old('terms', isset($quotation)
        ? $quotation->terms->pluck('term_text')->implode("\n")
        : '');
    $unitOptions = ($units ?? collect())->map(fn($unit) => [
        'code' => $unit->code,
        'label' => $unit->display_name,
    ])->values();
@endphp

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card border quotation-form-card h-100">
            <div class="card-body">
                <h5 class="card-title mb-4">Client & Quotation Details</h5>

                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Client <span class="text-danger">*</span></label>
                        <select id="quotation-client-select" name="client_id"
                            class="form-select @error('client_id') is-invalid @enderror" required>
                            <option value="">Select Client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected($selectedClientId == $client->id)>
                                    {{ $client->name }}</option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Project Name</label>
                        <select id="quotation-project-select" name="project_id"
                            class="form-select @error('project_id') is-invalid @enderror"
                            data-selected-project="{{ $selectedProjectId }}">
                            <option value="">Select Project</option>
                        </select>
                        @error('project_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Quotation</label>
                        <select id="quotation-select" name="quotation_id"
                            class="form-select @error('quotation_id') is-invalid @enderror">
                            <option value="">Select Quotation</option>
                        </select>
                        @error('quotation_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Quotation Create Date <span class="text-danger">*</span></label>
                        <input type="date" name="quotation_date"
                            class="form-control @error('quotation_date') is-invalid @enderror"
                            value="{{ old('quotation_date', optional($quotation->quotation_date ?? now())->format('Y-m-d')) }}"
                            required>
                        @error('quotation_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Quote Validity (Days)</label>
                        <input type="number" name="validity_days"
                            class="form-control @error('validity_days') is-invalid @enderror"
                            value="{{ old('validity_days', $quotation->validity_days ?? 30) }}" min="1" max="365">
                        @error('validity_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Quotation Title <span class="text-danger">*</span></label>
                        <input type="text" name="quotation_title"
                            class="form-control @error('quotation_title') is-invalid @enderror"
                            value="{{ old('quotation_title', $quotation->quotation_title ?? '') }}" required>
                        @error('quotation_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Client Name <span class="text-danger">*</span></label>
                        <input type="text" id="quotation-client-name" name="client_name"
                            class="form-control @error('client_name') is-invalid @enderror"
                            value="{{ old('client_name', $quotation->client_name ?? optional($currentClient)->name) }}"
                            required>
                        @error('client_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Company</label>
                        <input type="text" id="quotation-client-company" class="form-control"
                            value="{{ old('client_company_name', optional($currentClient)->company_name) }}" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="text" id="quotation-client-email" class="form-control"
                            value="{{ old('client_email', optional($currentClient)->email) }}" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" id="quotation-client-phone" class="form-control"
                            value="{{ old('client_phone', optional($currentClient)->phone) }}" readonly>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Client Address <span class="text-danger">*</span></label>
                        <textarea id="quotation-client-address" name="client_address"
                            class="form-control @error('client_address') is-invalid @enderror" rows="3"
                            required>{{ $currentAddress }}</textarea>
                        @error('client_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Work Start Date</label>
                        <input type="date" name="start_date"
                            class="form-control @error('start_date') is-invalid @enderror"
                            value="{{ old('start_date', optional($quotation->start_date ?? null)->format('Y-m-d')) }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Work Duration (Days)</label>
                        <input type="number" name="duration_days"
                            class="form-control @error('duration_days') is-invalid @enderror"
                            value="{{ old('duration_days', $quotation->duration_days ?? 1) }}" min="1">
                        @error('duration_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Proposal Content</label>
                        <textarea name="proposal_content"
                            class="form-control @error('proposal_content') is-invalid @enderror"
                            rows="4">{{ old('proposal_content', $quotation->proposal_content ?? '') }}</textarea>
                        @error('proposal_content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                            rows="4">{{ old('notes', $quotation->notes ?? '') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea name="terms" class="form-control @error('terms') is-invalid @enderror"
                            rows="6" placeholder="Enter one term per line">{{ $currentTerms }}</textarea>
                        @error('terms')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card border quotation-form-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                    <div>
                        <h5 class="card-title mb-1">Main Title & Sub Title Structure</h5>
                        <p class="text-muted mb-0">Add grouped sections and sub-items with automatic totals.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="add-main-title">
                        <i class="ti ti-plus me-1"></i>Main Title
                    </button>
                </div>

                @error('items')
                    <div class="alert alert-danger py-2">{{ $message }}</div>
                @enderror

                <div id="quotation-groups"></div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="totals-tile h-100">
                            <span class="text-muted d-block mb-1">Sub Total</span>
                            <h4 class="mb-0" id="quotation-subtotal">&#8377;0.00</h4>
                        </div>
                    </div>
                    {{-- <div class="col-md-6">
                        <div class="totals-tile h-100 totals-tile-primary">
                            <span class="text-muted d-block mb-1">Total Amount</span>
                            <h4 class="mb-0" id="quotation-grand-total">&#8377;0.00</h4>
                        </div>
                    </div> --}}
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 flex-wrap">
                    <a href="{{ route('quotations.list') }}" class="btn btn-light">Cancel</a>
                    <button type="submit"
                        class="btn btn-primary">{{ $isEdit ? 'Update Quotation' : 'Create Quotation' }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .quotation-form-card {
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .quotation-group-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            margin-bottom: 1rem;
        }

        .quotation-group-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .quotation-group-body {
            padding: 1rem;
        }

        .quotation-item-row {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1rem;
            background: #fff;
            margin-bottom: 0.85rem;
        }

        .totals-tile {
            border-radius: 16px;
            padding: 1rem 1.1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .totals-tile-primary {
            background: #eff6ff;
            border-color: #bfdbfe;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const initialGroups = @json($initialGroups);
            const clients = @json($clientsPayload);
            const clientSelect = document.getElementById('quotation-client-select');
            const projectSelect = document.getElementById('quotation-project-select');
            const groupContainer = document.getElementById('quotation-groups');
            const addMainTitleButton = document.getElementById('add-main-title');
            const selectedProjectId = projectSelect.dataset.selectedProject || '';

            const getNumber = (value) => {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const formatMoney = (value) => `&#8377;${value.toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;

            const createRow = (row = {}) => ({
                description: row.description || '',
                nos: row.nos ?? '',
                length: row.length ?? '',
                breadth: row.breadth ?? '',
                depth: row.depth ?? '',
                quantity: row.quantity ?? '',
                unit: row.unit || '',
                price: row.price ?? row.rate ?? '',
                amount: row.amount ?? '',
            });
            const unitOptions = @json($unitOptions);
            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const buildUnitOptions = (selectedUnit = '') => {
                const selected = String(selectedUnit || '');
                let hasSelected = selected === '';
                const options = ['<option value="">Select Unit</option>'];

                unitOptions.forEach((unit) => {
                    const code = String(unit.code || '');
                    const isSelected = selected === code;
                    hasSelected = hasSelected || isSelected;
                    options.push(`<option value="${escapeHtml(code)}" ${isSelected ? 'selected' : ''}>${escapeHtml(unit.label || code)}</option>`);
                });

                if (selected && !hasSelected) {
                    options.push(`<option value="${escapeHtml(selected)}" selected>${escapeHtml(selected)}</option>`);
                }

                return options.join('');
            };

            const groupsState = initialGroups.length
                ? initialGroups.map((group) => ({
                    main_title: group.main_title || '',
                    rows: (group.rows || []).length ? group.rows.map(createRow) : [createRow()],
                }))
                : [{ main_title: '', rows: [createRow()] }];

            const buildRowHtml = (groupIndex, rowIndex, row) => `
                    <div class="quotation-item-row" data-row-index="${rowIndex}">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                            <h6 class="mb-0">Sub Title ${rowIndex + 1}</h6>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row" data-group-index="${groupIndex}" data-row-index="${rowIndex}">
                                <i class="ti ti-trash me-1"></i>Remove Sub Title
                            </button>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Description / Sub Title <span class="text-danger">*</span></label>
                                <textarea name="items[${groupIndex}][rows][${rowIndex}][description]" class="form-control" rows="2" required>${row.description}</textarea>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Nos</label>
                                <input type="number" step="0.01" min="0" name="items[${groupIndex}][rows][${rowIndex}][nos]" class="form-control calc-input" value="${row.nos}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Length (L)</label>
                                <input type="number" step="0.01" min="0" name="items[${groupIndex}][rows][${rowIndex}][length]" class="form-control calc-input" value="${row.length}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Breadth (B)</label>
                                <input type="number" step="0.01" min="0" name="items[${groupIndex}][rows][${rowIndex}][breadth]" class="form-control calc-input" value="${row.breadth}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Depth (D)</label>
                                <input type="number" step="0.01" min="0" name="items[${groupIndex}][rows][${rowIndex}][depth]" class="form-control calc-input" value="${row.depth}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Quantity</label>
                                <input type="number" step="0.01" min="0" name="items[${groupIndex}][rows][${rowIndex}][quantity]" class="form-control quantity-input" value="${row.quantity}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Unit</label>
                                <select name="items[${groupIndex}][rows][${rowIndex}][unit]" class="form-control">
                                    ${buildUnitOptions(row.unit)}
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Price</label>
                                <input type="number" step="0.01" min="0" name="items[${groupIndex}][rows][${rowIndex}][price]" class="form-control price-input" value="${row.price}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" min="0" name="items[${groupIndex}][rows][${rowIndex}][amount]" class="form-control amount-input" value="${row.amount}" readonly required>
                            </div>
                        </div>
                    </div>
                `;

            const buildGroupHtml = (group, groupIndex) => `
                    <div class="quotation-group-card" data-group-index="${groupIndex}">
                        <div class="quotation-group-header">
                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <div class="flex-grow-1">
                                    <label class="form-label">Main Title <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">${groupIndex + 1}</span>
                                        <input type="text" name="items[${groupIndex}][main_title]" class="form-control" value="${group.main_title}" required>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm add-row" data-group-index="${groupIndex}">
                                        <i class="ti ti-plus me-1"></i>Sub Title
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-group" data-group-index="${groupIndex}">
                                        <i class="ti ti-trash me-1"></i>Main Title
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="quotation-group-body">
                            ${group.rows.map((row, rowIndex) => buildRowHtml(groupIndex, rowIndex, row)).join('')}
                        </div>
                    </div>
                `;

            const render = () => {
                groupContainer.innerHTML = groupsState.map((group, groupIndex) => buildGroupHtml(group, groupIndex)).join('');
                recalculateAll();
            };

            const syncStateFromDom = () => {
                const nextState = [];

                groupContainer.querySelectorAll('.quotation-group-card').forEach((groupElement) => {
                    const groupIndex = Number(groupElement.dataset.groupIndex);
                    const mainTitleInput = groupElement.querySelector(`input[name="items[${groupIndex}][main_title]"]`);
                    const rows = [];

                    groupElement.querySelectorAll('.quotation-item-row').forEach((rowElement, rowIndex) => {
                        const readValue = (field) => rowElement.querySelector(`[name="items[${groupIndex}][rows][${rowIndex}][${field}]"]`)?.value || '';
                        rows.push(createRow({
                            description: readValue('description'),
                            nos: readValue('nos'),
                            length: readValue('length'),
                            breadth: readValue('breadth'),
                            depth: readValue('depth'),
                            quantity: readValue('quantity'),
                            unit: readValue('unit'),
                            price: readValue('price'),
                            amount: readValue('amount'),
                        }));
                    });

                    nextState.push({
                        main_title: mainTitleInput?.value || '',
                        rows: rows.length ? rows : [createRow()],
                    });
                });

                groupsState.splice(0, groupsState.length, ...nextState);
            };

            const recalculateRow = (rowElement) => {
                const calcInputs = Array.from(rowElement.querySelectorAll('.calc-input'));
                const quantityInput = rowElement.querySelector('.quantity-input');
                const priceInput = rowElement.querySelector('.price-input');
                const amountInput = rowElement.querySelector('.amount-input');
                const factors = calcInputs.map((input) => getNumber(input.value)).filter((value) => value > 0);

                if (factors.length > 0) {
                    const calculatedQuantity = factors.reduce((carry, value) => carry * value, 1);
                    quantityInput.value = calculatedQuantity.toFixed(2);
                }

                const amount = getNumber(quantityInput.value) * getNumber(priceInput.value);
                amountInput.value = amount.toFixed(2);
            };

            const recalculateAll = () => {
                let total = 0;
                document.querySelectorAll('.quotation-item-row').forEach((rowElement) => {
                    recalculateRow(rowElement);
                    total += getNumber(rowElement.querySelector('.amount-input').value);
                });

                const subtotalElement = document.getElementById('quotation-subtotal');
                const grandTotalElement = document.getElementById('quotation-grand-total');

                if (subtotalElement) {
                    subtotalElement.innerHTML = formatMoney(total);
                }

                if (grandTotalElement) {
                    grandTotalElement.innerHTML = formatMoney(total);
                }
            };

            const setClientFields = (client) => {
                document.getElementById('quotation-client-name').value = client?.name || '';
                document.getElementById('quotation-client-company').value = client?.company_name || '';
                document.getElementById('quotation-client-email').value = client?.email || '';
                document.getElementById('quotation-client-phone').value = client?.phone || '';
                document.getElementById('quotation-client-address').value = client?.full_address || '';
            };

            const updateProjectOptions = (projects, selectedValue = '') => {
                const placeholder = '<option value="">Select Project</option>';
                const options = projects.map((project) => {
                    const selected = `${project.id}` === `${selectedValue}` ? 'selected' : '';
                    const subtitle = [project.project_code, project.location].filter(Boolean).join(' - ');
                    return `<option value="${project.id}" ${selected}>${project.name}${subtitle ? ` (${subtitle})` : ''}</option>`;
                });

                projectSelect.innerHTML = placeholder + options.join('');
            };

            const quotationSelect = document.getElementById('quotation-select');
            const updateQuotationOptions = (quotations, selectedValue = '') => {
                const placeholder = '<option value="">Select Quotation</option>';
                const options = quotations.map((q) => {
                    const selected = `${q.id}` === `${selectedValue}` ? 'selected' : '';
                    return `<option value="${q.id}" ${selected}>${q.quotation_number} - ₹${parseFloat(q.amount || 0).toLocaleString()}</option>`;
                });
                quotationSelect.innerHTML = placeholder + options.join('');
            };

            projectSelect.addEventListener('change', () => {
                const projectId = projectSelect.value;
                if (projectId) {
                    fetch(`{{ route('quotations.by-project', ':project') }}`.replace(':project', projectId), {
                        headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
                    })
                    .then(response => response.json())
                    .then(data => updateQuotationOptions(data || []))
                    .catch(() => updateQuotationOptions([]));
                } else {
                    updateQuotationOptions([]);
                }
            });

            const loadClientDetails = async (clientId, preserveProject = false) => {
                if (!clientId) {
                    setClientFields(null);
                    updateProjectOptions([], '');
                    return;
                }

                try {
                    const response = await fetch(`{{ route('quotations.list') }}?client_details=${clientId}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    const payload = await response.json();
                    setClientFields(payload.client || clients[clientId] || null);
                    updateProjectOptions(payload.projects || [], preserveProject ? projectSelect.value || selectedProjectId : '');
                } catch (error) {
                    setClientFields(clients[clientId] || null);
                    updateProjectOptions([], '');
                }
            };

            addMainTitleButton.addEventListener('click', () => {
                syncStateFromDom();
                groupsState.push({ main_title: '', rows: [createRow()] });
                render();
            });

            groupContainer.addEventListener('click', (event) => {
                const addRowButton = event.target.closest('.add-row');
                if (addRowButton) {
                    syncStateFromDom();
                    groupsState[Number(addRowButton.dataset.groupIndex)].rows.push(createRow());
                    render();
                    return;
                }

                const removeRowButton = event.target.closest('.remove-row');
                if (removeRowButton) {
                    syncStateFromDom();
                    const groupIndex = Number(removeRowButton.dataset.groupIndex);
                    const rowIndex = Number(removeRowButton.dataset.rowIndex);
                    if (groupsState[groupIndex].rows.length > 1) {
                        groupsState[groupIndex].rows.splice(rowIndex, 1);
                    } else {
                        groupsState[groupIndex].rows = [createRow()];
                    }
                    render();
                    return;
                }

                const removeGroupButton = event.target.closest('.remove-group');
                if (removeGroupButton) {
                    syncStateFromDom();
                    if (groupsState.length > 1) {
                        groupsState.splice(Number(removeGroupButton.dataset.groupIndex), 1);
                    } else {
                        groupsState.splice(0, 1, { main_title: '', rows: [createRow()] });
                    }
                    render();
                }
            });

            groupContainer.addEventListener('input', (event) => {
                if (event.target.closest('.quotation-item-row')) {
                    recalculateAll();
                }
            });

            clientSelect.addEventListener('change', () => {
                loadClientDetails(clientSelect.value, false);
            });

            render();
            loadClientDetails(clientSelect.value, true);
        })();
    </script>
@endpush
