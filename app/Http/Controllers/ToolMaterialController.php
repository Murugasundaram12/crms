<?php

namespace App\Http\Controllers;

use App\Models\ToolMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ToolMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $query = ToolMaterial::query()->with(['assignments.fromProject', 'assignments.toProject']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to')->toDateString());
        }

        $toolsMaterials = $query
            ->latest('date')
            ->latest()
            ->paginate((int) $request->input('paginate', 10));

        $allItems = ToolMaterial::query()->with(['assignments.fromProject', 'assignments.toProject'])->get();
        $summary = [
            'items' => $allItems->count(),
            'tools' => $allItems->where('item_type', 'tool')->count(),
            'materials' => $allItems->where('item_type', 'material')->count(),
            'stock_value' => $allItems->sum('stock_amount'),
            'low_stock' => $allItems->filter(fn(ToolMaterial $item) => $item->is_low_stock)->count(),
        ];

        return view('pages.tools_materials.index', compact('toolsMaterials', 'summary'));
    }

    public function create(): View
    {
        return view('pages.tools_materials.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateToolMaterial($request);
        $validated['opening_quantity'] = (float) ($validated['opening_quantity'] ?? 0);
        $validated['opening_rate'] = (float) ($validated['opening_rate'] ?? 0);
        $validated['opening_amount'] = round($validated['opening_quantity'] * $validated['opening_rate'], 2);
        $validated['reorder_level'] = (float) ($validated['reorder_level'] ?? 0);
        $validated['active_status'] = $request->boolean('active_status', true);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('tools-materials', 'public');
        }

        ToolMaterial::query()->create($validated);

        return redirect()->route('tools-materials.index')->with('success', 'Tool / material added successfully.');
    }

    public function edit(ToolMaterial $toolsMaterial): View
    {
        return view('pages.tools_materials.edit', compact('toolsMaterial'));
    }

    public function update(Request $request, ToolMaterial $toolsMaterial): RedirectResponse
    {
        $validated = $this->validateToolMaterial($request);
        $validated['opening_quantity'] = (float) ($validated['opening_quantity'] ?? 0);
        $validated['opening_rate'] = (float) ($validated['opening_rate'] ?? 0);
        $validated['opening_amount'] = round($validated['opening_quantity'] * $validated['opening_rate'], 2);
        $validated['reorder_level'] = (float) ($validated['reorder_level'] ?? 0);
        $validated['active_status'] = $request->boolean('active_status', true);

        if ($request->hasFile('image')) {
            if ($toolsMaterial->image_path) {
                Storage::disk('public')->delete($toolsMaterial->image_path);
            }

            $validated['image_path'] = $request->file('image')->store('tools-materials', 'public');
        }

        $toolsMaterial->update($validated);

        return redirect()->route('tools-materials.index')->with('success', 'Tool / material updated successfully.');
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

        return redirect()->route('tools-materials.index')->with('success', 'Tool / material deleted successfully.');
    }

    private function validateToolMaterial(Request $request): array
    {
        return $request->validate([
            'item_type' => ['required', Rule::in(['tool', 'material'])],
            'sku' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:2048'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date' => ['required', 'date'],
            'opening_quantity' => ['nullable', 'numeric', 'min:0'],
            'opening_rate' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'active_status' => ['nullable', 'boolean'],
        ]);
    }
}
