<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\MainCategory;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpensesController extends Controller
{
    public function history(Request $request)
    {
        $query = $this->baseQuery($request)
            ->whereNull('labour_id')
            ->whereNull('vendor_id');

        [$expenses, $totals] = $this->paginateWithTotals($query, $request);
        $editingExpense = $this->editingExpense($request);

        return view('pages.expenses.index', $this->commonViewData() + compact('expenses', 'totals', 'editingExpense'));
    }

    public function deletedHistory(Request $request)
    {
        $query = $this->baseQuery($request, true)
            ->whereNull('labour_id')
            ->whereNull('vendor_id');

        [$expenses, $totals] = $this->paginateWithTotals($query, $request);

        return view('pages.expenses.index', $this->commonViewData() + compact('expenses', 'totals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateExpense($request);

        DB::transaction(function () use ($validated) {
            $amount = (int) $validated['amount'];
            $paidAmount = (int) $validated['paid_amt'];
            $unpaidAmount = max($amount - $paidAmount, 0);
            $extraAmount = max($paidAmount - $amount, 0);

            $expense = Expense::create([
                ...$validated,
                'user_id' => Auth::id(),
                'paid_amt' => $paidAmount,
                'unpaid_amt' => $unpaidAmount,
                'extra_amt' => $extraAmount,
                'current_date' => $validated['current_date'] ?? now(),
            ]);
        });

        return redirect()
            ->route('expenses.history', array_filter([
                'project_id' => $validated['project_id'] ?? null,
            ]))
            ->with('success', 'Expense stored successfully.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $expense = Expense::query()->whereNull('deleted_at')->findOrFail($id);
        $validated = $this->validateExpense($request);

        DB::transaction(function () use ($expense, $validated) {
            $amount = (int) $validated['amount'];
            $paidAmount = (int) $validated['paid_amt'];
            $unpaidAmount = max($amount - $paidAmount, 0);
            $extraAmount = max($paidAmount - $amount, 0);

            $expense->update([
                ...$validated,
                'editedBy' => Auth::id(),
                'paid_amt' => $paidAmount,
                'unpaid_amt' => $unpaidAmount,
                'extra_amt' => $extraAmount,
            ]);
        });

        return redirect()->back()->with('success', 'Expense updated successfully.');
    }

    public function deleteRecord(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_id' => ['required', 'integer', 'exists:expenses,id'],
            'delete_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $expense = Expense::query()->whereNull('deleted_at')->findOrFail((int) $validated['expense_id']);

        DB::transaction(function () use ($expense, $validated) {
            $expense->reason = $validated['delete_reason'] ?? null;
            $expense->editedBy = Auth::id();
            $expense->save();
            $expense->delete();
        });

        return redirect()->back()->with('success', 'Expense deleted successfully.');
    }

    private function baseQuery(Request $request, bool $deleted = false)
    {
        $query = $deleted ? Expense::onlyTrashed() : Expense::query();

        return $query
            ->with(['project', 'employee', 'editedByUser', 'mainCategory', 'category'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->string('q')->toString();
                $query->where(function ($qq) use ($q) {
                    $qq->where('description', 'like', "%{$q}%")
                        ->orWhere('amount', 'like', "%{$q}%")
                        ->orWhere('paid_amt', 'like', "%{$q}%")
                        ->orWhere('unpaid_amt', 'like', "%{$q}%")
                        ->orWhere('extra_amt', 'like', "%{$q}%")
                        ->orWhere('reason', 'like', "%{$q}%")
                        ->orWhereHas('mainCategory', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('project', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('user', fn($userQuery) => $userQuery->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($request->filled('main_category'), function ($query) use ($request) {
                $mainCategory = $request->string('main_category')->toString();
                $query->whereHas('mainCategory', fn($q) => $q->where('name', $mainCategory));
            })
            ->when($request->filled('category_name'), function ($query) use ($request) {
                $category = $request->string('category_name')->toString();
                $query->whereHas('category', fn($q) => $q->where('name', $category));
            })
            ->when($request->filled('project_id'), fn($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('member_id'), fn($q) => $q->where('user_id', $request->integer('member_id')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('current_date', '>=', $request->date('date_from')->toDateString()))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('current_date', '<=', $request->date('date_to')->toDateString()));
    }

    private function paginateWithTotals($query, Request $request): array
    {
        $totals = (clone $query)
            ->selectRaw('COALESCE(SUM(amount),0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amt),0) as total_paid_amount')
            ->selectRaw('COALESCE(SUM(unpaid_amt),0) as total_unpaid_amount')
            ->selectRaw('COALESCE(SUM(extra_amt),0) as total_advanced_amount')
            ->first();

        $expenses = $query
            ->latest('current_date')
            ->paginate((int) $request->get('paginate', 10))
            ->withQueryString();

        return [$expenses, $totals];
    }

    private function commonViewData(): array
    {
        return [
            'projects' => Project::query()->orderBy('name')->get(),
            'employees' => User::query()->orderBy('name')->get(),
            'mainCategories' => MainCategory::query()->whereIn('status', ['active', 1])->orderBy('name')->pluck('name'),
            'categories' => Category::query()->orderBy('name')->pluck('name'),
            'mainCategoryOptions' => MainCategory::query()->whereIn('status', ['active', 1])->orderBy('name')->get(),
            'categoryOptions' => Category::query()->orderBy('name')->get(),
            'paymentModes' => Expense::paymentModes(),
        ];
    }

    private function editingExpense(Request $request): ?Expense
    {
        if (! $request->filled('edit')) {
            return null;
        }

        return Expense::query()
            ->with(['project', 'employee', 'editedByUser', 'mainCategory', 'category'])
            ->whereNull('labour_id')
            ->whereNull('vendor_id')
            ->whereNull('deleted_at')
            ->find($request->integer('edit'));
    }

    private function validateExpense(Request $request): array
    {
        $request->merge([
            'paid_amt' => $request->input('paid_amt', $request->input('paid_amount', 0)),
            'current_date' => $request->input('current_date', $request->input('expense_date', now()->toDateString())),
        ]);

        return $request->validate([
            'main_category_id' => ['nullable', 'integer', 'exists:main_categories,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'amount' => ['required', 'integer', 'min:0'],
            'paid_amt' => ['required', 'integer', 'min:0'],
            'payment_mode' => ['nullable', 'integer'],
            'image' => ['nullable', 'string', 'max:250'],
            'description' => ['nullable', 'string'],
            'current_date' => ['required', 'date'],
        ]);
    }
}
