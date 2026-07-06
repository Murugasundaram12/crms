<?php

namespace App\Http\Controllers;

use App\Models\AdvanceHistory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseUnpaidDate;
use App\Models\MainCategory;
use App\Models\Project;
use App\Models\Vendor;
use App\Services\CrmBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VendorExpensesController extends Controller
{
    public function history(Request $request)
    {
        $query = $this->vendorExpenseQuery($request);
        [$transactions, $totals] = $this->paginateWithTotals($query, $request);

        return view('pages.vendor_expenses.history', $this->viewData() + compact('transactions', 'totals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateExpense($request);

        DB::transaction(function () use ($validated) {
            $amount = (int) $validated['amount'];
            $paidAmount = (int) $validated['paid_amount'];
            $unpaidAmount = max($amount - $paidAmount, 0);
            $extraAmount = max($paidAmount - $amount, 0);

            $expense = Expense::create([
                'user_id' => Auth::id(),
                'main_category_id' => $validated['main_category_id'] ?? null,
                'category_id' => $validated['category_id'],
                'project_id' => $validated['project_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount' => $amount,
                'paid_amt' => $paidAmount,
                'unpaid_amt' => $unpaidAmount,
                'extra_amt' => $extraAmount,
                'payment_mode' => $validated['payment_mode'] ?? null,
                'vendor_id' => $validated['vendor_id'],
                'current_date' => $validated['current_date'] ?? now(),
                'image' => $validated['image'] ?? null,
            ]);

            if ($paidAmount > 0) {
                app(CrmBalanceService::class)->debitUserWallet((int) Auth::id(), $paidAmount, 'Vendor expense paid', 'vendor_expense', (int) $expense->id);
            }

            if ($extraAmount > 0) {
                app(CrmBalanceService::class)->adjustVendorAdvance((int) $validated['vendor_id'], $extraAmount);
                AdvanceHistory::create($this->filterColumns('advance_history', [
                    'vendor_id' => $validated['vendor_id'],
                    'labour_expense_transaction_id' => $expense->id,
                    'amount' => $extraAmount,
                    'entry_type' => 'credit',
                    'notes' => 'Vendor extra amount added as advance',
                    'user_id' => Auth::id(),
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]));
            }
        });

        return redirect()->back()->with('success', 'Vendor expense stored successfully.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $expense = Expense::query()
            ->whereNotNull('vendor_id')
            ->whereNull('deleted_at')
            ->findOrFail($id);
        $validated = $this->validateExpense($request);

        DB::transaction(function () use ($expense, $validated) {
            $amount = (int) $validated['amount'];
            $paidAmount = (int) $validated['paid_amount'];
            $unpaidAmount = max($amount - $paidAmount, 0);
            $extraAmount = max($paidAmount - $amount, 0);
            $oldPaid = (int) $expense->paid_amt;
            $oldExtra = (int) $expense->extra_amt;
            $oldVendorId = (int) $expense->vendor_id;
            $newVendorId = (int) $validated['vendor_id'];
            $balanceService = app(CrmBalanceService::class);

            $deltaPaid = $paidAmount - $oldPaid;
            if ($deltaPaid > 0) {
                $balanceService->debitUserWallet((int) Auth::id(), $deltaPaid, 'Vendor expense update debit', 'vendor_expense', (int) $expense->id);
            } elseif ($deltaPaid < 0) {
                $balanceService->creditUserWallet((int) Auth::id(), abs($deltaPaid), 'Vendor expense update refund', 'vendor_expense', (int) $expense->id);
            }

            if ($oldExtra > 0) {
                $balanceService->adjustVendorAdvance($oldVendorId, -$oldExtra);
            }
            if ($extraAmount > 0) {
                $balanceService->adjustVendorAdvance($newVendorId, $extraAmount);
            }

            $expense->update([
                'vendor_id' => $newVendorId,
                'user_id' => Auth::id(),
                'main_category_id' => $validated['main_category_id'] ?? null,
                'category_id' => $validated['category_id'],
                'project_id' => $validated['project_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'amount' => $amount,
                'paid_amt' => $paidAmount,
                'unpaid_amt' => $unpaidAmount,
                'extra_amt' => $extraAmount,
                'payment_mode' => $validated['payment_mode'] ?? null,
                'current_date' => $validated['current_date'] ?? now(),
                'image' => $validated['image'] ?? null,
                'editedBy' => Auth::id(),
            ]);
        });

        return redirect()->back()->with('success', 'Vendor expense updated successfully.');
    }

    public function unpaidHistory(Request $request)
    {
        $items = Expense::query()
            ->whereNotNull('vendor_id')
            ->where('unpaid_amt', '>', 0)
            ->with(['vendor', 'project'])
            ->when($request->filled('vendor_id'), fn($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->latest('current_date')
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $vendors = Vendor::query()->orderBy('name')->get();

        return view('pages.vendor_expenses.unpaid', ['transactions' => $items, 'vendors' => $vendors]);
    }

    public function unpaidStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:expenses,id'],
            'paid_amount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $expense = Expense::query()->whereNotNull('vendor_id')->lockForUpdate()->findOrFail((int) $validated['id']);
            $pay = min((int) $validated['paid_amount'], (int) $expense->unpaid_amt);

            if ($pay <= 0) {
                return;
            }

            ExpenseUnpaidDate::create($this->filterColumns('expenses_unpaid_date', [
                'expense_id' => $expense->id,
                'vendor_expense_transaction_id' => $expense->id,
                'user_id' => Auth::id(),
                'paid_amount' => $pay,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
                'notes' => $validated['notes'] ?? 'Vendor unpaid settlement',
            ]));

            app(CrmBalanceService::class)->debitUserWallet((int) Auth::id(), $pay, 'Vendor unpaid settlement', 'vendor_expense_unpaid', (int) $expense->id);

            $expense->update([
                'paid_amt' => (int) $expense->paid_amt + $pay,
                'unpaid_amt' => max((int) $expense->unpaid_amt - $pay, 0),
                'editedBy' => Auth::id(),
            ]);
        });

        return redirect()->back()->with('success', 'Vendor unpaid settled successfully.');
    }

    public function advanceHistory(Request $request)
    {
        $items = Vendor::query()
            ->when($request->filled('vendor_id'), fn($q) => $q->where('id', $request->integer('vendor_id')))
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $vendors = Vendor::query()->orderBy('name')->get();

        return view('pages.vendor_expenses.advance', ['advanceVendors' => $items, 'vendors' => $vendors]);
    }

    public function advanceStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'entry_type' => ['required', 'in:credit,withdraw'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $vendor = Vendor::query()->lockForUpdate()->findOrFail((int) $validated['vendor_id']);
            $amount = (int) $validated['amount'];

            if ($validated['entry_type'] === 'withdraw') {
                $amount = min($amount, (int) $vendor->advance_amt);
                app(CrmBalanceService::class)->adjustVendorAdvance((int) $vendor->id, -$amount);
                app(CrmBalanceService::class)->creditUserWallet((int) Auth::id(), $amount, 'Vendor advance withdraw refund', 'vendor_advance_withdraw', (int) $vendor->id);
            } else {
                app(CrmBalanceService::class)->adjustVendorAdvance((int) $vendor->id, $amount);
                app(CrmBalanceService::class)->debitUserWallet((int) Auth::id(), $amount, 'Vendor advance credit', 'vendor_advance_credit', (int) $vendor->id);
            }

            AdvanceHistory::create($this->filterColumns('advance_history', [
                'vendor_id' => $vendor->id,
                'amount' => $amount,
                'entry_type' => $validated['entry_type'],
                'notes' => $validated['notes'] ?? 'Vendor advance entry',
                'user_id' => Auth::id(),
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
            ]));
        });

        return redirect()->back()->with('success', 'Vendor advance entry stored successfully.');
    }

    public function deleteRecord(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:expenses,id'],
            'delete_reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $expense = Expense::query()->whereNotNull('vendor_id')->findOrFail((int) $validated['id']);

            if ((int) $expense->paid_amt > 0) {
                app(CrmBalanceService::class)->creditUserWallet((int) Auth::id(), (int) $expense->paid_amt, 'Vendor expense delete refund', 'vendor_expense', (int) $expense->id);
            }

            if ((int) $expense->extra_amt > 0) {
                app(CrmBalanceService::class)->adjustVendorAdvance((int) $expense->vendor_id, -(int) $expense->extra_amt);
            }

            $expense->reason = $validated['delete_reason'];
            $expense->editedBy = Auth::id();
            $expense->save();
            $expense->delete();
        });

        return redirect()->back()->with('success', 'Vendor expense deleted successfully.');
    }

    public function deletedHistory(Request $request)
    {
        $items = Expense::onlyTrashed()
            ->whereNotNull('vendor_id')
            ->with(['vendor', 'project'])
            ->when($request->filled('vendor_id'), fn($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->latest('current_date')
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $vendors = Vendor::query()->orderBy('name')->get();

        return view('pages.vendor_expenses.deleted', ['transactions' => $items, 'vendors' => $vendors]);
    }

    private function vendorExpenseQuery(Request $request)
    {
        return Expense::query()
            ->whereNotNull('vendor_id')
            ->with(['vendor', 'project', 'mainCategory', 'category'])
            ->when($request->filled('main_category_id'), fn($q) => $q->where('main_category_id', $request->integer('main_category_id')))
            ->when($request->filled('category_id'), fn($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('vendor_id'), fn($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('current_date', '>=', $request->date('date_from')->toDateString()))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('current_date', '<=', $request->date('date_to')->toDateString()))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function ($qq) use ($q) {
                    $qq->where('description', 'like', "%{$q}%")
                        ->orWhereHas('mainCategory', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"));
                });
            });
    }

    private function paginateWithTotals($query, Request $request): array
    {
        $totals = (clone $query)
            ->selectRaw('COALESCE(SUM(amount),0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amt),0) as total_paid_amount')
            ->selectRaw('COALESCE(SUM(unpaid_amt),0) as total_unpaid_amount')
            ->selectRaw('COALESCE(SUM(extra_amt),0) as total_advanced_amount')
            ->first();

        $transactions = $query->latest('current_date')->paginate((int) $request->get('paginate', 10))->withQueryString();

        return [$transactions, $totals];
    }

    private function viewData(): array
    {
        return [
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'projects' => Project::query()->orderBy('name')->get(),
            'mainCategories' => MainCategory::query()->where('status', 'active')->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ];
    }

    private function filterColumns(string $table, array $payload): array
    {
        return array_intersect_key($payload, array_flip(Schema::getColumnListing($table)));
    }

    private function validateExpense(Request $request): array
    {
        $request->merge([
            'paid_amount' => $request->input('paid_amount', $request->input('paid_amt', 0)),
        ]);

        return $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'main_category_id' => ['nullable', 'exists:main_categories,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'integer', 'min:0'],
            'paid_amount' => ['required', 'integer', 'min:0'],
            'payment_mode' => ['nullable', 'integer'],
            'current_date' => ['nullable', 'date'],
            'image' => ['nullable', 'string', 'max:250'],
        ]);
    }
}
