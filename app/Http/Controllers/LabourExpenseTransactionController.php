<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Labour;
use App\Models\LabourExpenseTransaction;
use App\Models\MainCategory;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LabourExpenseTransactionController extends Controller
{
    private array $paymentModes = [
        'Cash',
        'HDFC',
        'SBI',
        'Gpay',
        'PhonePe',
        'KVBL',
        'Kotak Mahindra',
        'TMB',
        'Equitas',
    ];

    public function index(Request $request)
    {
        $query = LabourExpenseTransaction::query()
            ->where('delete_status', false)
            ->with(['mainCategory', 'category', 'project', 'labour']);

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($qq) use ($q) {
                $qq->where('description', 'like', "%{$q}%")
                    ->orWhere('payment_mode', 'like', "%{$q}%")
                    ->orWhereHas('labour', fn($labour) => $labour->where('name', 'like', "%{$q}%"));
            });
        }

        $labourExpenseTransactions = $query
            ->latest()
            ->paginate((int) $request->get('paginate', 12))
            ->withQueryString();

        return view('pages.labour_expense_transactions.index', [
            'labourExpenseTransactions' => $labourExpenseTransactions,
        ]);
    }

    public function create()
    {
        $mainCategories = MainCategory::query()->where('status', true)->orderBy('name')->get();
        $projects = Project::query()->orderBy('name')->get();
        $labours = Labour::query()->orderBy('name')->get();

        return view('pages.labour_expense_transactions.create', [
            'mainCategories' => $mainCategories,
            'projects' => $projects,
            'labours' => $labours,
            'paymentModes' => $this->paymentModes,
        ]);
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

        LabourExpenseTransaction::create($validated);

        return redirect()->route('labour-expense-transactions.index')
            ->with('success', 'Labour expense added successfully.');
    }

    public function edit(LabourExpenseTransaction $labourExpenseTransaction)
    {
        $mainCategories = MainCategory::query()->where('status', true)->orderBy('name')->get();
        $categories = Category::query()
            ->whereHas('mainCategories', function ($q) use ($labourExpenseTransaction) {
                $q->where('main_categories.id', $labourExpenseTransaction->main_category_id);
            })
            ->orderBy('name')
            ->get();

        $projects = Project::query()->orderBy('name')->get();
        $labours = Labour::query()->orderBy('name')->get();

        return view('pages.labour_expense_transactions.edit', [
            'labourExpenseTransaction' => $labourExpenseTransaction,
            'mainCategories' => $mainCategories,
            'categories' => $categories,
            'projects' => $projects,
            'labours' => $labours,
            'paymentModes' => $this->paymentModes,
        ]);
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

        $labourExpenseTransaction->update($validated);

        return redirect()->route('labour-expense-transactions.index')
            ->with('success', 'Labour expense updated successfully.');
    }

    public function destroy(LabourExpenseTransaction $labourExpenseTransaction): RedirectResponse
    {
        $labourExpenseTransaction->delete_status = true;
        $labourExpenseTransaction->active_status = false;
        $labourExpenseTransaction->save();

        if ($labourExpenseTransaction->image_path) {
            Storage::disk('public')->delete($labourExpenseTransaction->image_path);
        }

        return redirect()->route('labour-expense-transactions.index')
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
            'payment_mode' => ['required', Rule::in($this->paymentModes)],
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
