<?php

namespace App\Http\Controllers;

use App\Models\LabourExpenseTransaction;
use App\Models\PaymentMethod;
use App\Services\CrmBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LabourExpenseTransactionController extends Controller
{

    public function index(Request $request)
    {
        return redirect()->route('labour-expenses.history', $request->query());
    }

    public function create()
    {
        return redirect()->route('labour-expenses.create.legacy');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateLabourExpense($request);

        $validated['user_id'] = Auth::id();
        $validated['current_date'] = $this->parseDateToYmd($request->string('current_date'));
        $validated['current_time'] = $request->string('current_time');

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('expense-images', 'public');
        }

        DB::transaction(function () use ($validated) {
            $transaction = LabourExpenseTransaction::create($validated);
            app(CrmBalanceService::class)->replaceUserWalletDebit(
                null,
                0,
                (int) $validated['user_id'],
                (float) $validated['paid_amount'],
                'Labour expense transaction payment',
                'labour_expense_transaction',
                (int) $transaction->id
            );
        });

        return redirect()->route('labour-expenses.history')
            ->with('success', 'Labour expense added successfully.');
    }

    public function edit(LabourExpenseTransaction $labourExpenseTransaction)
    {
        return redirect()->route('labour-expenses.edit.legacy', $labourExpenseTransaction->id);
    }

    public function update(Request $request, LabourExpenseTransaction $labourExpenseTransaction): RedirectResponse
    {
        $validated = $this->validateLabourExpense($request, $labourExpenseTransaction);

        $validated['current_date'] = $this->parseDateToYmd($request->string('current_date'));
        $validated['current_time'] = $request->string('current_time');

        if ($request->hasFile('image')) {
            if ($labourExpenseTransaction->image_path) {
                Storage::disk('public')->delete($labourExpenseTransaction->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('expense-images', 'public');
        }

        DB::transaction(function () use ($labourExpenseTransaction, $validated) {
            $oldUserId = (int) $labourExpenseTransaction->user_id;
            $oldPaidAmount = (float) $labourExpenseTransaction->paid_amount;

            $labourExpenseTransaction->update($validated);

            app(CrmBalanceService::class)->replaceUserWalletDebit(
                $oldUserId,
                $oldPaidAmount,
                (int) $labourExpenseTransaction->user_id,
                (float) $labourExpenseTransaction->paid_amount,
                'Labour expense transaction payment update',
                'labour_expense_transaction',
                (int) $labourExpenseTransaction->id
            );
        });

        return redirect()->route('labour-expenses.history')
            ->with('success', 'Labour expense updated successfully.');
    }

    public function destroy(LabourExpenseTransaction $labourExpenseTransaction): RedirectResponse
    {
        DB::transaction(function () use ($labourExpenseTransaction) {
            app(CrmBalanceService::class)->replaceUserWalletDebit(
                (int) $labourExpenseTransaction->user_id,
                (float) $labourExpenseTransaction->paid_amount,
                null,
                0,
                'Deleted labour expense transaction refund',
                'labour_expense_transaction',
                (int) $labourExpenseTransaction->id
            );

            $labourExpenseTransaction->delete_status = true;
            $labourExpenseTransaction->active_status = false;
            $labourExpenseTransaction->save();
        });

        if ($labourExpenseTransaction->image_path) {
            Storage::disk('public')->delete($labourExpenseTransaction->image_path);
        }

        return redirect()->route('labour-expenses.history')
            ->with('success', 'Labour expense deleted successfully.');
    }

    private function validateLabourExpense(Request $request, ?LabourExpenseTransaction $existing = null): array
    {
        return $request->validate([
            'main_category_id' => ['required', 'exists:main_categories,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'image' => ['nullable', 'image', 'max:2048'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'current_date' => ['required', 'date_format:d/m/Y'],
            'current_time' => ['required', 'string', 'max:20'],
            'labour_id' => ['required', 'exists:labours,id'],
            'salary' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function parseDateToYmd(string $date): string
    {
        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $date;
        }
    }
}
