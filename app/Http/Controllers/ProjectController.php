<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        // Build the project listing query with search and filters.
        $projectQuery = Project::with(['client', 'manager'])->withCount('tasks');
        $this->applySearchFilter($projectQuery, $request);
        $this->applyListFilters($projectQuery, $request);

        // Load the paginated project list.
        $projects = $projectQuery->latest()->paginate(12)->withQueryString();

        // Load dropdown data used by the page filters and forms.
        $clients = Client::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('pages.projects.index', compact('projects', 'clients', 'employees'));
    }

    public function store(Request $request)
    {
        // Validate the submitted form before creating the project.
        $validatedData = $this->validateProjectData($request);

        // Save the new project.
        Project::create($validatedData);

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        // Load the full project details page relationships.
        $this->loadProjectDetails($project);

        // Load employees available for task or manager assignment.
        $availableEmployees = Employee::orderBy('name')->get();

        return view('pages.projects.show', compact('project', 'availableEmployees'));
    }

    public function edit(Project $project)
    {
        return redirect()->route('projects.index', ['edit' => $project->id]);
    }

    public function update(Request $request, Project $project)
    {
        // Validate the form data before updating the project.
        $validatedData = $this->validateProjectData($request, $project);

        // Update the selected project.
        $project->update($validatedData);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        // Delete the selected project.
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }

    public function finalBill(Project $project)
    {
        // Start with the latest quotation total for the project.
        $latestQuotation = $project->quotations()->latest('created_at')->first();
        $quotationTotal = $latestQuotation?->total_amount ?? 0.0;

        // Add approved additions and subtract approved deductions.
        $approvedVariationTotal = $this->calculateApprovedVariationTotal($project);

        // Subtract only the payments that were actually paid.
        $paidPaymentsTotal = $project->payments()
            ->where('status', 'paid')
            ->sum('amount');

        $finalBill = $quotationTotal + $approvedVariationTotal - $paidPaymentsTotal;

        return response()->json([
            'final_bill' => (float) $finalBill,
            'quotation_total' => (float) $quotationTotal,
            'variations_net' => (float) $approvedVariationTotal,
            'payments_sum' => (float) $paidPaymentsTotal,
        ]);
    }

    private function applySearchFilter($projectQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $projectQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('project_code', 'like', "%{$searchTerm}%")
                ->orWhere('type', 'like', "%{$searchTerm}%")
                ->orWhereHas('client', function ($clientQuery) use ($searchTerm) {
                    $clientQuery->where('name', 'like', "%{$searchTerm}%");
                });
        });
    }

    private function applyListFilters($projectQuery, Request $request): void
    {
        foreach (['status', 'priority', 'client_id'] as $filterName) {
            $filterValue = $request->input($filterName);

            if ($filterValue) {
                $projectQuery->where($filterName, $filterValue);
            }
        }
    }

    private function loadProjectDetails(Project $project): void
    {
        $project->load([
            'client',
            'manager',
            'tasks.employee',
            'payments.client',
            'payments.stage',
            'expenses.employee',
            'quotations.items',
            'paymentStages.payments',
            'variations',
        ]);

        $project->loadCount([
            'tasks',
            'tasks as completed_tasks_count' => function ($query) {
                $query->where('status', 'completed');
            },
            'paymentStages as payment_stages_count' => function ($query) {
                $query->selectRaw('COUNT(DISTINCT payment_stages.id)');
            },
            'variations',
        ]);
    }

    private function calculateApprovedVariationTotal(Project $project): float
    {
        return (float) $project->variations()
            ->where('status', 'approved')
            ->get()
            ->sum(function ($variation) {
                return $variation->type === 'additional'
                    ? $variation->amount
                    : -$variation->amount;
            });
    }

    private function validateProjectData(Request $request, ?Project $project = null): array
    {
        // Validate each form field before creating or updating the project.
        return $request->validate([
            'project_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('projects', 'project_code')->ignore($project?->id),
            ],
            'client_id' => ['required', 'exists:clients,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['required', Rule::in(['planning', 'active', 'on_hold', 'completed', 'cancelled'])],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'url', 'max:500'],
        ]);
    }
}
