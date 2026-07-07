<?php

namespace App\Http\Controllers;

use App\Models\AdvanceHistory;
use App\Models\Category;
use App\Models\ExpenseUnpaidDate;
use App\Models\MainCategory;
use App\Models\Project;
use App\Models\Vendor;
use App\Models\VendorExpenseTransaction;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorExpensesController extends Controller
{
    public function history(Request $request)
    {
        $query = VendorExpenseTransaction::query()
            ->where('delete_status', false)
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
                        ->orWhere('payment_mode', 'like', "%{$q}%")
                        ->orWhereHas('mainCategory', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"));
                });
            });

        $totals = (clone $query)
            ->selectRaw('COALESCE(SUM(amount),0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amount),0) as total_paid_amount')
            ->selectRaw('COALESCE(SUM(unpaid_amount),0) as total_unpaid_amount')
            ->selectRaw('COALESCE(SUM(extra_amount),0) as total_advanced_amount')
            ->first();

        $items = $query
            ->latest()
            ->paginate((int) $request->get('paginate', 10))
            ->withQueryString();

        $vendors = Vendor::query()->orderBy('name')->get();
        $projects = Project::query()->orderBy('name')->get();
        $mainCategories = MainCategory::query()->where('status', 'active')->orderBy('name')->get();
        $categories = Category::query()->orderBy('name')->get();

        return view('pages.vendor_expenses.history', [
            'transactions' => $items,
            'vendors' => $vendors,
            'projects' => $projects,
            'mainCategories' => $mainCategories,
            'categories' => $categories,
            'totals' => $totals,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
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

            $tx = VendorExpenseTransaction::create([
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
                'vendor_id' => $validated['vendor_id'],
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
                    'description' => 'Vendor expense paid',
                    'reference_type' => 'vendor_expense',
                    'reference_id' => $tx->id,
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);
            }

            if ($extraAmount > 0) {
                Vendor::query()->where('id', $validated['vendor_id'])->increment('advance_amt', $extraAmount);
                AdvanceHistory::create([
                    'labour_id' => null,
                    'vendor_id' => $validated['vendor_id'],
                    'labour_expense_transaction_id' => $tx->id,
                    'amount' => $extraAmount,
                    'entry_type' => 'credit',
                    'notes' => 'Vendor extra amount added as advance',
                    'user_id' => Auth::id(),
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Vendor expense stored successfully.');
    }

    public function unpaidHistory(Request $request)
    {
        $items = VendorExpenseTransaction::query()
            ->where('delete_status', false)
            ->where('unpaid_amount', '>', 0)
            ->with(['vendor', 'project'])
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $vendors = Vendor::query()->orderBy('name')->get();
        return view('pages.vendor_expenses.unpaid', ['transactions' => $items, 'vendors' => $vendors]);
    }

    public function unpaidStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:vendor_expense_transactions,id'],
            'paid_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $tx = VendorExpenseTransaction::query()->lockForUpdate()->findOrFail((int) $validated['id']);
            $pay = min((float) $validated['paid_amount'], (float) $tx->unpaid_amount);

            if ($pay <= 0) {
                return;
            }

            ExpenseUnpaidDate::create([
                'expense_id' => null,
                'vendor_expense_transaction_id' => $tx->id,
                'user_id' => Auth::id(),
                'paid_amount' => $pay,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
                'notes' => $validated['notes'] ?? 'Vendor unpaid settlement',
            ]);

            Wallet::create([
                'user_id' => Auth::id(),
                'amount' => $pay,
                'transfer_type' => 1,
                'description' => 'Vendor unpaid settlement',
                'reference_type' => 'vendor_expense_unpaid',
                'reference_id' => $tx->id,
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
            ]);

            $tx->update([
                'paid_amount' => (float) $tx->paid_amount + $pay,
                'unpaid_amount' => max((float) $tx->unpaid_amount - $pay, 0),
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_type' => ['required', 'in:credit,withdraw'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $vendor = Vendor::query()->lockForUpdate()->findOrFail((int) $validated['vendor_id']);
            $amount = (float) $validated['amount'];

            if ($validated['entry_type'] === 'withdraw') {
                $amount = min($amount, (float) $vendor->advance_amt);
                $vendor->decrement('advance_amt', $amount);
                Wallet::create([
                    'user_id' => Auth::id(),
                    'amount' => $amount,
                    'transfer_type' => 0,
                    'description' => 'Vendor advance withdraw refund',
                    'reference_type' => 'vendor_advance_withdraw',
                    'reference_id' => $vendor->id,
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);
            } else {
                $vendor->increment('advance_amt', $amount);
                Wallet::create([
                    'user_id' => Auth::id(),
                    'amount' => $amount,
                    'transfer_type' => 1,
                    'description' => 'Vendor advance credit',
                    'reference_type' => 'vendor_advance_credit',
                    'reference_id' => $vendor->id,
                    'current_date' => now()->toDateString(),
                    'current_time' => now()->format('H:i:s'),
                ]);
            }

            AdvanceHistory::create([
                'labour_id' => null,
                'vendor_id' => $vendor->id,
                'labour_expense_transaction_id' => null,
                'amount' => $amount,
                'entry_type' => $validated['entry_type'],
                'notes' => $validated['notes'] ?? 'Vendor advance entry',
                'user_id' => Auth::id(),
                'current_date' => now()->toDateString(),
                'current_time' => now()->format('H:i:s'),
            ]);
        });

        return redirect()->back()->with('success', 'Vendor advance entry stored successfully.');
    }

    public function deleteRecord(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:vendor_expense_transactions,id'],
            'delete_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $tx = VendorExpenseTransaction::query()->where('delete_status', false)->findOrFail((int) $validated['id']);

            if ((float) $tx->paid_amount > 0) {
                Wallet::create([
                    'user_id' => Auth::id(),
                    'amount' => (float) $tx->paid_amount,
                    'transfer_type' => 0,
                    'description' => 'Vendor expense delete refund',
                    'reference_type' => 'vendor_expense',
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

        return redirect()->back()->with('success', 'Vendor expense deleted successfully.');
    }

    public function deletedHistory(Request $request)
    {
        $items = VendorExpenseTransaction::query()
            ->where('delete_status', true)
            ->with(['vendor', 'project'])
            ->when($request->filled('vendor_id'), fn($q) => $q->where('vendor_id', $request->integer('vendor_id')))
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        $vendors = Vendor::query()->orderBy('name')->get();
        return view('pages.vendor_expenses.deleted', ['transactions' => $items, 'vendors' => $vendors]);
    }
}
