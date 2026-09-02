@extends('layouts.app')

@section('title', 'Labour Attendance')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Labour Attendance</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Attendance</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Labour Attendance</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary shadow-sm">Employee Attendance</a>
            <a href="{{ route('labour-salaries.index') }}" class="btn btn-primary shadow-sm"><i class="ti ti-cash me-1"></i> Labour Salaries</a>
        </div>
    </div>

    @if($isSunday)
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
            <i class="ti ti-calendar-off fs-4"></i>
            <div>
                <strong>Sunday Weekly Off:</strong> Selected date ({{ $selectedDate }}) is a Sunday. Sundays are weekly off days and are excluded from normal working day counts and attendance marking.
            </div>
        </div>
    @endif

    @if($summary)
        <div class="card border-0 shadow-sm mb-4 bg-light">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-user-check text-primary me-2"></i>Monthly Attendance Summary: {{ $summary['labour_name'] }} ({{ $summary['month'] }})
                    </h5>
                    <a href="{{ route('labour-salaries.create', ['labour_id' => $summary['labour_id']]) }}" class="btn btn-sm btn-success">
                        <i class="ti ti-cash me-1"></i> Process Salary for {{ $summary['labour_name'] }}
                    </a>
                </div>
                <div class="row g-3">
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="p-3 bg-white rounded border text-center">
                            <small class="text-muted d-block mb-1">Mon-Sat Working Days</small>
                            <span class="h5 mb-0 text-dark fw-bold">{{ $summary['total_working_days'] }}</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="p-3 bg-white rounded border text-center">
                            <small class="text-muted d-block mb-1">Present Days</small>
                            <span class="h5 mb-0 text-success fw-bold">{{ $summary['present_days'] }}</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="p-3 bg-white rounded border text-center">
                            <small class="text-muted d-block mb-1">Half Days</small>
                            <span class="h5 mb-0 text-warning fw-bold">{{ $summary['half_days'] }}</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="p-3 bg-white rounded border text-center">
                            <small class="text-muted d-block mb-1">Absent Days</small>
                            <span class="h5 mb-0 text-danger fw-bold">{{ $summary['absent_days'] }}</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="p-3 bg-white rounded border text-center">
                            <small class="text-muted d-block mb-1">Payable Days</small>
                            <span class="h5 mb-0 text-primary fw-bold">{{ $summary['payable_days'] }}</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="p-3 bg-white rounded border text-center">
                            <small class="text-muted d-block mb-1">Est. Calculated Salary</small>
                            <span class="h5 mb-0 text-success fw-bold">₹{{ number_format($summary['calculated_salary'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0">Filter & Attendance Entry</h5>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('labour-attendances.index') }}" class="row g-3 align-items-end mb-4">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label">Labour</label>
                    <select name="labour_id" class="form-select">
                        <option value="">All Labours</option>
                        @foreach($labours as $labour)
                            <option value="{{ $labour->id }}" @selected((string)$selectedLabourId === (string)$labour->id)>
                                {{ $labour->name }} (₹{{ number_format($labour->salary, 2) }}/mo)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 col-lg-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
                </div>
                <div class="col-12 col-md-3 col-lg-3">
                    <label class="form-label">Month (For Summary)</label>
                    <input type="month" name="month" class="form-control" value="{{ $selectedMonth }}">
                </div>
                <div class="col-12 col-md-2 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i> Filter</button>
                    <a href="{{ route('labour-attendances.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>

            @if(!$isSunday)
                <div class="border-top pt-4">
                    <h6 class="fw-bold mb-3"><i class="ti ti-edit me-1 text-primary"></i> Mark Attendance for {{ $selectedDate }}</h6>
                    <form method="POST" action="{{ route('labour-attendances.bulk-store') }}">
                        @csrf
                        <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">
                        <div class="table-responsive">
                            <table class="table table-bordered table-nowrap align-middle mb-3">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 35%;">Labour Name</th>
                                        <th style="width: 45%;">Attendance Status</th>
                                        <th style="width: 20%;">Notes (Optional)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php($dateAttendances = $attendances->where('attendance_date', \Carbon\Carbon::parse($selectedDate))->keyBy('labour_id'))
                                    @foreach($labours as $index => $labour)
                                        @php($existingRecord = $dateAttendances->get($labour->id))
                                        @php($currentStatus = $existingRecord ? $existingRecord->status : 'off')
                                        <tr>
                                            <td>
                                                <input type="hidden" name="attendances[{{ $index }}][labour_id]" value="{{ $labour->id }}">
                                                <div class="fw-bold text-dark">{{ $labour->name }}</div>
                                                <small class="text-muted">{{ $labour->job_title ?? 'Labour' }} | Advance: ₹{{ number_format($labour->advance_amt, 2) }}</small>
                                            </td>
                                            <td>
                                                <input type="hidden" name="attendances[{{ $index }}][status]" value="off">
                                                <div class="btn-group w-100" role="group" aria-label="Status for {{ $labour->name }}">
                                                    <input type="radio" class="btn-check" name="attendances[{{ $index }}][status]" id="status_present_{{ $labour->id }}" value="present" @checked($currentStatus === 'present')>
                                                    <label class="btn btn-outline-success" for="status_present_{{ $labour->id }}"><i class="ti ti-check me-1"></i> Present</label>

                                                    <input type="radio" class="btn-check" name="attendances[{{ $index }}][status]" id="status_half_{{ $labour->id }}" value="half_day" @checked($currentStatus === 'half_day')>
                                                    <label class="btn btn-outline-warning" for="status_half_{{ $labour->id }}"><i class="ti ti-clock me-1"></i> Half Day</label>

                                                    <input type="radio" class="btn-check" name="attendances[{{ $index }}][status]" id="status_absent_{{ $labour->id }}" value="absent" @checked($currentStatus === 'absent')>
                                                    <label class="btn btn-outline-danger" for="status_absent_{{ $labour->id }}"><i class="ti ti-x me-1"></i> Absent</label>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" name="attendances[{{ $index }}][notes]" class="form-control form-control-sm" placeholder="Optional notes" value="{{ $existingRecord?->notes }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success shadow-sm px-4"><i class="ti ti-device-floppy me-1"></i> Save Attendance Records</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="card-title mb-0">Attendance Records History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Labour</th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Status</th>
                            <th>Entered By</th>
                            <th>Notes</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $record)
                            @php($recordCarbon = \Carbon\Carbon::parse($record->attendance_date))
                            <tr>
                                <td>{{ $record->id }}</td>
                                <td>
                                    <div class="fw-bold">{{ $record->labour?->name ?? '-' }}</div>
                                </td>
                                <td>{{ $recordCarbon->format('Y-m-d') }}</td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ $recordCarbon->format('l') }}</span>
                                </td>
                                <td>
                                    @if($record->status === 'present')
                                        <span class="badge bg-soft-success text-success px-3 py-2"><i class="ti ti-check me-1"></i> Present</span>
                                    @elseif($record->status === 'half_day')
                                        <span class="badge bg-soft-warning text-warning px-3 py-2"><i class="ti ti-clock me-1"></i> Half Day</span>
                                    @else
                                        <span class="badge bg-soft-danger text-danger px-3 py-2"><i class="ti ti-x me-1"></i> Absent</span>
                                    @endif
                                </td>
                                <td>{{ $record->employee?->name ?? 'System' }}</td>
                                <td>{{ $record->notes ?? '-' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('labour-attendances.destroy', $record) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this attendance record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Attendance">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No labour attendance records found for selected criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($attendances->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end">
                {{ $attendances->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
