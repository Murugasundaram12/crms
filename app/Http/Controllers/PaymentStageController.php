<?php

namespace App\Http\Controllers;

use App\Models\PaymentStage;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PaymentStageController extends Controller
{
    public function index(Request $request)
    {
        // Build the payment stage query with optional filters.
        $paymentStageQuery = PaymentStage::with('payments');
        $this->applySearchFilter($paymentStageQuery, $request);
        $this->applyNameFilter($paymentStageQuery, $request);
        $this->applyDateFilter($paymentStageQuery, $request);

        // Load the listing and supporting data.
        $paymentStages = $paymentStageQuery->latest()->paginate(10)->withQueryString();

        return view('pages.payments.stages', compact('paymentStages'));
    }

    public function store(Request $request)
    {
        // Validate the form before creating the payment stage.
        $validatedData = $this->validatePaymentStageData($request);

        // Save the new payment stage.
        PaymentStage::create($this->buildPaymentStagePayload($validatedData));

        return redirect()->route('payment-stages.index')->with('success', 'Payment stage created successfully.');
    }

    public function show(PaymentStage $paymentStage)
    {
        return redirect()->route('payment-stages.index', ['highlight' => $paymentStage->id]);
    }

    public function edit(PaymentStage $paymentStage)
    {
        // Reuse the main page and open the selected payment stage for editing.
        return redirect()->route('payment-stages.index', ['edit' => $paymentStage->id]);
    }

    public function update(Request $request, PaymentStage $paymentStage)
    {
        // Validate the form before updating the payment stage.
        $validatedData = $this->validatePaymentStageData($request, $paymentStage);

        // Save the updated payment stage.
        $paymentStage->update($this->buildPaymentStagePayload($validatedData, $paymentStage));

        return redirect()->route('payment-stages.index')->with('success', 'Payment stage updated successfully.');
    }

    public function destroy(PaymentStage $paymentStage)
    {
        // Delete the selected payment stage.
        $paymentStage->delete();

        return redirect()->route('payment-stages.index')->with('success', 'Payment stage deleted successfully.');
    }

    private function applySearchFilter($paymentStageQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $paymentStageQuery->where($this->stageNameColumn(), 'like', "%{$searchTerm}%");
    }

    private function applyNameFilter($paymentStageQuery, Request $request): void
    {
        $name = $request->string('name')->toString();

        if ($name === '') {
            return;
        }

        $paymentStageQuery->where($this->stageNameColumn(), $name);
    }

    private function applyDateFilter($paymentStageQuery, Request $request): void
    {
        if ($request->filled('date_from')) {
            $paymentStageQuery->whereDate('created_at', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $paymentStageQuery->whereDate('created_at', '<=', $request->date('date_to')->toDateString());
        }
    }

    private function stageNameColumn(): string
    {
        return Schema::hasColumn('payment_stages', 'stage_name') ? 'stage_name' : 'name';
    }

    // Removed status/project filters as fields dropped

    private function validatePaymentStageData(Request $request, ?PaymentStage $paymentStage = null): array
    {
        // Validate simplified stage (name only)
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
    }

    private function buildPaymentStagePayload(array $validatedData, ?PaymentStage $paymentStage = null): array
    {
        $payload = ['stage_name' => $validatedData['name']];

        if (Schema::hasColumn('payment_stages', 'name')) {
            $payload = ['name' => $validatedData['name']];
        }

        if (Schema::hasColumn('payment_stages', 'project_id') && ! $paymentStage?->project_id) {
            $payload['project_id'] = Project::query()->value('id');
        }

        if (Schema::hasColumn('payment_stages', 'percentage') && ! $paymentStage?->percentage) {
            $payload['percentage'] = 0;
        }

        if (Schema::hasColumn('payment_stages', 'status') && ! $paymentStage?->status) {
            $payload['status'] = 'pending';
        }

        if (Schema::hasColumn('payment_stages', 'order') && ! $paymentStage?->order) {
            $payload['order'] = ((int) PaymentStage::query()->max('order')) + 1;
        }

        return $payload;
    }
}
