<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        // Build the employee list query and apply optional filters.
        $employeeQuery = User::query()->with('roles');
        $this->applySearchFilter($employeeQuery, $request);
        $this->applyStatusFilter($employeeQuery, $request);
        $this->applyDateFilter($employeeQuery, $request);

        // Load employees and available roles for the page.
        $users = $employeeQuery->latest()->paginate(10)->withQueryString();
        $roles = Role::all();

        return view('pages.employees.index', compact('users', 'roles'));
    }

    public function create()
    {
        return redirect()->route('employees.index');
    }

    public function store(Request $request)
    {
        // Validate the form before creating the employee account.
        $validatedData = $this->validateEmployeeData($request);
        $selectedRole = Role::where('name', $validatedData['role'])->firstOrFail();
        $validatedData = $this->handleAvatarUpload($request, $validatedData);

        // Create the employee record and attach the selected role.
        $employee = User::create($validatedData);
        $employee->roles()->sync([$selectedRole->id]);

        return redirect()->route('employees.index')->with('success', 'User created successfully.');
    }

    public function show(User $employee)
    {
        $employee->load('roles');

        $expenses = Expense::query()
            ->with(['project', 'mainCategory', 'category'])
            ->where('user_id', $employee->id)
            ->latest('current_date')
            ->paginate(10, ['*'], 'expenses_page')
            ->withQueryString();

        $attendances = Attendance::query()
            ->where('user_id', $employee->id)
            ->latest('attendance_date')
            ->paginate(10, ['*'], 'attendance_page')
            ->withQueryString();

        $workedMinutes = (int) Attendance::query()
            ->where('user_id', $employee->id)
            ->where('attendance_date', '>=', now()->subDays(30)->toDateString())
            ->sum('worked_minutes');

        $stats = [
            'expense_total' => (float) Expense::query()->where('user_id', $employee->id)->sum('amount'),
            'paid_total' => (float) Expense::query()->where('user_id', $employee->id)->sum('paid_amt'),
            'unpaid_total' => (float) Expense::query()->where('user_id', $employee->id)->sum('unpaid_amt'),
            'worked_hours' => intdiv($workedMinutes, 60),
            'worked_minutes' => $workedMinutes % 60,
        ];

        return view('pages.employees.show', compact('employee', 'expenses', 'attendances', 'stats'));
    }

    public function edit(User $employee)
    {
        // Reuse the main listing page and open the selected employee for editing.
        return redirect()->route('employees.index', ['edit' => $employee->id]);
    }

    public function update(Request $request, User $employee)
    {
        // Validate the form before updating the employee account.
        $validatedData = $this->validateEmployeeData($request, $employee);
        $selectedRole = Role::where('name', $validatedData['role'])->firstOrFail();
        $validatedData = $this->handleAvatarUpload($request, $validatedData);

        // Save the updated employee values and sync the selected role.
        $employee->update($validatedData);
        $employee->roles()->sync([$selectedRole->id]);

        return redirect()->route('employees.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'User deleted successfully.');
    }

    private function applySearchFilter($employeeQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $employeeQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%")
                ->orWhere('phone', 'like', "%{$searchTerm}%")
                ->orWhere('designation', 'like', "%{$searchTerm}%");
        });
    }

    private function applyStatusFilter($employeeQuery, Request $request): void
    {
        $status = $request->string('status')->toString();

        if ($status === '') {
            return;
        }

        $employeeQuery->where('status', $status);
    }

    private function applyDateFilter($employeeQuery, Request $request): void
    {
        if ($request->filled('date_from')) {
            $employeeQuery->whereDate('created_at', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $employeeQuery->whereDate('created_at', '<=', $request->date('date_to')->toDateString());
        }
    }

    private function validateEmployeeData(Request $request, ?User $employee = null): array
    {
        // Validate each field before saving the employee record.
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee?->id),
            ],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'phone' => ['nullable', 'string', 'max:30'],
            'designation' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
        ]);


        // Do not overwrite the password when the edit form leaves it empty.
        if (blank($validatedData['password'] ?? null)) {
            unset($validatedData['password']);
        }

        return $validatedData;
    }

    private function handleAvatarUpload(Request $request, array $validatedData): array
    {
        if (! $request->hasFile('avatar')) {
            unset($validatedData['avatar']);

            return $validatedData;
        }

        $file = $request->file('avatar');
        $fileName = now()->format('YmdHis') . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $destination = public_path('images');

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $fileName);
        $validatedData['avatar'] = 'images/' . $fileName;

        return $validatedData;
    }
}
