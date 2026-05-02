<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        // Load roles with their permission counts for the listing page.
        $roles = Role::withCount('permissions')->latest()->paginate(10);

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        // Load all permissions so they can be assigned to the new role.
        $permissions = Permission::all();

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        // Validate the role form before creating a new record.
        $validatedData = $this->validateRoleData($request);

        // Create the role and attach the selected permissions.
        $role = Role::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
        ]);
        $role->permissions()->sync($validatedData['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role Created Successfully');
    }

    public function edit(Role $role)
    {
        // Load all permissions and the role's current selections.
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        // Validate the role form before updating the existing record.
        $validatedData = $this->validateRoleData($request, $role);

        // Update the role details and sync the selected permissions.
        $role->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
        ]);
        $role->permissions()->sync($validatedData['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role Updated Successfully');
    }

    public function destroy(Role $role)
    {
        // Remove permission links first, then delete the role.
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    private function validateRoleData(Request $request, ?Role $role = null): array
    {
        // Validate each field before creating or updating a role.
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);
    }
}
