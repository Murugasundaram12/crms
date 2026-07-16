<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Attendance;
use App\Models\Category;
use App\Models\Employee;
use App\Models\EmployeeDevice;
use App\Models\Expense;
use App\Models\Labour;
use App\Models\LabourRole;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LocationTracking;
use App\Models\MainCategory;
use App\Models\MobileApiToken;
use App\Models\Client;
use App\Models\Payment;
use App\Models\PaymentStage;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Services\CrmBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait MobileClientProjectEndpoints
{
    public function clients(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'clients-list')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['enquiry', 'active', 'inactive'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $clients = $this->scopeClientsForAppUser(Client::query(), $request->user())
            ->withCount(['projects', 'payments'])
            ->when($validated['q'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($validated['status'] ?? null, fn($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 15));

        $clients->setCollection($clients->getCollection()->map(fn(Client $client) => $this->clientPayload($client)));

        return response()->json($clients);
    }

    public function storeClient(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'clients-create')) {
            return $forbidden;
        }

        $client = Client::query()->create($this->validateClientData($request));

        return response()->json([
            'message' => 'Client created successfully.',
            'client' => $this->clientPayload($client),
        ], 201);
    }

    public function showClient(Request $request, Client $client)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'clients-list')) {
            return $forbidden;
        }

        if (! $this->canAccessClient($request->user(), $client)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(['client' => $this->clientPayload($client->loadCount(['projects', 'payments']))]);
    }

    public function updateClient(Request $request, Client $client)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'clients-edit')) {
            return $forbidden;
        }

        $client->update($this->validateClientData($request, $client));

        return response()->json([
            'message' => 'Client updated successfully.',
            'client' => $this->clientPayload($client->fresh()),
        ]);
    }

    public function deleteClient(Request $request, Client $client)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'clients-delete')) {
            return $forbidden;
        }

        if ($client->projects()->exists() || $client->payments()->exists()) {
            return response()->json(['message' => 'Client has related projects or payments. Mark it inactive instead.'], 409);
        }

        $client->delete();

        return response()->json(['message' => 'Client deleted successfully.']);
    }

    public function projectOptions(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'projects-list')) {
            return $forbidden;
        }

        return response()->json([
            'clients' => $this->scopeClientsForAppUser(Client::query(), $request->user())->orderBy('name')->get(['id', 'name', 'status']),
            'employees' => Employee::query()
                ->when(! $this->canViewAllAppData($request->user()), function ($query) use ($request) {
                    $taskEmployeeId = $this->taskEmployeeIdFromUserId($request->user()->id);

                    $taskEmployeeId ? $query->whereKey($taskEmployeeId) : $query->whereRaw('1 = 0');
                })
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'statuses' => ['planning', 'active', 'on_hold', 'completed', 'cancelled'],
            'priorities' => ['low', 'medium', 'high'],
        ]);
    }

    public function projects(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'projects-list')) {
            return $forbidden;
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['planning', 'active', 'on_hold', 'completed', 'cancelled'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'client_id' => ['nullable', 'exists:clients,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $projects = $this->scopeProjectsForAppUser(Project::query(), $request->user())
            ->with(['client', 'manager'])
            ->withCount('tasks')
            ->when($validated['q'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('project_code', 'like', "%{$search}%")
                        ->orWhereHas('client', fn($clientQuery) => $clientQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($validated['status'] ?? null, fn($query, string $status) => $query->where('status', $status))
            ->when($validated['priority'] ?? null, fn($query, string $priority) => $query->where('priority', $priority))
            ->when($validated['client_id'] ?? null, fn($query, int $clientId) => $query->where('client_id', $clientId))
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 15));

        $projects->setCollection($projects->getCollection()->map(fn(Project $project) => $this->projectPayload($project)));

        return response()->json($projects);
    }

    public function storeProject(Request $request)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'projects-create')) {
            return $forbidden;
        }

        $validated = $this->validateProjectData($request);
        $validated['progress'] = $validated['progress'] ?? 0;
        $project = Project::query()->create($validated);

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => $this->projectPayload($project->load(['client', 'manager'])),
        ], 201);
    }

    public function showProject(Request $request, Project $project)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'projects-list')) {
            return $forbidden;
        }

        if (! $this->canAccessProject($request->user(), $project)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $project->load(['client', 'manager', 'tasks.employee', 'payments.stage', 'expenses.mainCategory', 'expenses.category']);

        return response()->json(['project' => $this->projectPayload($project, true)]);
    }

    public function updateProject(Request $request, Project $project)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'projects-edit')) {
            return $forbidden;
        }

        $project->update($this->validateProjectData($request, $project));

        return response()->json([
            'message' => 'Project updated successfully.',
            'project' => $this->projectPayload($project->fresh(['client', 'manager'])),
        ]);
    }

    public function deleteProject(Request $request, Project $project)
    {
        if ($forbidden = $this->authorizeApiPermission($request, 'projects-delete')) {
            return $forbidden;
        }

        if ($project->tasks()->exists() || $project->payments()->exists() || $project->expenses()->exists()) {
            return response()->json(['message' => 'Project has related records. Mark it cancelled/completed instead.'], 409);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully.']);
    }
}

