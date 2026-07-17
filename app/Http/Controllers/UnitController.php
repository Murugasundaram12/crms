<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(Request $request): View
    {
        $query = Unit::query();

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($filter) use ($search): void {
                $filter->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('active_status', $request->status === 'active');
        }

        $units = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('pages.units.index', compact('units'));
    }

    public function create(): View
    {
        return view('pages.units.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Unit::query()->create($this->validatedData($request));

        return redirect()->route('units.index')->with('success', 'Unit created successfully.');
    }

    public function edit(Unit $unit): View
    {
        return view('pages.units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $unit->update($this->validatedData($request, $unit));

        return redirect()->route('units.index')->with('success', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $usedInTools = DB::table('tools_materials')
            ->whereIn('unit', [$unit->code, $unit->name])
            ->exists();
        $usedInQuotations = DB::table('quotation_items')
            ->whereIn('unit', [$unit->code, $unit->name])
            ->exists();

        if ($usedInTools || $usedInQuotations) {
            return redirect()->route('units.index')
                ->with('error', 'This unit is already used in tools/materials or quotations. Make it inactive instead of deleting.');
        }

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Unit deleted successfully.');
    }

    private function validatedData(Request $request, ?Unit $unit = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('units', 'name')->ignore($unit)],
            'code' => ['required', 'string', 'max:50', Rule::unique('units', 'code')->ignore($unit)],
            'description' => ['nullable', 'string', 'max:255'],
            'active_status' => ['nullable', 'boolean'],
        ]);

        $validated['active_status'] = $request->boolean('active_status', true);

        return $validated;
    }
}
