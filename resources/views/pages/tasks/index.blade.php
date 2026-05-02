@extends('layouts.app')

@section('title', 'Tasks')
@section('content_class', 'pb-0')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Tasks<span class="badge badge-soft-primary ms-2">{{ $summary['total'] }}</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tasks</li>
                </ol>
            </nav>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add New Task</a>
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <form action="{{ route('tasks.index') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap w-100">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search" value="{{ request('q') }}">
                </div>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'blocked' => 'Blocked'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    @foreach ($taskTypes as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="project_id" class="form-select">
                    <option value="">All Projects</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>
                            {{ $project->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-light shadow" type="submit">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <p class="mb-1">Total Tasks</p>
                        <h5 class="mb-0">{{ $summary['total'] }}</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <p class="mb-1">Completed</p>
                        <h5 class="mb-0">{{ $summary['completed'] }}</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <p class="mb-1">Important</p>
                        <h5 class="mb-0">{{ $summary['important'] }}</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 bg-light">
                        <p class="mb-1">Overdue</p>
                        <h5 class="mb-0">{{ $summary['overdue'] }}</h5>
                    </div>
                </div>
            </div>

            @forelse ($groupedTasks as $label => $tasks)
                <div class="task-wrap {{ $loop->last ? '' : 'border-bottom mb-3' }}">
                    <a href="#" class="d-flex align-items-center justify-content-between mb-3" data-bs-toggle="collapse"
                        data-bs-target="#task_group_{{ $loop->iteration }}">
                        <h6 class="fs-16 mb-0">{{ $label }}</h6>
                        <i class="ti ti-chevron-up arrow-rotate"></i>
                    </a>
                    <div class="collapse show" id="task_group_{{ $loop->iteration }}">
                        @foreach ($tasks as $task)
                            <div class="card rounded-start-0 {{ $loop->last ? 'mb-0' : 'mb-3' }}">
                                <div
                                    class="card-body border-start border-3 border-{{ $task->status === 'completed' ? 'success' : ($task->status === 'blocked' ? 'danger' : 'warning') }}">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                        <div class="d-flex align-items-center flex-wrap row-gap-2">
                                            <span class="me-3"><i class="ti ti-grip-vertical"></i></span>
                                            <div class="form-check form-check-md me-3">
                                                <input class="form-check-input" type="checkbox"
                                                    @checked($task->status === 'completed') disabled>
                                            </div>
                                            <div class="set-star rating-select me-3">
                                                <i class="ti {{ $task->is_important ? 'ti-star-filled' : 'ti-star' }} fs-16"></i>
                                            </div>
                                            <h6
                                                class="fw-semibold mb-0 fs-14 me-3 {{ $task->status === 'completed' ? 'text-decoration-line-through' : '' }}">
                                                {{ $task->title }}</h6>
                                            <span class="badge badge-soft-info border-0 me-2"><i
                                                    class="ti ti-subtask me-1"></i>{{ $taskTypes[$task->type] ?? ucfirst($task->type) }}</span>
                                            @if ($task->auto_repeat)
                                                <span class="badge badge-soft-primary me-2">Auto Repeat</span>
                                            @endif
                                            <span
                                                class="badge badge-soft-warning">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                                        </div>
                                        <div class="d-flex align-items-center flex-wrap row-gap-2">
                                            <div class="me-2">
                                                <span class="badge badge-soft-primary">{{ ucfirst($task->priority) }}</span>
                                            </div>
                                            <div class="me-2">
                                                <span class="badge badge-soft-success">{{ $task->project?->name ?? '-' }}</span>
                                            </div>
                                            <div class="me-2">
                                                <i
                                                    class="ti ti-calendar-exclamation me-1"></i>{{ optional($task->due_date)->format('d M Y') ?? '-' }}
                                            </div>
                                            <div class="avatar avatar-xs avatar-rounded me-2">
                                                <img src="{{ asset($task->employee?->avatar ?: 'assets/img/profiles/avatar-01.jpg') }}"
                                                    alt="img">
                                            </div>
                                            <div class="dropdown table-action">
                                                <a href="#" class="action-icon btn btn-xs shadow btn-icon btn-outline-light"
                                                    data-bs-toggle="dropdown" aria-expanded="false"><i
                                                        class="ti ti-dots-vertical"></i></a>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                        data-bs-target="#edit_task_{{ $task->id }}"><i
                                                            class="ti ti-edit text-blue"></i> Edit</a>
                                                    <button type="button" class="dropdown-item crm-delete-trigger"
                                                        data-bs-toggle="modal" data-bs-target="#crmDeleteModal"
                                                        data-delete-action="{{ route('tasks.destroy', $task) }}"
                                                        data-delete-title="Delete Task"
                                                        data-delete-message="Are you sure you want to delete task '{{ $task->title }}'?">
                                                        <i class="ti ti-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($task->description)
                                        <p class="mt-3 mb-0 text-muted">{{ $task->description }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <h5 class="mb-2">No tasks found</h5>
                    <p class="text-muted mb-3">Create the first work item for your construction workflow.</p>
                    <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvas_add">Add New Task</a>
                </div>
            @endforelse
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_add">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Add New Task</h5><button type="button" class="btn-close"
                data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('tasks.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-12"><label class="form-label">Task Title</label><input type="text" name="title"
                        class="form-control" required></div>
                <div class="col-12"><label class="form-label">Project</label><select name="project_id" class="form-select"
                        required>
                        <option value="">Select</option>@foreach ($projects as $project)<option value="{{ $project->id }}">
                        {{ $project->name }}</option>@endforeach
                    </select></div>
                <div class="col-12"><label class="form-label">Assigned Employee</label><select name="employee_id"
                        class="form-select">
                        <option value="">Select</option>@foreach ($employees as $employee)<option
                        value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach
                    </select></div>
                <div class="col-md-6"><label class="form-label">Task Type</label><select name="type" class="form-select"
                        required>
                        @foreach ($taskTypes as $value => $label)
                            <option value="{{ $value }}" @selected($value === old('type', 'general'))>{{ $label }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-6"><label class="form-label">Priority</label><select name="priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select></div>
                <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="blocked">Blocked</option>
                    </select></div>
                <div class="col-md-6"><label class="form-label">Due Date</label><input type="date" name="due_date"
                        class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Estimated Hours</label><input type="number" step="0.01"
                        name="estimated_hours" class="form-control" value="0"></div>
                <div class="col-md-6"><label class="form-label">Logged Hours</label><input type="number" step="0.01"
                        name="logged_hours" class="form-control" value="0"></div>
                <div class="col-12"><label class="form-label">Description</label><textarea name="description"
                        class="form-control" rows="4"></textarea></div>
                <div class="col-12">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_important" value="1"
                            id="important"><label class="form-check-label" for="important">Mark as important</label></div>
                </div>
                <div class="col-12">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="auto_repeat" value="1"
                            id="auto_repeat"><label class="form-check-label" for="auto_repeat">Enable auto-repeat for daily/weekly tasks</label></div>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2"><button type="button" class="btn btn-light"
                        data-bs-dismiss="offcanvas">Cancel</button><button type="submit" class="btn btn-primary">Save
                        Task</button></div>
            </form>
        </div>
    </div>

    @foreach ($groupedTasks as $tasks)
        @foreach ($tasks as $task)
            <div class="modal fade" id="edit_task_{{ $task->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Task</h5><button type="button" class="btn-close"
                                data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('tasks.update', $task) }}" method="POST" class="row g-3">
                                @csrf
                                @method('PUT')
                                <div class="col-12"><label class="form-label">Task Title</label><input type="text" name="title"
                                        class="form-control" value="{{ $task->title }}" required></div>
                                <div class="col-12"><label class="form-label">Project</label><select name="project_id"
                                        class="form-select" required>@foreach ($projects as $project)<option
                                            value="{{ $project->id }}" @selected($project->id === $task->project_id)>
                                        {{ $project->name }}</option>@endforeach</select></div>
                                <div class="col-12"><label class="form-label">Assigned Employee</label><select name="employee_id"
                                        class="form-select">
                                        <option value="">Select</option>@foreach ($employees as $employee)<option
                                            value="{{ $employee->id }}" @selected($employee->id === $task->employee_id)>
                                        {{ $employee->name }}</option>@endforeach
                                    </select></div>
                                <div class="col-md-6"><label class="form-label">Task Type</label><select name="type"
                                        class="form-select" required>
                                        @foreach ($taskTypes as $value => $label)
                                            <option value="{{ $value }}" @selected($value === $task->type)>{{ $label }}</option>
                                        @endforeach
                                    </select></div>
                                <div class="col-md-6"><label class="form-label">Priority</label><select name="priority"
                                        class="form-select">@foreach (['low', 'medium', 'high'] as $priority)<option
                                            value="{{ $priority }}" @selected($priority === $task->priority)>{{ ucfirst($priority) }}
                                        </option>@endforeach</select></div>
                                <div class="col-md-6"><label class="form-label">Status</label><select name="status"
                                        class="form-select">@foreach (['pending', 'in_progress', 'completed', 'blocked'] as $status)
                                            <option value="{{ $status }}" @selected($status === $task->status)>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
                                <div class="col-md-6"><label class="form-label">Due Date</label><input type="date" name="due_date"
                                        class="form-control" value="{{ optional($task->due_date)->format('Y-m-d') }}"></div>
                                <div class="col-md-6"><label class="form-label">Estimated Hours</label><input type="number"
                                        step="0.01" name="estimated_hours" class="form-control"
                                        value="{{ $task->estimated_hours }}"></div>
                                <div class="col-md-6"><label class="form-label">Logged Hours</label><input type="number" step="0.01"
                                        name="logged_hours" class="form-control" value="{{ $task->logged_hours }}"></div>
                                <div class="col-12"><label class="form-label">Description</label><textarea name="description"
                                        class="form-control" rows="4">{{ $task->description }}</textarea></div>
                                <div class="col-12">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="is_important"
                                            value="1" id="important_{{ $task->id }}" @checked($task->is_important)><label
                                            class="form-check-label" for="important_{{ $task->id }}">Mark as important</label></div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="auto_repeat"
                                            value="1" id="auto_repeat_{{ $task->id }}" @checked($task->auto_repeat)><label
                                            class="form-check-label" for="auto_repeat_{{ $task->id }}">Enable auto-repeat for daily/weekly tasks</label></div>
                                </div>
                                <div class="col-12 d-flex justify-content-end gap-2"><button type="button" class="btn btn-light"
                                        data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update
                                        Task</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endforeach
@endsection
