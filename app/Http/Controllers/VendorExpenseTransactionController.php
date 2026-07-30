<?php

namespace App\Http\Controllers;

use App\Models\VendorExpenseTransaction;
use App\Models\PaymentMethod;
use App\Services\CrmBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VendorExpenseTransactionController extends Controller
{

    public function index(Request $request)
    {
        return redirect()->route('vendor-expenses.history', $request->query());
    }

    public function create()
    {
        return redirect()->route('vendor-expenses.create.legacy');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateVendorExpense($request);

        $validated['user_id'] = Auth::id();
        $validated['current_date'] = $this->parseDateToYmd($request->string('current_date'));
        $validated['current_time'] = $request->string('current_time');

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('expense-images', 'public');
        }

        DB::transaction(function () use ($validated) {
            $transaction = VendorExpenseTransaction::create($validated);
            app(CrmBalanceService::class)->replaceUserWalletDebit(
                null,
                0,
                (int) $validated['user_id'],
                (float) $validated['paid_amount'],
                'Vendor expense transaction payment',
                'vendor_expense_transaction',
                (int) $transaction->id
            );
        });

        return redirect()->route('vendor-expenses.history')->with('success', 'Vendor expense added successfully.');
    }

    public function edit(VendorExpenseTransaction $vendorExpenseTransaction)
    {
        return redirect()->route('vendor-expenses.edit.legacy', $vendorExpenseTransaction->id);
    }

    public function update(Request $request, VendorExpenseTransaction $vendorExpenseTransaction): RedirectResponse
    {
        $validated = $this->validateVendorExpense($request, $vendorExpenseTransaction);

        $validated['current_date'] = $this->parseDateToYmd($request->string('current_date'));
        $validated['current_time'] = $request->string('current_time');

        if ($request->hasFile('image')) {
            if ($vendorExpenseTransaction->image_path) {
                Storage::disk('public')->delete($vendorExpenseTransaction->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('expense-images', 'public');
        }

        DB::transaction(function () use ($vendorExpenseTransaction, $validated) {
            $oldUserId = (int) $vendorExpenseTransaction->user_id;
            $oldPaidAmount = (float) $vendorExpenseTransaction->paid_amount;

            $vendorExpenseTransaction->update($validated);

            app(CrmBalanceService::class)->replaceUserWalletDebit(
                $oldUserId,
                $oldPaidAmount,
                (int) $vendorExpenseTransaction->user_id,
                (float) $vendorExpenseTransaction->paid_amount,
                'Vendor expense transaction payment update',
                'vendor_expense_transaction',
                (int) $vendorExpenseTransaction->id
            );
        });

        return redirect()->route('vendor-expenses.history')->with('success', 'Vendor expense updated successfully.');
    }

    public function destroy(VendorExpenseTransaction $vendorExpenseTransaction): RedirectResponse
    {
        DB::transaction(function () use ($vendorExpenseTransaction) {
            app(CrmBalanceService::class)->replaceUserWalletDebit(
                (int) $vendorExpenseTransaction->user_id,
                (float) $vendorExpenseTransaction->paid_amount,
                null,
                0,
                'Deleted vendor expense transaction refund',
                'vendor_expense_transaction',
                (int) $vendorExpenseTransaction->id
            );

            $vendorExpenseTransaction->delete_status = true;
            $vendorExpenseTransaction->active_status = false;
            $vendorExpenseTransaction->save();
        });

        if ($vendorExpenseTransaction->image_path) {
            Storage::disk('public')->delete($vendorExpenseTransaction->image_path);
        }

        return redirect()->route('vendor-expenses.history')->with('success', 'Vendor expense deleted successfully.');
    }

    private function validateVendorExpense(Request $request, ?VendorExpenseTransaction $existing = null): array
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
            'vendor_id' => ['required', 'exists:vendors,id'],
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
