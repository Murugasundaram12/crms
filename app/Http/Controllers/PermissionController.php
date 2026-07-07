<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        // Load permissions for the listing page.
        $permissionQuery = Permission::query();

        if ($request->filled('q')) {
            $searchTerm = $request->string('q')->toString();
            $permissionQuery->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('key', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('date_from')) {
            $permissionQuery->whereDate('created_at', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $permissionQuery->whereDate('created_at', '<=', $request->date('date_to')->toDateString());
        }

        $permissions = $permissionQuery->latest()->paginate(10)->withQueryString();

        return view('permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        // Validate the form before creating a new permission.
        $validatedData = $this->validatePermissionData($request);

        // Save the new permission record.
        Permission::create([
            'name' => $validatedData['name'],
            'key' => $validatedData['key'],
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permission Created Successfully');
    }

    public function edit(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        // Validate the form before updating the permission.
        $validatedData = $this->validatePermissionData($request, $permission);

        // Save the updated permission values.
        $permission->update([
            'name' => $validatedData['name'],
            'key' => $validatedData['key'],
        ]);

        return redirect()->route('permissions.index')->with('success', 'Permission Updated Successfully');
    }

    public function destroy(Permission $permission)
    {
        // Delete the selected permission.
        $permission->delete();

        return redirect()->route('permissions.index')
            ->with('success', 'Permissions deleted successfully.');
    }

    private function validatePermissionData(Request $request, ?Permission $permission = null): array
    {
        // Validate each field before saving the permission.
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', Rule::unique('permissions', 'key')->ignore($permission?->id)],
        ]);
    }
}
