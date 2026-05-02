<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Project;
use App\Models\PaymentStage;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    private const BALANCE_AFFECTING_STATUSES = ['partial', 'paid'];

    public function index(Request $request)
    {
        // Build the payment list query with optional search and status filters.
        $paymentQuery = Payment::with(['client', 'quotation', 'project', 'stage']);
        $this->applySearchFilter($paymentQuery, $request);
        $this->applyStatusFilter($paymentQuery, $request);
        $this->applyQuotationFilter($paymentQuery, $request);

        // Load the main payment list for the page.
        $payments = $paymentQuery->latest()->paginate(10)->withQueryString();
        $quotationIds = $payments->getCollection()->pluck('quotation_id')->filter()->unique();
        $paidSumsByQuotation = Payment::whereIn('quotation_id', $quotationIds)
            ->whereIn('status', self::BALANCE_AFFECTING_STATUSES)
            ->selectRaw('quotation_id, COALESCE(SUM(amount), 0) as paid_total')
            ->groupBy('quotation_id')
            ->pluck('paid_total', 'quotation_id');

        $payments->getCollection()->transform(function ($payment) use ($paidSumsByQuotation) {
            $quotationTotal = (float) ($payment->quotation?->total_amount ?? $payment->quotation?->amount ?? 0);
            $paid = (float) ($paidSumsByQuotation[$payment->quotation_id] ?? 0);
            $payment->is_fully_paid_quotation = $quotationTotal > 0 && $paid >= $quotationTotal;
            return $payment;
        });

        // Load supporting data used by filters and modal forms.
        $projects = Project::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        $quotations = Quotation::select('id', 'quotation_number')
            ->whereNotNull('quotation_number')
            ->orderBy('quotation_number')
            ->get();

        $stages = \App\Models\PaymentStage::orderBy('name')->get();

        $expenses = Expense::with(['project', 'employee'])->latest()->take(5)->get();

        return view('pages.payments.index', compact('payments', 'projects', 'clients', 'quotations', 'stages', 'expenses'));
    }

    /**
     * API: Get quotations for a client
     */
    public function getQuotationsByClient($clientId)
    {
        $clientId = (int) $clientId;

        if ($clientId <= 0) {
            return response()->json(['error' => 'Invalid client ID'], 422);
        }

        try {
            $quotations = Quotation::where('client_id', $clientId)
                ->select('id', 'quotation_number as number', 'amount')
                ->orderBy('id', 'desc')
                ->get();

            $quotationIds = $quotations->pluck('id');

            $paidSums = Payment::whereIn('quotation_id', $quotationIds)
                ->whereIn('status', self::BALANCE_AFFECTING_STATUSES)
                ->selectRaw('quotation_id, COALESCE(SUM(amount), 0) as paid_total')
                ->groupBy('quotation_id')
                ->pluck('paid_total', 'quotation_id');

            $payload = $quotations->map(function ($quotation) use ($paidSums) {
                $quotationTotal = (float) ($quotation->total_amount ?? $quotation->amount ?? 0);
                $paidTotal = (float) ($paidSums[$quotation->id] ?? 0);
                $remainingAmount = max($quotationTotal - $paidTotal, 0);

                return [
                    'id' => $quotation->id,
                    'number' => $quotation->number,
                    'total_amount' => $remainingAmount,
                    'remaining_amount' => $remainingAmount,
                    'is_fully_paid' => $remainingAmount <= 0,
                ];
            });

            return response()->json($payload);
        } catch (\Exception $e) {
            \Log::error('getQuotationsByClient error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * API: Get quotation total_amount
     */
    public function quotationTotal($id)
    {
        $quotation = \App\Models\Quotation::select('id', 'amount')->findOrFail($id);

        $quotationTotal = (float) ($quotation->total_amount ?? $quotation->amount ?? 0);
        $paidTotal = (float) Payment::where('quotation_id', $quotation->id)
            ->whereIn('status', self::BALANCE_AFFECTING_STATUSES)
            ->sum('amount');
        $remainingAmount = max($quotationTotal - $paidTotal, 0);

        return response()->json([
            'total_amount' => $quotationTotal,
            'remaining_amount' => $remainingAmount,
            'is_fully_paid' => $remainingAmount <= 0,
        ]);
    }

    /**
     * API: Get project by client_id (latest project to avoid multiple)
     */
    public function getProjectByClient($clientId)
    {
        $clientId = (int) $clientId;

        if ($clientId <= 0) {
            return response()->json(['error' => 'Invalid client ID'], 422);
        }

        try {
            $project = Project::where('client_id', $clientId)
                ->latest('id')
                ->first(['id', 'name']);

            if (!$project) {
                return response()->json(['project' => null]);
            }

            return response()->json(['project' => $project]);
        } catch (\Exception $e) {
            \Log::error('getProjectByClient error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function store(Request $request)
    {
        // validate the form before saving a new payment
        $validatedData = $this->validatePaymentData($request);

        // Map paid_at to payment_date before saving
        if (!empty($validatedData['paid_at'])) {
            $validatedData['payment_date'] = $validatedData['paid_at'];
        }
        unset($validatedData['paid_at']);

        DB::transaction(function () use (&$validatedData) {
            if (Schema::hasColumn('payments', 'invoice_number')) {
                $validatedData['invoice_number'] = $this->nextInvoiceNumber();
                $validatedData['payment_code'] = $validatedData['invoice_number'];
            } else {
                $validatedData['payment_code'] = 'PAY-' . strtoupper(Str::random(6));
            }
            Payment::create($validatedData);
        });

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['client', 'quotation', 'project', 'stage']);
        return view('pages.payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        // Reuse the listing page and open the selected payment for editing.
        return redirect()->route('payments.index', ['edit' => $payment->id]);
    }

    public function update(Request $request, Payment $payment)
    {
        // Validate the form before updating the payment.
        $validatedData = $this->validatePaymentData($request, $payment);

        // Map paid_at to payment_date before saving
        if (!empty($validatedData['paid_at'])) {
            $validatedData['payment_date'] = $validatedData['paid_at'];
        }
        unset($validatedData['paid_at']);

        // Save the updated payment values.
        $payment->update($validatedData);

        return redirect()->route('payments.index')->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        // Delete the selected payment record.
        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }

    private function applySearchFilter($paymentQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $paymentQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('transaction_id', 'like', "%{$searchTerm}%")
                ->orWhereHas('client', function ($clientQuery) use ($searchTerm) {
                    $clientQuery->where('name', 'like', "%{$searchTerm}%");
                })
                ->orWhereHas('quotation', function ($qQuery) use ($searchTerm) {
                    $qQuery->where('quotation_number', 'like', "%{$searchTerm}%");
                })
                ->orWhereHas('project', function ($projectQuery) use ($searchTerm) {
                    $projectQuery->where('name', 'like', "%{$searchTerm}%");
                });
        });
    }

    private function applyStatusFilter($paymentQuery, Request $request): void
    {
        $status = $request->string('status')->toString();

        if ($status === '') {
            return;
        }

        $paymentQuery->where('status', $status);
    }

    private function applyQuotationFilter($paymentQuery, Request $request): void
    {
        $quotationNumber = trim((string) $request->get('quotation_number', ''));
        if ($quotationNumber === '') {
            return;
        }

        $paymentQuery->whereHas('quotation', function ($query) use ($quotationNumber) {
            $query->where('quotation_number', 'like', '%' . $quotationNumber . '%');
        });
    }

    private function validatePaymentData(Request $request, ?Payment $payment = null): array
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'quotation_id' => ['required', 'exists:quotations,id'],
            'stage_id' => ['required', 'exists:payment_stages,id'],
            'project_id' => ['nullable', 'exists:projects,id'],

            'transaction_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('payments', 'transaction_id')->ignore($payment?->id),
            ],

            'method' => ['required', Rule::in(['cash', 'bank_transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['pending', 'paid', 'overdue', 'partial'])],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['transaction_id'] = $validated['transaction_id']
            ?? 'TXN-' . strtoupper(Str::random(8));

        // Custom validation: amount <= remaining quotation amount
        $quotation = \App\Models\Quotation::findOrFail($validated['quotation_id']);
        $quotationTotal = (float) ($quotation->total_amount ?? $quotation->amount ?? 0);

        $paidQuery = Payment::where('quotation_id', $validated['quotation_id'])
            ->whereIn('status', self::BALANCE_AFFECTING_STATUSES);
        if ($payment) {
            $paidQuery->where('id', '!=', $payment->id);
        }
        $alreadyPaid = (float) $paidQuery->sum('amount');
        $remainingAmount = max($quotationTotal - $alreadyPaid, 0);

        if ($remainingAmount <= 0) {
            throw ValidationException::withMessages([
                'quotation_id' => 'This quotation is fully paid. New payments are blocked.',
            ]);
        }

        if ((float) $validated['amount'] > $remainingAmount) {
            throw ValidationException::withMessages([
                'amount' => 'Amount cannot exceed remaining amount: ' . number_format($remainingAmount, 2),
            ]);
        }

        if (($validated['status'] ?? null) === 'partial') {
            $totalStages = PaymentStage::count();
            $usedStageCount = Payment::where('quotation_id', $validated['quotation_id'])
                ->when($payment, fn ($query) => $query->where('id', '!=', $payment->id))
                ->distinct('stage_id')
                ->count('stage_id');

            if ($totalStages > 0 && $usedStageCount >= $totalStages) {
                throw ValidationException::withMessages([
                    'stage_id' => 'No more stages available for partial payment. Please add a new stage.',
                ]);
            }
        }

        // Prevent duplicate stage for the same quotation on add/edit.
        $duplicateStageQuery = Payment::where('quotation_id', $validated['quotation_id'])
            ->where('stage_id', $validated['stage_id']);
        if ($payment) {
            $duplicateStageQuery->where('id', '!=', $payment->id);
        }
        if ($duplicateStageQuery->exists()) {
            throw ValidationException::withMessages([
                'stage_id' => 'Selected stage is already used for this quotation. Please choose another stage.',
            ]);
        }

        return $validated;
    }

    private function nextInvoiceNumber(): string
    {
        if (!Schema::hasColumn('payments', 'invoice_number')) {
            return 'INV-0001';
        }

        $lastInvoice = Payment::whereNotNull('invoice_number')
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('invoice_number');

        $lastNumber = 0;
        if (is_string($lastInvoice) && preg_match('/^INV-(\d+)$/', $lastInvoice, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return 'INV-' . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
