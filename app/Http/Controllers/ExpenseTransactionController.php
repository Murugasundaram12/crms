<?php

namespace App\Http\Controllers;

use App\Models\ExpenseTransaction;
use App\Models\MainCategory;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExpenseTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseTransaction::query()
            ->where('delete_status', false)
            ->with(['mainCategory', 'category', 'project', 'paymentMethod']);

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($qq) use ($q) {
                $qq->where('description', 'like', "%{$q}%")
                    ->orWhereHas('paymentMethod', fn($pm) => $pm->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('mainCategory', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('project', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('main_category_id')) {
            $query->where('main_category_id', $request->integer('main_category_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('current_date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('current_date', '<=', $request->date('date_to')->toDateString());
        }

        $expenseTransactions = $query->latest()->paginate((int) $request->get('paginate', 12))->withQueryString();

        return view('pages.expense_transactions.index', [
            'expenseTransactions' => $expenseTransactions,
            'mainCategories' => MainCategory::query()->where('status', 'active')->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'projects' => Project::query()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $mainCategories = MainCategory::query()->whereIn('status', ['active', 1])->orderBy('name')->get();
        $categories = Category::query()
            ->with('mainCategories:id')
            ->orderBy('name')
            ->get();
        $projects = Project::query()->orderBy('name')->get();

        return view('pages.expense_transactions.create', [
            'mainCategories' => $mainCategories,
            'projects' => $projects,
            'categories' => $categories,
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateExpense($request);

        $validated['user_id'] = Auth::id();
        $validated['current_date'] = $this->parseDateToYmd($request->string('current_date'));
        $validated['current_time'] = $request->string('current_time');

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('expense-images', 'public');
        }

        ExpenseTransaction::create($validated);

        return redirect()->route('expense-transactions.index')->with('success', 'Expense added successfully.');
    }

    public function edit(ExpenseTransaction $expenseTransaction)
    {
        $mainCategories = MainCategory::query()->whereIn('status', ['active', 1])->orderBy('name')->get();
        $categories = Category::query()->whereHas('mainCategories', function ($q) use ($expenseTransaction) {
            $q->where('main_categories.id', $expenseTransaction->main_category_id);
        })->orderBy('name')->get();

        $projects = Project::query()->orderBy('name')->get();

        return view('pages.expense_transactions.edit', [
            'expenseTransaction' => $expenseTransaction,
            'mainCategories' => $mainCategories,
            'categories' => $categories,
            'projects' => $projects,
            'paymentMethods' => PaymentMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ExpenseTransaction $expenseTransaction): RedirectResponse
    {
        $validated = $this->validateExpense($request, $expenseTransaction);

        $validated['current_date'] = $this->parseDateToYmd($request->string('current_date'));
        $validated['current_time'] = $request->string('current_time');

        if ($request->hasFile('image')) {
            if ($expenseTransaction->image_path) {
                Storage::disk('public')->delete($expenseTransaction->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('expense-images', 'public');
        }

        $expenseTransaction->update($validated);

        return redirect()->route('expense-transactions.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(ExpenseTransaction $expenseTransaction): RedirectResponse
    {
        $expenseTransaction->delete_status = true;
        $expenseTransaction->active_status = false;
        $expenseTransaction->save();

        if ($expenseTransaction->image_path) {
            Storage::disk('public')->delete($expenseTransaction->image_path);
        }

        return redirect()->route('expense-transactions.index')->with('success', 'Expense deleted successfully.');
    }

    private function validateExpense(Request $request, ?ExpenseTransaction $existing = null): array
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
