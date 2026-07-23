<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\Project;
use App\Models\User;
use App\Models\Variation;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VariationController extends Controller
{
    public function index(Request $request): View
    {
        $variationQuery = Variation::with(['project', 'approvedBy', 'employee', 'paymentMethod']);
        $this->applySearchFilter($variationQuery, $request);
        $this->applyStatusFilter($variationQuery, $request);
        $this->applyTypeFilter($variationQuery, $request);
        $this->applyProjectFilter($variationQuery, $request);
        $this->applyDateFilter($variationQuery, $request);

        $totals = (clone $variationQuery)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'additional' THEN amount ELSE 0 END), 0) as total_additional")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'deduction' THEN amount ELSE 0 END), 0) as total_deduction")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' AND type = 'additional' THEN amount WHEN status = 'approved' AND type = 'deduction' THEN -amount ELSE 0 END), 0) as approved_net")
            ->first();

        $variations = $variationQuery->latest()->paginate(10)->withQueryString();
        $projects = Project::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $paymentMethods = PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        return view('pages.variations.index', compact('variations', 'projects', 'employees', 'users', 'paymentMethods', 'totals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $this->validateVariationData($request);

        DB::transaction(function () use ($validatedData): void {
            $variation = Variation::create($validatedData);
            $this->applyVariationWallet($variation, 1);
        });

        return redirect()->route('variations.index', ['project_id' => $validatedData['project_id']])->with('success', 'Variation created successfully.');
    }

    public function show(Variation $variation): RedirectResponse
    {
        return redirect()->route('variations.index', ['highlight' => $variation->id]);
    }

    public function edit(Variation $variation): RedirectResponse
    {
        return redirect()->route('variations.index', ['edit' => $variation->id]);
    }

    public function update(Request $request, Variation $variation): RedirectResponse
    {
        $validatedData = $this->validateVariationData($request, $variation);

        DB::transaction(function () use ($variation, $validatedData): void {
            $this->applyVariationWallet($variation, -1);
            $variation->update($validatedData);
            $this->applyVariationWallet($variation->fresh(), 1);
        });

        return redirect()->route('variations.index', ['project_id' => $validatedData['project_id']])->with('success', 'Variation updated successfully.');
    }

    public function destroy(Variation $variation): RedirectResponse
    {
        DB::transaction(function () use ($variation): void {
            $this->applyVariationWallet($variation, -1);
            $variation->delete();
        });

        return redirect()->route('variations.index')->with('success', 'Variation deleted successfully.');
    }

    private function applySearchFilter($variationQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();
        if ($searchTerm === '') {
            return;
        }

        $variationQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('description', 'like', "%{$searchTerm}%")
                ->orWhereHas('project', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$searchTerm}%"));
        });
    }

    private function applyStatusFilter($variationQuery, Request $request): void
    {
        $status = $request->string('status')->toString();
        if ($status !== '') {
            $variationQuery->where('status', $status);
        }
    }

    private function applyTypeFilter($variationQuery, Request $request): void
    {
        $type = $request->string('type')->toString();
        if ($type !== '') {
            $variationQuery->where('type', $type);
        }
    }

    private function applyProjectFilter($variationQuery, Request $request): void
    {
        $projectId = $request->integer('project_id');
        if ($projectId !== 0) {
            $variationQuery->where('project_id', $projectId);
        }
    }

    private function applyDateFilter($variationQuery, Request $request): void
    {
        if ($request->filled('date_from')) {
            $variationQuery->whereDate('date', '>=', $request->date('date_from')->toDateString());
        }
        if ($request->filled('date_to')) {
            $variationQuery->whereDate('date', '<=', $request->date('date_to')->toDateString());
        }
    }

    private function validateVariationData(Request $request, ?Variation $variation = null): array
    {
        return $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'description' => ['required', 'string'],
            'type' => ['required', Rule::in(['additional', 'deduction'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'date' => ['required', 'date'],
            'approved_by' => ['nullable', 'exists:employees,id'],
            'employee_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);
    }

    private function applyVariationWallet(Variation $variation, int $direction): void
    {
        if ($variation->status !== 'approved' || (float) $variation->amount <= 0) {
            return;
        }

        $targetUserId = $variation->employee_id ?: $this->userIdForEmployee((int) $variation->approved_by);
        if (! $targetUserId) {
            return;
        }

        $balanceService = app(CrmBalanceService::class);
        $amount = (float) $variation->amount;

        // Determine effective direction based on type and multiplier direction
        // type === 'additional': addition/credit to employee wallet
        // type === 'deduction': deduction/debit from employee wallet
        if ($variation->type === 'additional') {
            $actionDirection = $direction === 1 ? 'credit' : 'debit';
        } else {
            $actionDirection = $direction === 1 ? 'debit' : 'credit';
        }

        if ($direction === 1) {
            $balanceService->recordWalletTransaction(
                userId: $targetUserId,
                amount: $amount,
                direction: $actionDirection,
                sourceType: 'variation',
                sourceId: $variation->id,
                paymentMethodId: $variation->payment_method_id,
                description: 'Variation ' . ($variation->type === 'deduction' ? 'deduction' : 'addition') . ' - ' . $variation->description,
                createdBy: Auth::id(),
                projectId: $variation->project_id,
                clientId: $variation->project?->client_id
            );
        } else {
            // Reversing existing transaction
            if ($actionDirection === 'debit') {
                $balanceService->debitUserWallet($targetUserId, $amount, 'Reversal of variation ' . $variation->id);
            } else {
                $balanceService->creditUserWallet($targetUserId, $amount, 'Reversal of variation ' . $variation->id);
            }

            // Remove previous wallet entry if table exists
            Wallet::query()
                ->where('source_type', 'variation')
                ->where('source_id', $variation->id)
                ->delete();
        }
    }

    private function userIdForEmployee(int $employeeId): ?int
    {
        $employee = Employee::query()->find($employeeId);
        if (! $employee) {
            return null;
        }

        $user = User::query()
            ->whereKey($employee->id)
            ->when($employee->email, fn($query) => $query->orWhere('email', $employee->email))
            ->first(['id']);

        return $user?->id;
    }
}
