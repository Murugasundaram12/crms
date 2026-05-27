<?php

namespace App\Http\Controllers;

use App\Models\AdvanceHistory;
use App\Models\Labour;
use App\Models\LabourExpenseTransaction;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LabourExpensesController extends Controller
{
    public function history(Request $request)
    {
        $transactions = LabourExpenseTransaction::query()
            ->where('delete_status', false)
            ->with(['labour', 'project'])
            ->when($request->filled('labour_id'), fn($q) => $q->where('labour_id', $request->integer('labour_id')))
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $labours = Labour::query()->orderBy('name')->get();
        return view('pages.labour_expenses.history', compact('transactions', 'labours'));
    }

    public function weeklyHistory(Request $request)
    {
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        $transactions = LabourExpenseTransaction::query()
            ->where('delete_status', false)
            ->whereBetween('current_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('labour_id'), fn($q) => $q->where('labour_id', $request->integer('labour_id')))
            ->with('labour')
            ->get();


        $labours = Labour::query()->orderBy('name')->get();
        return view('pages.labour_expenses.weekly', compact('transactions', 'labours', 'start', 'end'));
    }

    public function projectHistory(Request $request)
    {
        $transactions = LabourExpenseTransaction::query()
            ->where('delete_status', false)
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->integer('project_id')))
            ->with(['labour', 'project'])
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        return response()->json($transactions);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'main_category_id' => ['required', 'exists:main_categories,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($validated) {
            $amount = (float) $validated['amount'];
            $paidAmount = (float) $validated['paid_amount'];
            $unpaidAmount = max($amount - $paidAmount, 0);
            $extraAmount = max($paidAmount - $amount, 0);

            $tx = LabourExpenseTransaction::create([
                'user_id' => Auth::id(),
                'main_category_id' => $validated['main_category_id'],
                'category_id' => $validated['category_id'],
                'project_id' => $validated['project_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount' => $amount,
                'paid_amount' => $paidAmount,
                'unpaid_amount' => $unpaidAmount,
                'extra_amount' => $extraAmount,
                'payment_mode' => $validated['payment_mode'] ?? 'Cash',
                'labour_id' => $validated['labour_id'],
                'salary' => $validated['salary'] ?? $amount,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
                'active_status' => true,
                'delete_status' => false,
            ]);

            if ($paidAmount > 0) {
                Wallet::create([
                    'user_id' => Auth::id(),
                    'amount' => $paidAmount,
                    'transfer_type' => 1,
                    'description' => 'Labour expense paid',
                    'reference_type' => 'labour_expense',
                    'reference_id' => $tx->id,
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);
            }

            if ($extraAmount > 0) {
                Labour::query()->where('id', $validated['labour_id'])->increment('advance_amt', $extraAmount);
                AdvanceHistory::create([
                    'labour_id' => $validated['labour_id'],
                    'labour_expense_transaction_id' => $tx->id,
                    'amount' => $extraAmount,
                    'entry_type' => 'credit',
                    'notes' => 'Extra amount added as advance',
                    'user_id' => Auth::id(),
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Labour expense stored successfully.');
    }

    public function advanceHistory(Request $request)
    {
        $history = AdvanceHistory::query()
            ->with('labour')
            ->when($request->filled('labour_id'), fn($q) => $q->where('labour_id', $request->integer('labour_id')))
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $labours = Labour::query()->orderBy('name')->get();
        return view('pages.labour_expenses.advance', compact('history', 'labours'));
    }

    public function advanceStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'labour_expense_transaction_id' => ['required', 'exists:labour_expense_transactions,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $labour = Labour::query()->lockForUpdate()->findOrFail((int) $validated['labour_id']);
            $tx = LabourExpenseTransaction::query()->lockForUpdate()->findOrFail((int) $validated['labour_expense_transaction_id']);

            $availableAdvance = (float) $labour->advance_amt;
            $unpaid = (float) $tx->unpaid_amount;
            $requested = (float) $validated['amount'];
            $settle = min($requested, $availableAdvance, $unpaid);

            if ($settle <= 0) {
                return;
            }

            $labour->decrement('advance_amt', $settle);
            $tx->update([
                'paid_amount' => (float) $tx->paid_amount + $settle,
                'unpaid_amount' => max($unpaid - $settle, 0),
            ]);

            AdvanceHistory::create([
                'labour_id' => $labour->id,
                'labour_expense_transaction_id' => $tx->id,
                'amount' => $settle,
                'entry_type' => 'settle',
                'notes' => $validated['notes'] ?? 'Advance settled against unpaid labour expense',
                'user_id' => Auth::id(),
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
            ]);
        });

        return redirect()->back()->with('success', 'Labour advance settled successfully.');
    }

    public function deleteRecord(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:labour_expense_transactions,id'],
            'delete_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $tx = LabourExpenseTransaction::query()->where('delete_status', false)->findOrFail((int) $validated['id']);

            if ((float) $tx->paid_amount > 0) {
                Wallet::create([
                    'user_id' => Auth::id(),
                    'amount' => (float) $tx->paid_amount,
                    'transfer_type' => 0,
                    'description' => 'Labour expense delete refund',
                    'reference_type' => 'labour_expense',
                    'reference_id' => $tx->id,
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);
            }

            $tx->update([
                'delete_status' => true,
                'active_status' => false,
                'delete_reason' => $validated['delete_reason'],
            ]);
        });

        return redirect()->back()->with('success', 'Labour expense deleted successfully.');
    }

    public function deletedHistory(Request $request)
    {
        $transactions = LabourExpenseTransaction::query()
            ->where('delete_status', true)
            ->with(['labour', 'project'])
            ->when($request->filled('labour_id'), fn($q) => $q->where('labour_id', $request->integer('labour_id')))
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $labours = Labour::query()->orderBy('name')->get();
        return view('pages.labour_expenses.deleted', compact('transactions', 'labours'));
    }
}
