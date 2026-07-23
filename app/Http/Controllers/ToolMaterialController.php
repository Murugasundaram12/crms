<?php

namespace App\Http\Controllers;

use App\Models\Preorder;
use App\Models\ToolMaterial;
use App\Models\ToolMaterialAssignment;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ToolMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = $request->string('tab', 'purchase')->toString();
        if (! in_array($activeTab, ['preorder', 'purchase', 'issue_to_site'], true)) {
            $activeTab = 'purchase';
        }

        // 1. Preorder Query
        $preorderQuery = Preorder::query()->with(['toolMaterial', 'vendor', 'paymentMethod', 'creator']);
        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $preorderQuery->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('toolMaterial', fn($toolQuery) => $toolQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('vendor', fn($vendorQuery) => $vendorQuery->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('status') && $activeTab === 'preorder') {
            $preorderQuery->where('status', $request->string('status')->toString());
        }
        if ($request->filled('date_from')) {
            $preorderQuery->whereDate('preorder_date', '>=', $request->date('date_from')->toDateString());
        }
        if ($request->filled('date_to')) {
            $preorderQuery->whereDate('preorder_date', '<=', $request->date('date_to')->toDateString());
        }
        $preorders = $preorderQuery->latest('preorder_date')->latest()->paginate((int) $request->input('paginate', 10), ['*'], 'preorders_page')->withQueryString();

        // 2. Purchase / Stock List Query
        $purchaseQuery = ToolMaterial::query()->with(['assignments.fromProject', 'assignments.toProject']);
        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $purchaseQuery->where('name', 'like', "%{$search}%");
        }
        if ($request->filled('date_from')) {
            $purchaseQuery->whereDate('date', '>=', $request->date('date_from')->toDateString());
        }
        if ($request->filled('date_to')) {
            $purchaseQuery->whereDate('date', '<=', $request->date('date_to')->toDateString());
        }
        if ($request->filled('status') && $activeTab === 'purchase') {
            $status = $request->string('status')->toString();
            if ($status === 'active') {
                $purchaseQuery->where('active_status', true);
            } elseif ($status === 'inactive') {
                $purchaseQuery->where('active_status', false);
            }
        }
        $toolsMaterials = $purchaseQuery->latest('date')->latest()->paginate((int) $request->input('paginate', 10), ['*'], 'purchases_page')->withQueryString();

        // 3. Issue to Site Query
        $issueQuery = ToolMaterialAssignment::query()
            ->with(['toolMaterial', 'toProject', 'handler'])
            ->where('transaction_type', 'issue_to_site');

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $issueQuery->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('receiver_name', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('toolMaterial', fn($toolQuery) => $toolQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('toProject', fn($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('status') && $activeTab === 'issue_to_site') {
            $issueQuery->where('status', $request->string('status')->toString());
        }
        if ($request->filled('date_from')) {
            $issueQuery->whereDate('transferred_at', '>=', $request->date('date_from')->toDateString());
        }
        if ($request->filled('date_to')) {
            $issueQuery->whereDate('transferred_at', '<=', $request->date('date_to')->toDateString());
        }
        $issueAssignments = $issueQuery->latest('transferred_at')->latest()->paginate((int) $request->input('paginate', 10), ['*'], 'issues_page')->withQueryString();

        $allItems = ToolMaterial::query()->with(['assignments.fromProject', 'assignments.toProject'])->get();
        $summary = [
            'items' => $allItems->count(),
            'tools' => $allItems->where('item_type', 'tool')->count(),
            'materials' => $allItems->where('item_type', 'material')->count(),
            'stock_value' => $allItems->sum('stock_amount'),
            'low_stock' => $allItems->filter(fn(ToolMaterial $item) => $item->is_low_stock)->count(),
        ];

        return view('pages.tools_materials.index', compact('toolsMaterials', 'preorders', 'issueAssignments', 'summary', 'activeTab'));
    }

    public function create(): View
    {
        $units = Unit::query()->active()->orderBy('name')->get();

        return view('pages.tools_materials.create', compact('units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateToolMaterial($request);
        $validated = $this->normalizeToolMaterialStockFields($validated);
        $validated['active_status'] = $request->boolean('active_status', true);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('tools-materials', 'public');
        }

        ToolMaterial::query()->create($validated);

        return redirect()->route('tools-materials.index', ['tab' => 'purchase'])->with('success', 'Tool / material added successfully.');
    }

    public function edit(ToolMaterial $toolsMaterial): View
    {
        $units = Unit::query()
            ->where('active_status', true)
            ->orWhere('code', $toolsMaterial->unit)
            ->orderBy('name')
            ->get();

        return view('pages.tools_materials.edit', compact('toolsMaterial', 'units'));
    }

    public function update(Request $request, ToolMaterial $toolsMaterial): RedirectResponse
    {
        $validated = $this->validateToolMaterial($request);
        $validated = $this->normalizeToolMaterialStockFields($validated);
        $validated['active_status'] = $request->boolean('active_status', true);

        if ($request->hasFile('image')) {
            if ($toolsMaterial->image_path) {
                Storage::disk('public')->delete($toolsMaterial->image_path);
            }

            $validated['image_path'] = $request->file('image')->store('tools-materials', 'public');
        }

        $toolsMaterial->update($validated);

        return redirect()->route('tools-materials.index', ['tab' => 'purchase'])->with('success', 'Tool / material updated successfully.');
    }

    public function destroy(ToolMaterial $toolsMaterial): RedirectResponse
    {
        if ($toolsMaterial->assignments()->exists()) {
            return back()->with('error', 'This tool / material has stock transactions. Cancel or delete the transactions before deleting this item.');
        }

        if ($toolsMaterial->image_path) {
            Storage::disk('public')->delete($toolsMaterial->image_path);
        }

        $toolsMaterial->delete();

        return redirect()->route('tools-materials.index', ['tab' => 'purchase'])->with('success', 'Tool / material deleted successfully.');
    }

    private function validateToolMaterial(Request $request): array
    {
        return $request->validate([
            'item_type' => ['required', Rule::in(['tool', 'material'])],
            'sku' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required_if:item_type,material', 'nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:2048'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date' => ['required', 'date'],
            'opening_quantity' => ['required_if:item_type,material', 'nullable', 'numeric', 'min:0'],
            'opening_rate' => ['required_if:item_type,material', 'nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'active_status' => ['nullable', 'boolean'],
        ]);
    }

    private function normalizeToolMaterialStockFields(array $validated): array
    {
        if (($validated['item_type'] ?? null) === 'tool') {
            $validated['unit'] = 'Nos';
        }

        $validated['opening_quantity'] = (float) ($validated['opening_quantity'] ?? 0);
        $validated['opening_rate'] = (float) ($validated['opening_rate'] ?? 0);
        $validated['opening_amount'] = round($validated['opening_quantity'] * $validated['opening_rate'], 2);
        $validated['reorder_level'] = (float) ($validated['reorder_level'] ?? 0);

        return $validated;
    }
}
