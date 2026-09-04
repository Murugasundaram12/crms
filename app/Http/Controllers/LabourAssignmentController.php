<?php

namespace App\Http\Controllers;

use App\Models\Labour;
use App\Models\LabourAssignment;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LabourAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = LabourAssignment::query()->with(['labour', 'project', 'employee']);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('labour_id')) {
            $query->where('labour_id', $request->integer('labour_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('date')) {
            $date = Carbon::parse($request->string('date')->toString())->toDateString();
            $query->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date);
        }

        $assignments = $query->latest('start_date')->latest()->paginate(15)->withQueryString();
        $labours = Labour::query()->orderBy('name')->get();
        $projects = Project::query()->orderBy('name')->get();
        $selectedDate = $request->input('date');

        return view('pages.labour_assignments.index', compact('assignments', 'labours', 'projects', 'selectedDate'));
    }

    public function edit(LabourAssignment $labourAssignment): View
    {
        $assignment = $labourAssignment;
        $labours = Labour::query()->orderBy('name')->get();
        $projects = Project::query()->orderBy('name')->get();

        return view('pages.labour_assignments.edit', compact('assignment', 'labours', 'projects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $startDate = Carbon::parse($validated['start_date'])->toDateString();
        $endDate = Carbon::parse($validated['end_date'])->toDateString();

        if ($validated['status'] === 'active' && LabourAssignment::hasOverlappingAssignment((int) $validated['labour_id'], $startDate, $endDate)) {
            throw ValidationException::withMessages([
                'labour_id' => 'The selected labour already has an active project assignment overlapping with this date range (' . $startDate . ' to ' . $endDate . ').',
            ]);
        }

        LabourAssignment::create([
            'labour_id' => $validated['labour_id'],
            'project_id' => $validated['project_id'],
            'employee_id' => Auth::id(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('labour_assignments.index')->with('success', 'Labour project assignment created successfully.');
    }

    public function update(Request $request, LabourAssignment $labourAssignment): RedirectResponse
    {
        $validated = $request->validate([
            'labour_id' => ['required', 'exists:labours,id'],
            'project_id' => ['required', 'exists:projects,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $startDate = Carbon::parse($validated['start_date'])->toDateString();
        $endDate = Carbon::parse($validated['end_date'])->toDateString();

        if ($validated['status'] === 'active' && LabourAssignment::hasOverlappingAssignment((int) $validated['labour_id'], $startDate, $endDate, $labourAssignment->id)) {
            throw ValidationException::withMessages([
                'labour_id' => 'The selected labour already has another active project assignment overlapping with this date range.',
            ]);
        }

        $labourAssignment->update([
            'labour_id' => $validated['labour_id'],
            'project_id' => $validated['project_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('labour_assignments.index')->with('success', 'Labour assignment updated successfully.');
    }

    public function destroy(LabourAssignment $labourAssignment): RedirectResponse
    {
        $labourAssignment->delete();

        return redirect()->route('labour_assignments.index')->with('success', 'Labour assignment deleted successfully.');
    }
}
