<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ToolMaterial;
use App\Models\ToolMaterialAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ToolMaterialAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = ToolMaterialAssignment::query()
            ->with(['toolMaterial', 'fromProject', 'toProject']);

        if ($request->filled('tool_material_id')) {
            $query->where('tool_material_id', $request->integer('tool_material_id'));
        }

        if ($request->filled('project_id')) {
            $projectId = $request->integer('project_id');
            $query->where(function ($projectQuery) use ($projectId) {
                $projectQuery
                    ->where('from_project_id', $projectId)
                    ->orWhere('to_project_id', $projectId);
            });
        }

        if ($request->filled('transfer_type')) {
            $query->where('transfer_type', $request->string('transfer_type')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transferred_at', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transferred_at', '<=', $request->date('date_to')->toDateString());
        }

        $assignments = $query
            ->latest('transferred_at')
            ->paginate((int) $request->input('paginate', 10));

        return view('pages.tool_material_assignments.index', [
            'assignments' => $assignments,
            'toolsMaterials' => $this->toolsMaterials(),
            'projects' => $this->projects(),
        ]);
    }

    public function create(): View
    {
        return view('pages.tool_material_assignments.create', [
            'toolsMaterials' => $this->toolsMaterials(),
            'projects' => $this->projects(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ToolMaterialAssignment::query()->create($this->validateAssignment($request));

        return redirect()->route('tools-material-assignments.index')->with('success', 'Tool / material transfer saved successfully.');
    }

    public function edit(ToolMaterialAssignment $toolsMaterialAssignment): View
    {
        return view('pages.tool_material_assignments.edit', [
            'assignment' => $toolsMaterialAssignment,
            'toolsMaterials' => $this->toolsMaterials(),
            'projects' => $this->projects(),
        ]);
    }

    public function update(Request $request, ToolMaterialAssignment $toolsMaterialAssignment): RedirectResponse
    {
        $toolsMaterialAssignment->update($this->validateAssignment($request));

        return redirect()->route('tools-material-assignments.index')->with('success', 'Tool / material transfer updated successfully.');
    }

    public function destroy(ToolMaterialAssignment $toolsMaterialAssignment): RedirectResponse
    {
        $toolsMaterialAssignment->delete();

        return redirect()->route('tools-material-assignments.index')->with('success', 'Tool / material transfer deleted successfully.');
    }

    private function validateAssignment(Request $request): array
    {
        $validated = $request->validate([
            'tool_material_id' => ['required', 'exists:tools_materials,id'],
            'from_project_id' => ['required', 'exists:projects,id'],
            'to_project_id' => ['nullable', 'exists:projects,id'],
            'transfer_type' => ['required', Rule::in(['site_to_office', 'site_to_site'])],
            'transferred_at' => ['required', 'date'],
        ], [], [
            'tool_material_id' => 'tool name',
            'from_project_id' => 'site name',
            'to_project_id' => 'to site',
            'transferred_at' => 'date and time',
        ]);

        if ($validated['transfer_type'] === 'site_to_site') {
            if (empty($validated['to_project_id'])) {
                throw ValidationException::withMessages([
                    'to_project_id' => 'To site is required for site to site transfer.',
                ]);
            }

            if ((int) $validated['from_project_id'] === (int) $validated['to_project_id']) {
                throw ValidationException::withMessages([
                    'to_project_id' => 'To site must be different from site name.',
                ]);
            }
        }

        if ($validated['transfer_type'] === 'site_to_office') {
            $validated['to_project_id'] = null;
        }

        return $validated;
    }

    private function toolsMaterials()
    {
        return ToolMaterial::query()->orderBy('name')->get(['id', 'name']);
    }

    private function projects()
    {
        return Project::query()->orderBy('name')->get(['id', 'name', 'project_code']);
    }
}
