<?php

namespace App\Http\Controllers;

use App\Models\LabourRole;
use App\Support\DeleteDependencyGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LabourRoleController extends Controller
{
    public function index(Request $request)
    {
        // Build the labour role query and apply the requested filters.
        $labourRoleQuery = LabourRole::query()->withCount('labours');
        $this->applySearchFilter($labourRoleQuery, $request);
        $this->applySalaryTypeFilter($labourRoleQuery, $request);
        $this->applyDateFilter($labourRoleQuery, $request);

        // Load the filtered labour roles for the listing page.
        $labourRoles = $labourRoleQuery->latest()->paginate(10)->withQueryString();

        return view('pages.labour_roles.index', compact('labourRoles'));
    }

    public function create()
    {
        // Show the form used to create a new labour role.
        return view('pages.labour_roles.create');
    }

    public function store(Request $request)
    {
        // Validate the form before creating a new labour role.
        $validatedData = $this->validateLabourRoleData($request);

        // Save the new labour role record.
        LabourRole::create($validatedData);

        return redirect()->route('labour_roles.index')->with('success', 'Labour role created successfully.');
    }

    public function edit($id)
    {
        // Load the selected labour role for editing.
        $labourRole = LabourRole::findOrFail($id);

        return view('pages.labour_roles.edit', compact('labourRole'));
    }

    public function update(Request $request, $id)
    {
        // Load the selected labour role before updating it.
        $labourRole = LabourRole::findOrFail($id);

        // Validate the submitted values and save the changes.
        $validatedData = $this->validateLabourRoleData($request, $labourRole);
        $labourRole->update($validatedData);

        return redirect()->route('labour_roles.index')->with('success', 'Labour role updated successfully.');
    }

    public function destroy($id)
    {
        $labourRole = LabourRole::findOrFail($id);
        $blockedBy = DeleteDependencyGuard::firstBlockingReference($labourRole->id, [
            ['table' => 'labours', 'column' => 'labour_role_id', 'label' => 'labours'],
        ]);

        if ($blockedBy['blocked']) {
            return redirect()->route('labour_roles.index')
                ->with('error', DeleteDependencyGuard::message('Labour role', $blockedBy['label']));
        }

        $labourRole->delete();

        return redirect()->route('labour_roles.index')->with('success', 'Labour role deleted successfully.');
    }

    private function applySearchFilter($labourRoleQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $labourRoleQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('salary_type', 'like', "%{$searchTerm}%")
                ->orWhere('salary', 'like', "%{$searchTerm}%");
        });
    }

    private function applySalaryTypeFilter($labourRoleQuery, Request $request): void
    {
        $salaryType = $request->string('salary_type')->toString();

        if ($salaryType === '') {
            return;
        }

        $labourRoleQuery->where('salary_type', $salaryType);
    }

    private function applyDateFilter($labourRoleQuery, Request $request): void
    {
        if ($request->filled('date_from')) {
            $labourRoleQuery->whereDate('created_at', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $labourRoleQuery->whereDate('created_at', '<=', $request->date('date_to')->toDateString());
        }
    }

    private function validateLabourRoleData(Request $request, ?LabourRole $labourRole = null): array
    {
        // Validate each field before saving the labour role.
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('labour_roles', 'name')->ignore($labourRole?->id)],
            'salary_type' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'salary' => ['required', 'numeric'],
        ]);
    }
}
