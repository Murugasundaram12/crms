<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Support\DeleteDependencyGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $vendorQuery = Vendor::query();
        $this->applySearchFilter($vendorQuery, $request);
        $this->applyDateFilter($vendorQuery, $request);

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

        Vendor::create($this->normalizeVendorAdvance($validatedData));

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

        $vendor->update($this->normalizeVendorAdvance($validatedData));

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $blockedBy = DeleteDependencyGuard::firstBlockingReference($vendor->id, [
            ['table' => 'vendor_expense_transactions', 'column' => 'vendor_id', 'label' => 'vendor expenses'],
            ['table' => 'expenses', 'column' => 'vendor_id', 'label' => 'expenses'],
            ['table' => 'transfer_details', 'column' => 'vendor_id', 'label' => 'wallet transfers'],
            ['table' => 'tool_material_assignments', 'column' => 'vendor_id', 'label' => 'tool/material purchases'],
            ['table' => 'advance_history', 'column' => 'vendor_id', 'label' => 'advance history'],
        ]);

        if ($blockedBy['blocked']) {
            return redirect()->route('vendors.index')
                ->with('error', DeleteDependencyGuard::message('Vendor', $blockedBy['label']));
        }

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

    private function applyDateFilter($vendorQuery, Request $request): void
    {
        if ($request->filled('date_from')) {
            $vendorQuery->whereDate('created_at', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $vendorQuery->whereDate('created_at', '<=', $request->date('date_to')->toDateString());
        }
    }

    private function validateVendorData(Request $request, ?Vendor $vendor = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('vendors', 'name')->ignore($vendor?->id)],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'advance_amount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'phone.regex' => 'Enter a valid 10 digit Indian mobile number.',
        ]);
    }

    private function normalizeVendorAdvance(array $validatedData): array
    {
        if (array_key_exists('advance_amount', $validatedData) && Schema::hasColumn('vendors', 'advance_amt')) {
            $validatedData['advance_amt'] = $validatedData['advance_amount'] ?? 0;
        }

        return $validatedData;
    }
}
