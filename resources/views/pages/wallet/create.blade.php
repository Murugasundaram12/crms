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

    <form method="POST" action="{{ route('wallet.store') }}" id="walletForm">
        @csrf
        <input type="hidden" id="wallet-balance" value="{{ $walletBalance }}">

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Client Name</label>
                        <select name="client_id" id="clientId" class="form-select" required>
                            <option value="">Select Client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Project Name</label>
                        <select name="project_id" id="projectId" class="form-select" required>
                            <option value="">Select Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" data-client-id="{{ $project->client_id }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Amount</label>
                        <input type="number" step="1" min="1" name="amount" id="walletAmount" class="form-control" value="{{ old('amount') }}" required>
                        <div class="text-danger small mt-1 d-none" id="walletAmountError">Amount is insufficient</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Payment Mode</label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="">Select Payment Mode</option>
                            @foreach($paymentModes as $id => $mode)
                                <option value="{{ $id }}" @selected((string) old('payment_mode') === (string) $id)>{{ $mode }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="transfer_type" id="transferType" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="0" @selected(old('transfer_type') === '0')>Add / Credited</option>
                            <option value="1" @selected(old('transfer_type') === '1')>Subtract / Debited</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="current_date" class="form-control" value="{{ old('current_date', now()->toDateString()) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Time</label>
                        <input type="time" name="time" class="form-control" value="{{ old('time', now()->format('H:i')) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Stage</label>
                        <select name="stage_id" class="form-select">
                            <option value="">Select Stage</option>
                            @foreach($stages as $stage)
                                <option value="{{ $stage->id }}" @selected((string) old('stage_id') === (string) $stage->id)>{{ $stage->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
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
            const type = document.getElementById('transferType').value;
            const amount = Number(document.getElementById('walletAmount').value || 0);
            const balance = Number(document.getElementById('wallet-balance').value || 0);
            const error = document.getElementById('walletAmountError');

            if (type === '1' && amount > balance) {
                event.preventDefault();
                error.classList.remove('d-none');
                return;
            }

            error.classList.add('d-none');
        });
    </script>
@endsection
