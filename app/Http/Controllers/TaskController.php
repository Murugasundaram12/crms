<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private const TASK_TYPES = [
        'general' => 'General Task',
        'daily' => 'Daily Task',
        'weekly' => 'Weekly Task',
    ];

    public function index(Request $request)
    {
        // Build the task query with optional search and filters.
        $taskQuery = Task::with(['project', 'employee']);
        $this->applySearchFilter($taskQuery, $request);
        $this->applyListFilters($taskQuery, $request);
        $this->applyDateFilter($taskQuery, $request);

        // Load the tasks in the same order used by the board view.
        $tasks = $taskQuery
            ->orderByRaw('COALESCE(due_date, CURRENT_DATE) desc')
            ->orderByDesc('is_important')
            ->orderBy('sort_order')
            ->get();

        // Group tasks by their due date label for the page sections.
        $groupedTasks = $tasks->groupBy(fn(Task $task) => $this->getDueDateLabel($task));

        // Load dropdown data and summary counters used by the page.
        $projects = Project::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();
        $summary = $this->buildTaskSummary($tasks);
        $taskTypes = self::TASK_TYPES;

        return view('pages.tasks.index', compact('groupedTasks', 'projects', 'employees', 'summary', 'taskTypes'));
    }

    public function store(Request $request)
    {
        // Validate the form before creating the task.
        $validatedData = $this->validateTaskData($request);

        // Save the new task.
        $task = Task::create($validatedData);

        $this->createNextRecurringTaskIfNeeded($task);

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function create()
    {
        return redirect()->route('tasks.index');
    }

    public function show(Task $task)
    {
        return redirect()->route('tasks.index', ['highlight' => $task->id]);
    }

    public function edit(Task $task)
    {
        // Reuse the board page and open the selected task for editing.
        return redirect()->route('tasks.index', ['edit' => $task->id]);
    }

    public function update(Request $request, Task $task)
    {
        // Validate the form before updating the task.
        $validatedData = $this->validateTaskData($request);

        // Save the updated task.
        $task->update($validatedData);

        $this->createNextRecurringTaskIfNeeded($task->fresh());

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        // Delete the selected task.
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }

    private function applySearchFilter($taskQuery, Request $request): void
    {
        $searchTerm = $request->string('q')->toString();

        if ($searchTerm === '') {
            return;
        }

        $taskQuery->where(function ($queryBuilder) use ($searchTerm) {
            $queryBuilder
                ->where('title', 'like', "%{$searchTerm}%")
                ->orWhere('type', 'like', "%{$searchTerm}%")
                ->orWhereHas('project', function ($projectQuery) use ($searchTerm) {
                    $projectQuery->where('name', 'like', "%{$searchTerm}%");
                })
                ->orWhereHas('employee', function ($employeeQuery) use ($searchTerm) {
                    $employeeQuery->where('name', 'like', "%{$searchTerm}%");
                });
        });
    }

    private function applyListFilters($taskQuery, Request $request): void
    {
        foreach (['status', 'priority', 'project_id', 'employee_id', 'type'] as $filterName) {
            $filterValue = $request->input($filterName);

            if ($filterValue) {
                $taskQuery->where($filterName, $filterValue);
            }
        }
    }

    private function applyDateFilter($taskQuery, Request $request): void
    {
        if ($request->filled('date_from')) {
            $taskQuery->whereDate('due_date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $taskQuery->whereDate('due_date', '<=', $request->date('date_to')->toDateString());
        }
    }

    private function getDueDateLabel(Task $task): string
    {
        if (! $task->due_date) {
            return 'No Due Date';
        }

        if ($task->due_date->isToday()) {
            return 'Today';
        }

        if ($task->due_date->isYesterday()) {
            return 'Yesterday';
        }

        return $task->due_date->format('d M Y');
    }

    private function buildTaskSummary($tasks): array
    {
        return [
            'total' => $tasks->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'important' => $tasks->where('is_important', true)->count(),
            'overdue' => $tasks->filter(function (Task $task) {
                return $task->due_date
                    && $task->due_date->lt(Carbon::today())
                    && $task->status !== 'completed';
            })->count(),
        ];
    }

    private function validateTaskData(Request $request): array
    {
        // Validate each field before saving the task.
        $validatedData = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(array_keys(self::TASK_TYPES))],
            'auto_repeat' => ['nullable', 'boolean'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'blocked'])],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'logged_hours' => ['nullable', 'numeric', 'min:0'],
            'is_important' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Normalize checkbox and completion values before saving.
        $validatedData['is_important'] = $request->boolean('is_important');
        $validatedData['auto_repeat'] = $request->boolean('auto_repeat');
        $validatedData['completed_at'] = $validatedData['status'] === 'completed' ? now() : null;

        return $validatedData;
    }

    private function createNextRecurringTaskIfNeeded(Task $task): void
    {
        if (! $task->auto_repeat || ! in_array($task->type, ['daily', 'weekly'], true) || $task->status !== 'completed') {
            return;
        }

        if (! $task->due_date) {
            return;
        }

        $nextDueDate = match ($task->type) {
            'daily' => $task->due_date->copy()->addDay(),
            'weekly' => $task->due_date->copy()->addWeek(),
            default => null,
        };

        if (! $nextDueDate) {
            return;
        }

        $alreadyExists = Task::query()
            ->where('recurring_source_id', $task->id)
            ->whereDate('due_date', $nextDueDate)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        Task::create([
            'project_id' => $task->project_id,
            'employee_id' => $task->employee_id,
            'title' => $task->title,
            'description' => $task->description,
            'type' => $task->type,
            'auto_repeat' => true,
            'recurring_source_id' => $task->id,
            'priority' => $task->priority,
            'status' => 'pending',
            'due_date' => $nextDueDate,
            'completed_at' => null,
            'estimated_hours' => $task->estimated_hours,
            'logged_hours' => 0,
            'is_important' => $task->is_important,
            'sort_order' => $task->sort_order,
        ]);
    }
}
