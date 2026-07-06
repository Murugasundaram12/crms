<?php

namespace App\Http\Controllers;

use App\Models\MainCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MainCategoryController extends Controller
{
    public function index(Request $request)
    {
        $mainCategoryQuery = MainCategory::query();
        $this->applySearchFilter($mainCategoryQuery, $request);
        $this->applyDateFilter($mainCategoryQuery, $request);

        $mainCategories = $mainCategoryQuery->latest()->paginate(10)->withQueryString();

        return view('pages.main_categories.index', compact('mainCategories'));
    }

    public function create()
    {
        return view('pages.main_categories.create');
    }

    public function toggle(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])]
        ]);

        $mainCategory = MainCategory::findOrFail($id);
        $mainCategory->status = $request->status;
        $mainCategory->save();

        return response()->json(['success' => true]);
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateMainCategoryData($request);

        MainCategory::create($validatedData);

        return redirect()->route('main_categories.index')->with('success', 'Main category created successfully.');
    }

    public function edit($id)
    {
        $mainCategory = MainCategory::findOrFail($id);

        return view('pages.main_categories.edit', compact('mainCategory'));
    }

    public function update(Request $request, $id)
    {
        $mainCategory = MainCategory::findOrFail($id);

        $validatedData = $this->validateMainCategoryData($request, $mainCategory);

        $mainCategory->update($validatedData);

        return redirect()->route('main_categories.index')->with('success', 'Main category updated successfully.');
    }

    public function destroy($id)
    {
        $mainCategory = MainCategory::findOrFail($id);

        $isInUse = DB::table('expenses')->where('main_category_id', $mainCategory->id)->exists()
            || DB::table('expense_transactions')->where('main_category_id', $mainCategory->id)->exists()
            || DB::table('labour_expense_transactions')->where('main_category_id', $mainCategory->id)->exists()
            || DB::table('vendor_expense_transactions')->where('main_category_id', $mainCategory->id)->exists();

        if ($isInUse) {
            return redirect()->route('main_categories.index')
                ->with('error', 'Main category is used in transactions and cannot be deleted.');
        }

        $mainCategory->categories()->detach();
        $mainCategory->delete();

        return redirect()->route('main_categories.index')->with('success', 'Main category deleted successfully.');
    }

    private function applySearchFilter($mainCategoryQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $mainCategoryQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder->where('name', 'like', "%{$searchTerm}%");
        });
    }

    private function applyDateFilter($mainCategoryQuery, Request $request): void
    {
        if ($request->filled('date_from')) {
            $mainCategoryQuery->whereDate('created_at', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $mainCategoryQuery->whereDate('created_at', '<=', $request->date('date_to')->toDateString());
        }
    }

    private function validateMainCategoryData(Request $request, ?MainCategory $mainCategory = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('main_categories', 'name')->ignore($mainCategory?->id)],
        ]);

        $validated['status'] = 'active'; // default active

        return $validated;
    }
}
