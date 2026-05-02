<?php

namespace App\Http\Controllers;

use App\Models\PaymentStage;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentStageController extends Controller
{
    public function index(Request $request)
    {
        // Build the payment stage query with optional filters.
        $paymentStageQuery = PaymentStage::with('payments');
        $this->applySearchFilter($paymentStageQuery, $request);

        // Load the listing and supporting data.
        $paymentStages = $paymentStageQuery->latest()->paginate(10)->withQueryString();

        return view('pages.payments.stages', compact('paymentStages'));
    }

    public function store(Request $request)
    {
        // Validate the form before creating the payment stage.
        $validatedData = $this->validatePaymentStageData($request);

        // Save the new payment stage.
        PaymentStage::create($validatedData);

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
        $paymentStage->update($validatedData);

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

        $paymentStageQuery->where('stage_name', 'like', "%{$searchTerm}%");
    }

    // Removed status/project filters as fields dropped

    private function validatePaymentStageData(Request $request, ?PaymentStage $paymentStage = null): array
    {
        // Validate simplified stage (name only)
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
    }
}
