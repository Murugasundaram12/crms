@extends('layouts.app')

@section('title', 'Add Wallet')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h4 class="m-0">Add Wallet</h4>
        <a href="{{ route('wallet.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('wallet.store') }}" id="walletForm" novalidate>
        @csrf
        <input type="hidden" id="wallet-balance" value="{{ $walletBalance }}">
        <input type="hidden" id="wallet-user-id" value="{{ auth()->id() }}">

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Member Name</label>
                        <select name="user_id" id="walletUserId" class="form-select @error('user_id') is-invalid @enderror" @error('user_id') aria-describedby="wallet_user_id_error" aria-invalid="true" @enderror>
                            <option value="" data-wallet="{{ $walletBalance }}">Current User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" data-wallet="{{ $user->wallet }}" @selected((string) old('user_id') === (string) $user->id)>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="invalid-feedback d-block" id="wallet_user_id_error">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Client Name <span class="text-danger">*</span></label>
                        <select name="client_id" id="clientId" class="form-select @error('client_id') is-invalid @enderror" @error('client_id') aria-describedby="wallet_client_id_error" aria-invalid="true" @enderror required>
                            <option value="">Select Client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                        @error('client_id')<div class="invalid-feedback d-block" id="wallet_client_id_error">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Project Name <span class="text-danger">*</span></label>
                        <select name="project_id" id="projectId" class="form-select @error('project_id') is-invalid @enderror" @error('project_id') aria-describedby="wallet_project_id_error" aria-invalid="true" @enderror required>
                            <option value="">Select Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" data-client-id="{{ $project->client_id }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                        @error('project_id')<div class="invalid-feedback d-block" id="wallet_project_id_error">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="1" min="1" name="amount" id="walletAmount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" @error('amount') aria-describedby="wallet_amount_error" aria-invalid="true" @enderror required>
                        @error('amount')<div class="invalid-feedback d-block" id="wallet_amount_error">{{ $message }}</div>@enderror
                        <div class="text-danger small mt-1 d-none" id="walletAmountError">Amount is insufficient</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror" @error('payment_method_id') aria-describedby="wallet_payment_method_id_error" aria-invalid="true" @enderror required>
                            <option value="">Select Payment Mode</option>
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->id }}" @selected((string) old('payment_method_id') === (string) $pm->id)>{{ $pm->name }}</option>
                            @endforeach
                        </select>
                        @error('payment_method_id')<div class="invalid-feedback d-block" id="wallet_payment_method_id_error">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="transfer_type" id="transferType" class="form-select @error('transfer_type') is-invalid @enderror" @error('transfer_type') aria-describedby="wallet_transfer_type_error" aria-invalid="true" @enderror required>
                            <option value="">Select Type</option>
                            <option value="0" @selected(old('transfer_type') === '0')>Add / Credited</option>
                            <option value="1" @selected(old('transfer_type') === '1')>Subtract / Debited</option>
                        </select>
                        @error('transfer_type')<div class="invalid-feedback d-block" id="wallet_transfer_type_error">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="current_date" class="form-control @error('current_date') is-invalid @enderror" value="{{ old('current_date', now()->toDateString()) }}" @error('current_date') aria-describedby="wallet_current_date_error" aria-invalid="true" @enderror required>
                        @error('current_date')<div class="invalid-feedback d-block" id="wallet_current_date_error">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Time</label>
                        <input type="time" name="time" class="form-control @error('time') is-invalid @enderror" value="{{ old('time', now()->format('H:i')) }}" @error('time') aria-describedby="wallet_time_error" aria-invalid="true" @enderror>
                        @error('time')<div class="invalid-feedback d-block" id="wallet_time_error">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Stage</label>
                        <select name="stage_id" class="form-select @error('stage_id') is-invalid @enderror" @error('stage_id') aria-describedby="wallet_stage_id_error" aria-invalid="true" @enderror>
                            <option value="">Select Stage</option>
                            @foreach($stages as $stage)
                                <option value="{{ $stage->id }}" @selected((string) old('stage_id') === (string) $stage->id)>{{ $stage->name }}</option>
                            @endforeach
                        </select>
                        @error('stage_id')<div class="invalid-feedback d-block" id="wallet_stage_id_error">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" @error('description') aria-describedby="wallet_description_error" aria-invalid="true" @enderror>{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback d-block" id="wallet_description_error">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <span class="text-muted">Current Wallet Balance: {{ number_format($walletBalance, 2) }}</span>
                        <button class="btn btn-primary" type="submit">Save Wallet</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        const clientSelect = document.getElementById('clientId');
        const projectSelect = document.getElementById('projectId');

        function syncProjectOptions() {
            const clientId = clientSelect.value;

            Array.from(projectSelect.options).forEach(function (option) {
                if (!option.value) {
                    return;
                }

                const isMatch = !clientId || option.dataset.clientId === clientId;
                option.hidden = !isMatch;
                option.disabled = !isMatch;
            });

            if (projectSelect.selectedOptions.length && projectSelect.selectedOptions[0].disabled) {
                projectSelect.value = '';
            }
        }

        clientSelect.addEventListener('change', syncProjectOptions);
        syncProjectOptions();

        document.getElementById('walletForm').addEventListener('submit', function (event) {
            const userSelect = document.getElementById('walletUserId');
            const selectedUserId = userSelect.value;
            const type = document.getElementById('transferType').value;
            const amount = Number(document.getElementById('walletAmount').value || 0);
            const actorBalance = Number(document.getElementById('wallet-balance').value || 0);
            const selectedBalance = Number(userSelect.selectedOptions[0]?.dataset.wallet || actorBalance);
            const error = document.getElementById('walletAmountError');
            const sourceBalance = type === '1' ? selectedBalance : Number.POSITIVE_INFINITY;

            if (amount > sourceBalance) {
                event.preventDefault();
                error.classList.remove('d-none');
                return;
            }

            error.classList.add('d-none');
        });
    </script>
@endsection
