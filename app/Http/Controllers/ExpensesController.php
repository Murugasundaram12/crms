<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpensesController extends Controller
{
    public function history(Request $request)
    {
        $expenses = Expense::query()
            ->where('delete_status', false)
            ->with(['project', 'employee'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%{$q}%")
                        ->orWhere('category', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $projects = Project::query()->orderBy('name')->get();
        $employees = Employee::query()->orderBy('name')->get();

        return view('pages.expenses.index', compact('expenses', 'projects', 'employees'));
    }

    public function deletedHistory(Request $request)
    {
        $expenses = Expense::query()
            ->where('delete_status', true)
            ->with(['project', 'employee'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%{$q}%")
                        ->orWhere('category', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('delete_reason', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $projects = Project::query()->orderBy('name')->get();
        $employees = Employee::query()->orderBy('name')->get();

        return view('pages.expenses.index', compact('expenses', 'projects', 'employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateExpense($request);
        $amount = (float) $validated['amount'];
        $paidAmount = (float) $validated['paid_amount'];
        $unpaidAmount = max($amount - $paidAmount, 0);
        $extraAmount = max($paidAmount - $amount, 0);

        DB::transaction(function () use ($validated, $paidAmount, $unpaidAmount, $extraAmount) {
            $expense = Expense::create([
                ...$validated,
                'expense_code' => $this->generateExpenseCode(),
                'employee_id' => Auth::id(),
                'status' => $unpaidAmount > 0 ? 'pending' : 'paid',
                'unpaid_amount' => $unpaidAmount,
                'extra_amount' => $extraAmount,
                'delete_status' => false,
                'active_status' => true,
            ]);

            if ($paidAmount > 0) {
                $this->createWalletEntry($paidAmount, 1, 'Expense paid amount', 'expense', (int) $expense->id);
            }
        });

        return redirect()->back()->with('success', 'Expense stored successfully.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $expense = Expense::query()->where('delete_status', false)->findOrFail($id);
        $validated = $this->validateExpense($request);

        $amount = (float) $validated['amount'];
        $paidAmount = (float) $validated['paid_amount'];
        $unpaidAmount = max($amount - $paidAmount, 0);
        $extraAmount = max($paidAmount - $amount, 0);

        DB::transaction(function () use ($expense, $validated, $paidAmount, $unpaidAmount, $extraAmount) {
            $previousPaid = (float) $expense->paid_amount;
            $deltaPaid = $paidAmount - $previousPaid;

            if ($deltaPaid > 0) {
                $this->createWalletEntry($deltaPaid, 1, 'Expense update debit', 'expense', (int) $expense->id);
            } elseif ($deltaPaid < 0) {
                $this->createWalletEntry(abs($deltaPaid), 0, 'Expense update refund', 'expense', (int) $expense->id);
            }

            $expense->update([
                ...$validated,
                'status' => $unpaidAmount > 0 ? 'pending' : 'paid',
                'unpaid_amount' => $unpaidAmount,
                'extra_amount' => $extraAmount,
            ]);
        });

        return redirect()->back()->with('success', 'Expense updated successfully.');
    }

    public function deleteRecord(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_id' => ['required', 'integer', 'exists:expenses,id'],
            'delete_reason' => ['required', 'string', 'max:1000'],
        ]);

        $expense = Expense::query()->where('delete_status', false)->findOrFail((int) $validated['expense_id']);

        DB::transaction(function () use ($expense, $validated) {
            if ((float) $expense->paid_amount > 0) {
                $this->createWalletEntry((float) $expense->paid_amount, 0, 'Expense delete refund', 'expense', (int) $expense->id);
            }

            $expense->update([
                'delete_status' => true,
                'active_status' => false,
                'delete_reason' => $validated['delete_reason'],
            ]);
        });

        return redirect()->back()->with('success', 'Expense deleted successfully.');
    }

    private function createWalletEntry(float $amount, int $transferType, string $description, string $referenceType, int $referenceId): void
    {
        Wallet::create([
            'user_id' => Auth::id(),
            'amount' => $amount,
            'transfer_type' => $transferType,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'current_date' => now()->toDateString(),
            'current_time' => now()->format('H:i:s'),
        ]);
    }

    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function generateExpenseCode(): string
    {
        return 'EXP-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
