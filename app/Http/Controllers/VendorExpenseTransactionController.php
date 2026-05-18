<?php

namespace App\Http\Controllers;

use App\Models\VendorExpenseTransaction;
use App\Models\MainCategory;
use App\Models\Category;
use App\Models\Project;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VendorExpenseTransactionController extends Controller
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
        $query = VendorExpenseTransaction::query()
            ->where('delete_status', false)
            ->with(['mainCategory', 'category', 'project', 'vendor']);

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($qq) use ($q) {
                $qq->where('description', 'like', "%{$q}%")
                    ->orWhere('payment_mode', 'like', "%{$q}%")
                    ->orWhereHas('vendor', fn($vendor) => $vendor->where('name', 'like', "%{$q}%"));
            });
        }

        $vendorExpenseTransactions = $query->latest()->paginate((int) $request->get('paginate', 12))->withQueryString();

        return view('pages.vendor_expense_transactions.index', [
            'vendorExpenseTransactions' => $vendorExpenseTransactions,
        ]);
    }

    public function create()
    {
        $mainCategories = MainCategory::query()->where('status', true)->orderBy('name')->get();
        $projects = Project::query()->orderBy('name')->get();
        $vendors = Vendor::query()->orderBy('name')->get();

        return view('pages.vendor_expense_transactions.create', [
            'mainCategories' => $mainCategories,
            'projects' => $projects,
            'vendors' => $vendors,
            'paymentModes' => $this->paymentModes,
        ]);
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

        VendorExpenseTransaction::create($validated);

        return redirect()->route('vendor-expense-transactions.index')->with('success', 'Vendor expense added successfully.');
    }

    public function edit(VendorExpenseTransaction $vendorExpenseTransaction)
    {
        $mainCategories = MainCategory::query()->where('status', true)->orderBy('name')->get();
        $categories = Category::query()->whereHas('mainCategories', function ($q) use ($vendorExpenseTransaction) {
            $q->where('main_categories.id', $vendorExpenseTransaction->main_category_id);
        })->orderBy('name')->get();

        $projects = Project::query()->orderBy('name')->get();
        $vendors = Vendor::query()->orderBy('name')->get();

        return view('pages.vendor_expense_transactions.edit', [
            'vendorExpenseTransaction' => $vendorExpenseTransaction,
            'mainCategories' => $mainCategories,
            'categories' => $categories,
            'projects' => $projects,
            'vendors' => $vendors,
            'paymentModes' => $this->paymentModes,
        ]);
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

        $vendorExpenseTransaction->update($validated);

        return redirect()->route('vendor-expense-transactions.index')->with('success', 'Vendor expense updated successfully.');
    }

    public function destroy(VendorExpenseTransaction $vendorExpenseTransaction): RedirectResponse
    {
        $vendorExpenseTransaction->delete_status = true;
        $vendorExpenseTransaction->active_status = false;
        $vendorExpenseTransaction->save();

        if ($vendorExpenseTransaction->image_path) {
            Storage::disk('public')->delete($vendorExpenseTransaction->image_path);
        }

        return redirect()->route('vendor-expense-transactions.index')->with('success', 'Vendor expense deleted successfully.');
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
            'payment_mode' => ['required', Rule::in($this->paymentModes)],
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
