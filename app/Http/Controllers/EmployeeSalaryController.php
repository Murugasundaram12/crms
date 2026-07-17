<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSalary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeSalaryController extends Controller
{
    public function index(Request $request)
    {
        $employeeSalaryQuery = EmployeeSalary::query();
        $this->applySearchFilter($employeeSalaryQuery, $request);
        $this->applySalaryTypeFilter($employeeSalaryQuery, $request);
        $this->applyDateFilter($employeeSalaryQuery, $request);

        $employeeSalaries = $employeeSalaryQuery->latest()->paginate(10)->withQueryString();

        return view('pages.employee_salaries.index', compact('employeeSalaries'));
    }

    public function create()
    {
        $employeeUsers = $this->getEligibleEmployeeUsers();

        return view('pages.employee_salaries.create', compact('employeeUsers'));
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateEmployeeSalaryData($request);

        EmployeeSalary::create($validatedData);

        return redirect()->route('employee-salaries.index')->with('success', 'Employee salary created successfully.');
    }

    public function edit(EmployeeSalary $employeeSalary)
    {
        $employeeUsers = $this->getEligibleEmployeeUsers();

        return view('pages.employee_salaries.edit', compact('employeeSalary', 'employeeUsers'));
    }

    public function update(Request $request, EmployeeSalary $employeeSalary)
    {
        $validatedData = $this->validateEmployeeSalaryData($request, $employeeSalary);
        $employeeSalary->update($validatedData);

        return redirect()->route('employee-salaries.index')->with('success', 'Employee salary updated successfully.');
    }

    public function destroy(EmployeeSalary $employeeSalary)
    {
        $employeeSalary->delete();

        return redirect()->route('employee-salaries.index')->with('success', 'Employee salary deleted successfully.');
    }

    private function applySearchFilter($employeeSalaryQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $employeeSalaryQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('salary_type', 'like', "%{$searchTerm}%")
                ->orWhere('salary', 'like', "%{$searchTerm}%");
        });
    }

    private function applySalaryTypeFilter($employeeSalaryQuery, Request $request): void
    {
        $salaryType = $request->string('salary_type')->toString();

        if ($salaryType === '') {
            return;
        }

        $employeeSalaryQuery->where('salary_type', $salaryType);
    }

    private function applyDateFilter($employeeSalaryQuery, Request $request): void
    {
        if ($request->filled('date_from')) {
            $employeeSalaryQuery->whereDate('created_at', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $employeeSalaryQuery->whereDate('created_at', '<=', $request->date('date_to')->toDateString());
        }
    }

    private function validateEmployeeSalaryData(Request $request, ?EmployeeSalary $employeeSalary = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_salaries', 'name')->ignore($employeeSalary?->id),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $userExists = User::query()
                        ->where('name', $value)
                        ->where(function ($queryBuilder) {
                            $queryBuilder->whereNull('status')->orWhere('status', 'active');
                        })
                        ->exists();

                    if (! $userExists) {
                        $fail('Please select a valid active user.');
                    }
                },
            ],
            'salary' => ['required', 'numeric', 'min:0.01'],
            'salary_type' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
        ]);
    }

    private function getEligibleEmployeeUsers()
    {
        return User::query()
            ->with('roles')
            ->where(function ($queryBuilder) {
                $queryBuilder->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();
    }
}
