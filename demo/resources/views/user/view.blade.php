@extends('layouts/contentNavbarLayout')
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" defer></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    @media only screen and (max-width:320px) {
        .aa {
            display: inline !important;
        }
    }

    .dataTables_wrapper {
        font-family: tahoma;
        font-size: 13px;
        clear: both;

    }

    .dataTables_length select {
        width: 120px;
    }

    .table-responsive {
        margin-top: 5px;
        margin-left: 5px;
    }



    /* General container tweaks */
    .container {
        font-family: 'Poppins', sans-serif;
        color: #2c3e50;
    }

    /* Left section (Profile) */
    .col-md-3 {
        background: #fdfdfd;
        padding: 40px 20px;
        text-align: center;
        border-right: 1px solid #e0e0e0;
    }

    /* Circular image container */
    .col-md-3 div[style*="border-radius: 50%"] {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border: 4px solid #ffffff;
        background-color: #ffffff;
        transition: transform 0.3s ease;
    }

    .col-md-3 div[style*="border-radius: 50%"]:hover {
        transform: scale(1.05);
    }

    /* Profile image inside circle */
    .col-md-3 img {
        object-fit: cover;
    }

    /* Name text */
    .col-md-3 h5 {
        margin-top: 20px;
        font-size: 1.4rem;
        font-weight: 600;
        color: #2c3e50;
    }

    /* Right section (Details) */
    .col-md-9 {
        background-color: #ffffff;
        padding: 40px 30px;
        box-shadow: inset 0 0 0 1000px rgba(255, 255, 255, 0.03);
    }

    /* Highlighted hours text */
    .col-md-9 b {
        font-size: 1.2rem;
        color: #1e88e5;
    }

    /* Sub-label */
    .col-md-9 small {
        font-size: 0.85rem;
        color: #7b8a8b;
    }

    /* Borders */
    .border-start {
        border-left: 1px solid #e0e0e0 !important;
    }

    .border-end {
        border-right: 1px solid #e0e0e0 !important;
    }

    .tab-container {
        width: 100%;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .tab-buttons {
        display: flex;
        background: #eee;
        border-bottom: 1px solid #ccc;
    }

    .tab-button {
        flex: 1;
        padding: 14px;
        background: transparent;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 14px;
        color: #555;
        transition: background 0.3s, color 0.3s;
    }

    .tab-button:hover {
        background-color: #171f29;
        color: #fff;
    }

    .tab-button.active {
        background-color: #171f29;
        color: #fff !important;
    }

    .tab-content {
        display: none;
        padding: 20px;
    }

    .tab-content.active {
        display: block;
    }

    svg {
        width: 18px;
        height: 18px;
        stroke: currentColor;
    }

    .tab-pane {
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    .tab-pane.active,
    .tab-pane.show {
        display: block;
        opacity: 1;
    }
</style>


@section('title', 'View | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
    <!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->
    <!-- Basic Layout & Basic with Icons -->
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-end">
            <a class="btn btn-primary" href="{{ route('user-index') }}">
                <i class="bx bx-arrow-back me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card p-3"> <!-- Reduced padding -->
        <div class="container">
            <div class="row align-items-center" style="">
                <!-- Profile image and name -->
                <div class="col-md-3 text-center py-3 border-end">
                    @if ($user->image)
                        <div style="width: 90px; height: 90px; border-radius: 50%; overflow: hidden; margin: 0 auto;">
                            <img src="{{ url('public/images/' . $user->image) }}" width="100%" height="100%"
                                style="object-fit: cover;">
                        </div>
                        <br>
                    @endif
                    <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>
                </div>

                <!-- Working hours info -->
                <div class="col-md-9 py-3 border-start">
                    <div>
                        <b>Total working hours <small>(last 30 days)</small>: {{ $hours }}</b>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <!-- Include Font Awesome in your HTML head -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <div class="tab-container" style="margin-top: 5px;">
        <div class="tab-buttons">
            <!-- General Info -->
            <a href="{{ route('user-show', ['id' => $user->id, 'tab' => 'general-info']) }}"
                class="tab-button {{ request('tab') == 'general-info' || $tab == 'general-info' ? 'active' : '' }}">
                <!-- Icon: User -->
                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    viewBox="0 0 24 24" height="20" width="20">
                    <path d="M20 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M4 21v-2a4 4 0 0 1 3-3.87"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                General Info
            </a>

            <!-- Job Info -->
            <a href="{{ route('user-show', ['id' => $user->id, 'tab' => 'job-info']) }}"
                class="tab-button {{ request('tab') == 'job-info' || $tab == 'job-info' ? 'active' : '' }}">
                <!-- Icon: Briefcase -->
                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    viewBox="0 0 24 24" height="20" width="20">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 3h-8a2 2 0 0 0-2 2v2h12V5a2 2 0 0 0-2-2z"></path>
                </svg>
                Job Info
            </a>

            <!-- Expenses -->
            @can('expenses-history')
                <a href="{{ route('user-show', ['id' => $user->id, 'tab' => 'expenses-info']) }}"
                    class="tab-button {{ request('tab') == 'expenses-info' || $tab == 'expenses-info' ? 'active' : '' }}">
                    <!-- Icon: Dollar File -->
                    <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        viewBox="0 0 24 24" height="20" width="20">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <line x1="10" y1="9" x2="8" y2="9"></line>
                    </svg>
                    Expenses
                </a>
            @endcan

            <!-- Attendance -->
            <a href="{{ route('user-show', ['id' => $user->id, 'tab' => 'attendance-info']) }}"
                class="tab-button {{ request('tab') == 'attendance-info' || $tab == 'attendance-info' ? 'active' : '' }}">
                <!-- Icon: Calendar -->
                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    viewBox="0 0 24 24" height="20" width="20">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                Attendance
            </a>
        </div>



        <div class="tab-content {{ request('tab') == 'general-info' || $tab == 'general-info' ? ' show active' : '' }}">
            <div class="tab-pane {{ request('tab') == 'general-info' || $tab == 'general-info' ? ' show active' : '' }}" id="general-info">
                <div class="row">
                    <div class="col-xl">
                        <div class="card mb-4" style="margin-top:30px;">

                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-fullname">First Name :</label>
                                            {{ $user->first_name }}
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-company">Last Name :</label>
                                            {{ $user->last_name }}

                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-email">Email :</label>
                                            {{ $user->email }}
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-phone">Phone Number :</label>
                                            {{ $user->phone }}
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-message">Gender :</label>
                                            @if ($user->gender == 1)
                                                Male
                                            @elseif($user->gender == 2)
                                                Female
                                            @else
                                                Others
                                            @endif
                                            <br>
                                        </div>

                                        <div class="mb-3">

                                            <label class="form-label" for="basic-default-phone">Roles : </label>
                                            {{ $user->role_name }}
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-content {{ request('tab') == 'job-info' || $tab == 'job-info' ? ' show active' : '' }} ">
            <div class="tab-pane {{ request('tab') == 'job-info' || $tab == 'job-info' ? ' show active' : '' }}  " id="job-info">
                <div class="row">

                    <div class="col-xl">
                        <div class="card mb-4" style="margin-top:30px;">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-phone">Job Title :</label>
                                            {{ $user->job_title }}
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-phone">Salary : </label>
                                            {{ $user->salary }}

                                        </div>


                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="datetimepicker1">Date of joining :</label>
                                            {{ $user->date_of_joining }}<br>

                                            <div class="mb-3">
                                                <label class="form-label" for="basic-default-phone">Upload Government
                                                    Document :</label>
                                                @if ($user->government_image != '' && $user->government_image != null)
                                                    <a href="{{ url('public/images/' . $user->government_image) }}"
                                                        target="_blank">View
                                                    </a>
                                                @else
                                                    --
                                                @endif

                                            </div>

                                        </div>

                                    </div>



                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="tab-content {{ request('tab') == 'expenses-info' || $tab == 'expenses-info' ? ' show active' : '' }}">
            <div class="tab-pane {{ request('tab') == 'expenses-info' || $tab == 'expenses-info' ? ' show active' : '' }}" id="expenses-info">
                <div class="card" >
                    <form id="submit-form">
                        <div class="d-flex justify-content-between align-items-center m-2">

                            <div class="d-flex justify-content-center align-items-center">
                                <span class="me-2">Showing:</span>
                                <select class="form-control me-2" style="width:50%" name="paginate" id="showing_result"
                                    onchange="submitform()">
                                    <option value="15" {{ request('paginate') == 15 ? 'selected' : '' }}>15</option>
                                    <option value="50" {{ request('paginate') == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('paginate') == 100 ? 'selected' : '' }}>100</option>
                                </select>

                            </div>
                            <div class="d-flex justify-content-center align-items-center">
                                {{-- <form method="post" action="{{ route('roles.index') }}" class="d-flex align-items-center">  --}}
                                <span class="me-2">Search:</span>
                                <input type="text" name="search" id="search" value="{{ request('search') }}"
                                    class="form-control me-2" style="width: 200px;">

                                <button class="btn btn-primary me-2 client_search" type="submit">
                                    <i class="bx bx-search" style="font-size: 18px;"></i>
                                </button>
                                <a class="btn btn-danger" href="{{ route('user-show', ['id' => request('id')]) }}">
                                    <i class="bx bx-x-circle" style="font-size: 18px;"></i>
                                </a>
                                {{-- </form> --}}
                            </div>

                        </div>
                    </form>
                    <div class="table-responsive text-nowrap">
                        <table class="table" id="unpaid_expenses_listing_table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Project Name</th>
                                    <th>Amount</th>

                                    <th>Image</th>
                                    <th>Payment Mode</th>
                                    <th>Description</th>
                                    <th>Paid</th>
                                    <th>Unpaid</th>
                                    <th>Advanced Amount</th>
                                    @role('Admin')
                                        <th>Added By</th>

                                        <th>Edited By</th>
                                    @endrole
                                    <th>Paid date</th>

                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">

                                @foreach ($expenses as $expense)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $expense->category_name ? $expense->category_name : '--' }}</td>
                                        <td>{{ $expense->project_name ? $expense->project_name : '--' }}</td>
                                        <td>{{ $expense->amount }}</td>
                                        <td>
                                            @if ($expense->image != '' || $expense->image != null)
                                                <img src={{ url('public/images/' . $expense->image) }}" width="50px">
                                            @endif
                                        </td>
                                        <td>{{ $expense->payment_name }}</td>
                                        <td>{{ $expense->description ? $expense->description : '--' }}</td>
                                        <td><span style="color: green;">{{ $expense->paid_amt }}</td>
                                        <td>
                                            @if ($expense->unpaid_amt != 0)
                                                <a href="{{ route('unpaidex-create', $expense->id) }}"
                                                    style="color:red">{{ $expense->unpaid_amt }}</a>
                                            @else
                                                {{ $expense->unpaid_amt }}
                                            @endif
                                        </td>
                                        <td>{{ $expense->extra_amt }}</td>
                                        @role('Admin')
                                            <td>{{ $expense->first . '' . $expense->last }}</td>
                                            <td>{{ $expense->first_name . '' . $expense->last_name }}</td>
                                        @endrole
                                        @if (!empty($unpaid_date))
                                            <td>{{ $unpaid_date->updated_at }}</td>
                                        @else<td>{{ $expense->created_at }}</td>
                                        @endif

                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                        <div class="paginatestyle mt-4">
                            {{ $expenses->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div
            class="tab-content {{ request('tab') == 'attendance-info' || $tab == 'attendance-info' ? 'show active' : '' }} ">
            <div class="tab-pane {{ request('tab') == 'attendance-info' || $tab == 'attendance-info' ? 'show active' : '' }} " id="attendance-info">
                <div class="card" style="margin-top: 0px;">
                    <form id="submit-form1">
                        <div class="d-flex justify-content-between align-items-center m-2">

                            <div class="d-flex justify-content-center align-items-center">
                                <span class="me-2">Showing:</span>
                                <select class="form-control me-2" style="width:50%" name="paginate1" id="showing_result"
                                    onchange="submitform1()">
                                    <option value="15" {{ request('paginate1') == 15 ? 'selected' : '' }}>15</option>
                                    <option value="50" {{ request('paginate1') == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('paginate1') == 100 ? 'selected' : '' }}>100
                                    </option>
                                </select>

                            </div>

                            <div class="d-flex justify-content-center align-items-center">
                                <label for="date_range" style="margin-right: 5px;">Date</label>
                                <input type="text" id="date_range" name="date_range" class="form-control"
                                    value="{{ request('date_range') }}" style="margin-right: 5px;">

                                <button class="btn btn-primary me-2 client_search" type="submit">
                                    <i class="bx bx-search" style="font-size: 18px;"></i>
                                </button>
                                <a class="btn btn-danger" href="{{ route('user-show', ['id' => request('id')]) }}">
                                    <i class="bx bx-x-circle" style="font-size: 18px;"></i>
                                </a>
                              
                            </div>

                        </div>
                    </form>
                    <div class="table-responsive text-nowrap">
                        <table class="table" id="attendance_listing_table">
                            <thead>
                                <tr>

                                    <th>Login</th>
                                    <th>Logout</th>
                                    <th>Duration</th>

                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">

                                @foreach ($attendances as $attendance)
                                    <tr>
                                        <td>{{ $attendance->created_at ? $attendance->created_at : '--' }}</td>
                                        <td>{{ $attendance->updated_at ? $attendance->updated_at : '--' }}</td>
                                        <td>{{ $attendance->duration }}</td>

                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                        <div class="paginatestyle mt-4">
                            {{ $attendances->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        function submitform() {
            $('#submit-form').submit();
        }

        function submitform1() {
            $('#submit-form1').submit();
        }
        $(function() {
            $('input[name="date_range"]').daterangepicker({
                autoUpdateInput: false, // don't set default value
                opens: 'left',
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            $('input[name="date_range"]').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format(
                    'MM/DD/YYYY'));
            });

            $('input[name="date_range"]').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        });
    </script>
@endsection
