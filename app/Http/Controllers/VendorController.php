<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $vendorQuery = Vendor::query();
        $this->applySearchFilter($vendorQuery, $request);

        $vendors = $vendorQuery->latest()->paginate(10)->withQueryString();

        return view('pages.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('pages.vendors.create');
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateVendorData($request);

        Vendor::create($validatedData);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);

        return view('pages.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $validatedData = $this->validateVendorData($request, $vendor);

        $vendor->update($validatedData);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    private function applySearchFilter($vendorQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $vendorQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('phone', 'like', "%{$searchTerm}%")
                ->orWhere('address', 'like', "%{$searchTerm}%")
                ->orWhere('advance_amount', 'like', "%{$searchTerm}%");
        });
    }

    private function validateVendorData(Request $request, ?Vendor $vendor = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('vendors', 'name')->ignore($vendor?->id)],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
