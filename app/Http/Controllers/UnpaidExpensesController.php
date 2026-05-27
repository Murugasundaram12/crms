<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Employee;
use App\Models\ExpenseUnpaidDate;
use App\Models\Project;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnpaidExpensesController extends Controller
{
    public function history(Request $request)
    {
        $expenses = Expense::query()
            ->where('delete_status', false)
            ->where('unpaid_amount', '>', 0)
            ->with(['project', 'employee'])
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $projects = Project::query()->orderBy('name')->get();
        $employees = Employee::query()->orderBy('name')->get();

        return view('pages.expenses.index', compact('expenses', 'projects', 'employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_id' => ['required', 'integer', 'exists:expenses,id'],
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $expense = Expense::query()->where('delete_status', false)->findOrFail((int) $validated['expense_id']);
        $payAmount = (float) $validated['paid_amount'];

        DB::transaction(function () use ($expense, $payAmount, $validated) {
            $currentUnpaid = (float) $expense->unpaid_amount;
            $settlement = min($payAmount, $currentUnpaid);
            $newUnpaid = max($currentUnpaid - $settlement, 0);

            ExpenseUnpaidDate::create([
                'expense_id' => $expense->id,
                'user_id' => Auth::id(),
                'paid_amount' => $settlement,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
                'notes' => $validated['notes'] ?? null,
            ]);

            Wallet::create([
                'user_id' => Auth::id(),
                'amount' => $settlement,
                'transfer_type' => 1,
                'description' => 'Unpaid expense settlement',
                'reference_type' => 'expense_unpaid_settlement',
                'reference_id' => $expense->id,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
            ]);

            $expense->update([
                'paid_amount' => (float) $expense->paid_amount + $settlement,
                'unpaid_amount' => $newUnpaid,
                'status' => $newUnpaid > 0 ? 'pending' : 'paid',
            ]);
        });

        return redirect()->back()->with('success', 'Unpaid amount settled successfully.');
    }
}
