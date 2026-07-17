<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MainCategory;
use App\Support\DeleteDependencyGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $mainCategories = MainCategory::select("id", "name")->orderBy('name')->get();

        $query = DB::table('categories')
            ->leftJoin('category_main_category as pivot', 'pivot.category_id', '=', 'categories.id')
            ->leftJoin('main_categories as pivot_main', 'pivot.main_category_id', '=', 'pivot_main.id')
            ->leftJoin('main_categories as legacy_main', 'categories.main_category_id', '=', 'legacy_main.id')
            ->select(
                'categories.id as category_id',
                DB::raw('COALESCE(pivot.main_category_id, categories.main_category_id) as main_category_id'),
                'categories.name as category_name',
                DB::raw("COALESCE(pivot_main.name, legacy_main.name, '-') as main_category_name"),
                'categories.created_at'
            );

        $mainCategoryId = $request->get('main_category_id');
        if ($mainCategoryId) {
            $query->where(function ($filterQuery) use ($mainCategoryId) {
                $filterQuery->where('pivot.main_category_id', $mainCategoryId)
                    ->orWhere('categories.main_category_id', $mainCategoryId);
            });
        }

        if ($request->filled('q')) {
            $query->where('categories.name', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('categories.created_at', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('categories.created_at', '<=', $request->date('date_to')->toDateString());
        }

        $categories = $query->orderBy('categories.created_at', 'desc')->paginate(10)->withQueryString();

        // Get all categories (for assign modal) - show all to allow assign/remove
        $masterCategories = Category::orderBy("name")->get();

        // Get assigned categories grouped by main_category_id (for modal checkbox states)
        $assignedCategories = [];
        foreach ($mainCategories as $mainCat) {
            $pivotIds = $mainCat->categories()->pluck('categories.id')->toArray();
            $legacyIds = Category::where('main_category_id', $mainCat->id)->pluck('id')->toArray();
            $assignedCategories[$mainCat->id] = array_values(array_unique(array_merge($pivotIds, $legacyIds)));
        }

        return view("pages.categories.index", compact("categories", "masterCategories", "mainCategories", "assignedCategories"));
    }

    public function create()
    {
        $mainCategories = MainCategory::select("id", "name")->orderBy('name')->get();
        return view("pages.categories.create", compact("mainCategories"));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "name" => "required|string|max:255|unique:categories,name",
            "main_category_id" => "nullable|exists:main_categories,id",
        ]);

        $category = Category::create($validated);
        $this->syncCategoryMainCategory($category, $validated['main_category_id'] ?? null);

        return redirect()->route("categories.index")->with("success", "Category created successfully.");
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        $mainCategories = MainCategory::select("id", "name")->orderBy('name')->get();
        return view("pages.categories.edit", compact("category", "mainCategories"));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $validated = $request->validate([
            "name" => "required|string|max:255|unique:categories,name," . $category->id,
            "main_category_id" => "nullable|exists:main_categories,id",
        ]);

        $category->update($validated);
        $this->syncCategoryMainCategory($category, $validated['main_category_id'] ?? null);

        return redirect()->route("categories.index")->with("success", "Category updated successfully.");
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $blockedBy = DeleteDependencyGuard::firstBlockingReference($category->id, [
            ['table' => 'expenses', 'column' => 'category_id', 'label' => 'expenses'],
            ['table' => 'expense_transactions', 'column' => 'category_id', 'label' => 'expenses'],
            ['table' => 'labour_expense_transactions', 'column' => 'category_id', 'label' => 'labour expenses'],
            ['table' => 'vendor_expense_transactions', 'column' => 'category_id', 'label' => 'vendor expenses'],
        ]);

        if ($blockedBy['blocked']) {
            return redirect()->route("categories.index")
                ->with("error", DeleteDependencyGuard::message('Category', $blockedBy['label']));
        }

        $category->mainCategories()->detach();
        $category->delete();
        return redirect()->route("categories.index")->with("success", "Category deleted successfully.");
    }

    public function assign(Request $request)
    {
        $request->validate([
            "main_category_id" => "required|exists:main_categories,id",
            "category_ids" => "array",
            "category_ids.*" => "exists:categories,id",
        ]);
        $mainCategoryId = $request->main_category_id;
        $selectedCategoryIds = $request->category_ids ?? [];
        $mainCategory = MainCategory::findOrFail($mainCategoryId);

        DB::transaction(function () use ($mainCategory, $mainCategoryId, $selectedCategoryIds) {
            $mainCategory->categories()->sync($selectedCategoryIds);

            Category::whereIn('id', $selectedCategoryIds)->update(['main_category_id' => $mainCategoryId]);
            Category::where('main_category_id', $mainCategoryId)
                ->when(! empty($selectedCategoryIds), fn($query) => $query->whereNotIn('id', $selectedCategoryIds))
                ->update(['main_category_id' => null]);
        });

        return redirect()->back()->with("success", "Categories assigned successfully");
    }

    private function syncCategoryMainCategory(Category $category, ?int $mainCategoryId): void
    {
        if ($mainCategoryId) {
            $category->mainCategories()->sync([$mainCategoryId]);
            return;
        }

        $category->mainCategories()->detach();
    }
}
