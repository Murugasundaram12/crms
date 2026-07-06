@extends('layouts/contentNavbarLayout')
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .table-responsive {
        overflow-y: auto !important;
    }

    #expenses_listing_table th,
    #expenses_listing_table td {
        width: 10%;
        /* Adjust the width as needed */
        font-size: 13px;
        /* Adjust the font size as needed */
        white-space: nowrap;
        /* Prevent text from wrapping */
        text-overflow: ellipsis;
        /* Add ellipsis for long text */
        overflow: hidden;
        /* Hide overflowing content */
    }

    .bootstrap-select {
        max-width: 150px;
    }

    a.disabled {
        pointer-events: none;
        cursor: default;
        opacity: 0.5;
    }

    /* Customize the styling further if needed */

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

    table {
        width: 50%;
        border-spacing: 0;
        /* Remove spacing between cells */
        border-collapse: collapse;
        /* Collapse cell borders */
    }

    td,
    th {
        padding: 5px;
        /* Reduce cell padding */
    }

    .dropdown-toggle {
        width: 146px !important;
    }

    .bs-caret::after {
        color: #f7f7f7 !important;
        content: "";
        display: none !important;
    }

    .table-responsive {
        overflow: auto !important;
    }

    @media (max-width: 768px) {

        #filter-section .col-md-3,
        #filter-section .col-md-2,
        #filter-section .col-md-1 {
            margin-bottom: 10px;
        }
    }

    #filter-section {
        padding: 5px !important;
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

    /* Keyframes: Flip-fade effect */
    @keyframes dropdownFlipIn {
        0% {
            transform: perspective(400px) rotateX(-90deg);
            /* Only flip down */
            opacity: 0;
        }

        100% {
            transform: perspective(400px) rotateX(0deg);
            opacity: 1;
        }
    }

    /* Glass-style dropdown with full-rounded corners and no horizontal animation */
    .filter-dropdown {
        max-height: 350px;
        overflow-y: auto;
        border-radius: 16px;
        width: 360px;
        padding: 20px;
        display: none;

        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
    }

    /* Apply only flip-in effect */
    .dropdown-menu.show.filter-dropdown {
        display: block !important;
        animation: dropdownFlipIn 0.4s ease both;
        transform-origin: top;
        position: absolute;
        top: 100% !important;
    }

    /* Close button styled in red */
    .btn-close {
        background-color: rgba(220, 53, 69, 0.8);
        border-radius: 50%;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
        opacity: 1;
    }

    /* Make labels bold */
    .filter-dropdown label {
        font-weight: 600;
    }

    /* Small & neat buttons side by side */
    .filter-dropdown .btn {
        padding: 4px 12px;
        font-size: 0.85rem;
    }
</style>

@section('title', 'List | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')

    @if (session()->has('expenses-popup'))
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

        <script>
            $(function() {
                toastr.success('{{ session('expenses-popup') }}', {
                    timeOut: 1000,
                    fadeOut: 1000,
                });
            });
        </script>
    @endif
    @if (session()->has('message'))
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

        <script>
            $(function() {
                toastr.success('{{ session('message') }}', {
                    timeOut: 1000,
                    fadeOut: 1000,
                });
            });
        </script>
    @endif

    @if (session()->has('msg'))
        <script>
            $(function() {
                toastr.error('{{ session('msg') }}', {
                    timeOut: 1000,
                    fadeOut: 1000,
                });
            });
        </script>
    @endif

    <div class="" style="margin-top: -10px;">
        <div class="row g-2 mt-3">
            <div class="col-md-12 text-end">
                <div class="d-flex justify-content-end flex-wrap gap-2" style="margin-right: 5px">
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary no-caret" type="button" data-bs-toggle="dropdown"
                            data-bs-auto-close="false" aria-expanded="false" style="position: relative">
                            Filter
                        </button>

                        <div class="dropdown-menu p-4 shadow filter-dropdown" id="filterDropdown" style="min-width: 380px;">
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-sm btn-danger" id="closeDropdownBtn">x</button>

                            </div>
                            <form id="submit-form">
                                <input type="hidden" name="tab" value="{{ request('tab') }}" id="tab">
                                <div id="filter-section">
                                    <!-- First Row: Filters + Search -->
                                    <div class="row g-2 align-items-end">
                                        <div class="mb-2">
                                            <label for="entries">Entities</label>
                                            <select id="entries" name="paginate" class="form-control"
                                                onchange="submitform()">
                                                <option value="10" {{ request('paginate') == '10' ? 'selected' : '' }}>
                                                    10</option>
                                                <option value="25" {{ request('paginate') == '25' ? 'selected' : '' }}>
                                                    25</option>
                                                <option value="50" {{ request('paginate') == '50' ? 'selected' : '' }}>
                                                    50</option>
                                                <option value="100" {{ request('paginate') == '100' ? 'selected' : '' }}>
                                                    100</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label for="category_id">Main Category</label>
                                            <select id="main_category_id" name="main_category_id" class="glass-select2 form-control ">
                                                <option value="">Select Category</option>
                                                @foreach ($main_category as $main)
                                                    <option value="{{ $main->id }}"
                                                        {{ request('main_category_id') == $main->id ? 'selected' : '' }}>
                                                        {{ $main->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label for="category_id">Category</label>
                                            <select id="category_id" name="category_id" class="glass-select2 form-control ">
                                                <option value="">Select Category</option>
                                                @foreach ($category as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>


                                        <div class="mb-2">
                                            <label for="project_id">Project</label>
                                            <select id="project_id" name="project_id" class="form-control">
                                                <option value="">Select Project</option>
                                                @foreach ($project as $project)
                                                    <option value="{{ $project->id }}"
                                                        {{ request('project_id') == $project->id ? 'selected' : '' }}>
                                                        {{ $project->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @role('Admin')
                                            <div class="mb-2">
                                                <label for="user_id">Member</label>
                                                <select id="user_id" name="user_id" class="form-control">
                                                    <option value="">Select Member</option>
                                                    @foreach ($user as $member)
                                                        <option value="{{ $member->id }}"
                                                            {{ request('user_id') == $member->id ? 'selected' : '' }}>
                                                            {{ $member->first_name }}
                                                            {{ $member->last_name }} - {{ $member->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endrole

                                        <div class="mb-2">
                                            <label for="date_range">Date Range</label>
                                            <input type="text" id="date_range" name="date_range" class="form-control"
                                                value="{{ request('date_range') }}">
                                        </div>
                                        <div class="mb-2">
                                            <label for="search">Search</label>
                                            <input type="text" id="search" name="search"
                                                value="{{ request('search') }}" class="form-control" placeholder="Search">
                                        </div>
                                    </div>

                                    <!-- Second Row: Buttons -->
                                       <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-danger btn-sm" href="{{ route('expenses-history') }}">Reset</a>
                                        <button type="submit" class="btn btn-success btn-sm">Apply</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                    <button type="button" class="btn btn-success" id="expense-export">
                        <i class="bi bi-file-earmark-excel-fill"></i>
                    </button>
                    <button type="button" class="btn btn-danger" id="expense-pdf">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                </div>
            </div>
        </div>


        <!-- Basic Bootstrap Table -->
        <div class="tab-container" style="margin: 10px">

            <div id="designer" class="tab-content active">
                <div class="table-responsive text-nowrap" style="padding:20px;width:99%;">
                    <table class="table " id="expenses_listing_table">
                        <thead>
                            <tr>
                                @can('expenses-Over all delete option')
                                    <th><a data-toggle="modal" href="javascript:void(0)" class="deleteAllExpense disabled"><i
                                                class="bi bi-trash" style="font-size:24px; color:red"></i> </a></th>
                                @endcan
                                <th>Paid date</th>
                                <th>Main <br />Category</th>
                                <th>Category <br /> Name</th>
                                <th>Project Name</th>
                                <th>Labour Name</th>
                                <th>Vendor Name</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Unpaid</th>
                                <th>Advanced <br /> Amount</th>
                                <th style="width:30px">Description</th>
                                <th>Image</th>
                                <th>Payment Mode</th>
                                @role('Admin')
                                    <th>Added By</th>
                                    <th>Edited By</th>
                                @endrole

                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @if (count($expenses) > 0)
                                @foreach ($expenses as $expense)
                                    <tr>
                                        @can('expenses-Over all delete option')
                                            <td><input type="checkbox" class="expense_id" name="expense_id" id="expense_id"
                                                    value="{{ $expense->id }}">
                                            </td>
                                        @endcan
                                        <td>{{ \Carbon\Carbon::parse($expense->current_date)->format('d-m-Y') }} <br />
                                            {{ \Carbon\Carbon::parse($expense->current_date)->format('h:i A') }}</td>
                                        <td>{{ $expense->main_category_name ?? '--' }}</td>

                                        <td>{{ $expense->category_name ? $expense->category_name : '--' }}</td>
                                        <td>{{ $expense->project_name ? $expense->project_name : '--' }}</td>
                                        <td>{{ $expense->labour_name ? $expense->labour_name : '--' }}</td>
                                        <td>{{ $expense->vendor_name ? $expense->vendor_name : '--' }}</td>
                                        <td><b><span style="color:#ef6a0e">{{ $expense->amount }}</span></b></td>
                                        <td><b><span style="color: green;">{{ $expense->paid_amt }}</span></b></td>
                                        <td>
                                            @if ($expense->unpaid_amt != 0)
                                                <b><a href="{{ route('unpaidex-create', $expense->id) }}"
                                                    style="color:red">{{ $expense->unpaid_amt }}</a></b> @else<b>
                                                    <p style="color:red">{{ $expense->unpaid_amt }}</p>
                                                </b>
                                            @endif
                                        </td>
                                        <td><b><span style="color:#840eef;">{{ $expense->extra_amt }}</span></b></td>
                                        <td style="width:10px"> @php
                                            $desc = $expense->description ?? '--';
                                        @endphp

                                            @if (strlen($desc) > 30)
                                                <span class="desc">{{ substr($desc, 0, 30) }}...</span>
                                                <span class="full-desc" style="display:none;">{{ $desc }}</span>
                                                <a href="javascript:void(0);" onclick="toggleDesc(this)">Show more</a>
                                            @else
                                                {{ $desc }}
                                            @endif
                                        </td>

                                        <td>
                                            @if ($expense->image != '' || $expense->image != null)
                                                <a href="{{ url('images/' . $expense->image) }}" target="_blank">View</a>
                                            @else
                                                --
                                            @endif
                                        <td>{{ $expense->payment_name }}</td>

                                        @role('Admin')
                                            <td>{{ $expense->first . '' . $expense->last }}</td>
                                            <td>{{ $expense->first_name . '' . $expense->last_name }}</td>
                                        @endrole


                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9">
                                        <center>No data found.</center>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    <div class="paginatestyle mt-4">
                        {{ $expenses->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>

            {{-- <div id="developer" class="tab-content">
            <h2>Developer Panel</h2>
            <p>Code integrations, API tools, and advanced settings.</p>
        </div>

        <div id="preview" class="tab-content">
            <h2>Preview Panel</h2>
            <p>See your layout in real-time.</p>
        </div> --}}
        </div>


        <!--/ Basic Bootstrap Table -->

        <p class="text-end" style="margin-top: 31px; margin-right: 147px; font-size: medium;">
            <span class="d-inline"><b>Total Amount:</b> <b><span
                        style="color:#ef6a0e">{{ $sum }}</span></b></span>
            <span class="d-inline ms-3"><b>Total Paid Amount:</b> <b><span
                        style="color: green;">{{ $paid_amt }}</span></b></span>
            <span class="d-inline ms-3"><b>Total Unpaid Amount:</b> <b><span
                        style="color: red;">{{ $unpaid_amt }}</span></b></span>
            <span class="d-inline ms-3"><b>Total Advanced Amount:</b><b><span style="color: #840eef;">
                        {{ $advanced_amt }}</span></b></span>
        </p>

        <!--- modal popup for transfer -->
        <div class="modal fade" id="unpaid-popup" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog d-flex justify-content-center">
                <div class="modal-content w-75">
                    <div class="modal-header">
                        <h5 class="modal-title" id="unpaid_title">Unpaid Details</h5>
                        <button type="button" class="btn-close unpaid-close" data-mdb-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="unpaidform"></div>
                    </div>
                </div>
            </div>
        </div>
        <!--- modal popup for transfer -->

        <!-- Modal -->
        <div class="modal fade" id="myModal" role="dialog">
            <div class="modal-dialog modal-sm">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Confirmation</h4>

                    </div>
                    <div class="modal-body">
                        <p style="text-align: center;">Are you sure want to delete this?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary yes-delete" data-dismiss="modal">Yes</button>
                        <button type="button" class="btn btn-danger no-delete" data-dismiss="modal">No</button>
                    </div>
                </div>

            </div>
        </div>
        <!--- delete all confirmation -->
        <div class="modal fade" id="deleteAllModal" role="dialog">
            <div class="modal-dialog modal-sm">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Confirmation</h4>

                    </div>
                    <div class="modal-body">
                        <p style="text-align: center;">Are you sure want to delete this?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary yes-delete-all" data-dismiss="modal">Yes</button>
                        <button type="button" class="btn btn-danger no-delete-all" data-dismiss="modal">No</button>
                    </div>
                </div>

            </div>
        </div>
        <!--- delete all confirmation -->
        <div class="modal fade" id="myModal_reason" role="dialog">
            <div class="modal-dialog modal-sm">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Reason</h4>
                        <hr>
                    </div>
                    <div class="modal-body">
                        <input type="text" id="reason" name="reason" class="form-control"
                            placeholder="Enter reason" value="" />
                        <label id="reason-error" class="error" style="color:red" for="basic-default-email">Reason is
                            required</label>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary yes-reason" data-dismiss="modal">Submit</button>
                        <button type="button" class="btn btn-danger no-reason" data-dismiss="modal">cancel</button>
                    </div>
                </div>

            </div>
        </div>

        <!-- modal popup for delete role ended -->

        <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"
            integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous">
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"
            integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous">
        </script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            function toggleDesc(link) {
                const td = link.parentElement;
                const short = td.querySelector('.desc');
                const full = td.querySelector('.full-desc');

                if (short.style.display === 'none') {
                    short.style.display = 'inline';
                    full.style.display = 'none';
                    link.textContent = 'Show more';
                } else {
                    short.style.display = 'none';
                    full.style.display = 'inline';
                    link.textContent = 'Show less';
                }
            }
        </script>
        <script>
            const buttons = document.querySelectorAll('.tab-button');
            const contents = document.querySelectorAll('.tab-content');

            buttons.forEach(button => {
                button.addEventListener('click', () => {
                    buttons.forEach(btn => btn.classList.remove('active'));
                    contents.forEach(tab => tab.classList.remove('active'));

                    button.classList.add('active');
                    document.getElementById(button.getAttribute('data-tab')).classList.add('active');
                });
            });
        </script>

        <script>
            function submitform() {
                $('#submit-form').submit();
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
            $('#main_category_id').select2({
                placeholder: "Select",
                allowClear: true,
                width: '100%',
            });
            $('#category_id').select2({
                placeholder: "Select",
                allowClear: true,
                width: '100%',
            });
            $('#project_id').select2({
                placeholder: "Select",
                allowClear: true,
                width: '100%',
            });
            $('#user_id').select2({
                placeholder: "Select",
                allowClear: true,
                width: '100%',
            });
            $("document").ready(function() {
                var roleid;
                var user;
                $('.error').addClass('hide');
                setTimeout(function() {
                    $("div.alert").remove();
                }, 5000); // 5 secs
                $("#expenses_listing_table").on("click", ".deleteExpense", function() {
                    roleid = $(this).attr('data-id');
                    user = $(this).attr('data-user');
                    $("#myModal").removeClass('fade');
                    $("#myModal").modal('show');
                });
                $('.no-delete').click(function() {
                    $("#myModal").addClass('fade');
                    $("#myModal").modal('hide');
                });
                $('.no-reason').click(function() {
                    $("#myModal_reason").addClass('fade');
                    $("#myModal_reason").modal('hide');
                });
                $('.yes-delete').click(function() {
                    console.log('roleid', roleid);
                    $('#myModal').modal('hide');
                    $('#myModal_reason').modal('show');
                    // $("#myModal").modal('hide');
                    // var url = '{{ route('expenses-delete', ':id') }}';
                    //       url1 = url.replace(':id', roleid);
                    //       window.location.href=url1;
                });
                $('.yes-reason').click(function() {
                    console.log('roleid', roleid);
                    var reason = $('#reason').val();
                    console.log('reason', reason);
                    if (reason == '') {
                        $('#reason-error').removeClass('hide');
                    } else {
                        $('#reason-error').addClass('hide');
                        $("#myModal_reason").modal('hide');
                        var url = '{{ route('expenses-delete') }}';

                        window.location.href = url + '?id=' + roleid + '&reason=' + reason + '&user=' + user;
                    }
                });
            });
            $(document).ready(function() {
                $('#unpaid-popup').modal('hide');
            });

            // });
            $('#expense-export').click(function() {
                console.log('test');
                var user = $('#user_id').find(":selected").val();
                var project = $('#project_id').find(":selected").val();
                var category = $('#category_id').find(":selected").val();
                var main_category = $('#main_category_id').find(":selected").val();
                var date_range = $('#date_range').val();
                var search = $('#search').val();
                var tab = $("#tab").val();
                var url = '{{ route('expenses-report-export') }}';
                window.location.href = url + '?date_range=' + date_range + '&search=' + search + '&main_category_id='+main_category+ '&category_id=' +
                    category + '&project_id=' + project + '&user_id=' + user + '&tab=' + tab;
            });
            $('#expense-pdf').click(function() {
                console.log('test1');
                var user = $('#user_id').find(":selected").val();
                var project = $('#project_id').find(":selected").val();
                var category = $('#category_id').find(":selected").val();
                var main_category = $('#main_category_id').find(":selected").val();
                var date_range = $('#date_range').val();
                var search = $('#search').val();
                var tab = $("#tab").val();
                var url = '{{ route('expenses-report-pdf') }}';
                window.location.href = url + '?date_range=' + date_range + '&search=' + search + '&main_category_id='+main_category+ '&category_id=' +
                    category + '&project_id=' + project + '&user_id=' + user + '&tab=' + tab;
            });
            $('.expense_id').on('click', function() {
                if ($(this).is(':checked')) {
                    $('.deleteAllExpense').removeClass('disabled');
                } else {
                    $('.deleteAllExpense').addClass('disabled');
                }
            });
            $('.deleteAllExpense').click(function() {
                $('#deleteAllModal').modal('show');
            });
            $('.no-delete-all').click(function() {
                $('.expense_id').prop('checked', false);
                $('.deleteAllExpense').addClass('disabled');
                $('#deleteAllModal').modal('hide');
            });
            $('.yes-delete-all').click(function() {

                var val = [];
                $('.expense_id:checked').each(function(i) {
                    val[i] = $(this).val();
                });
                $('.preloader').css('display', 'block');
                $.ajax({
                    type: "delete",
                    url: "{{ route('expense-delete-all') }}",
                    data: {
                        id: val,
                        _token: document.querySelector('meta[name="csrf-token"]').content,
                    },
                    dataType: 'json',
                    success: function(html) {
                        console.log(html);
                        $('.preloader').css('display', 'none');
                        $('#deleteAllModal').modal('hide');
                        toastr.success('Deleted Successfully', {
                            timeOut: 1000,
                            fadeOut: 1000,
                        });

                        setTimeout(function() {
                            // Do something after 5 seconds
                            location.reload(); //reload page
                        }, 5000);
                    }
                });
            });
              $(document).ready(function() {
            // Close dropdown on "Apply"
            $('#filterForm').on('submit', function() {
                var dropdownToggle = $('[data-bs-toggle="dropdown"]');
                var dropdown = bootstrap.Dropdown.getInstance(dropdownToggle[0]);
                if (dropdown) {
                    dropdown.hide();
                }
            });

            // Close dropdown on custom close button
            $('#closeDropdownBtn').on('click', function() {
                var dropdownToggle = $('[data-bs-toggle="dropdown"]');
                var dropdown = bootstrap.Dropdown.getInstance(dropdownToggle[0]);
                if (dropdown) {
                    dropdown.hide();
                }
            });
        });
              $('#main_category_id').change(function(){
          var main_id = $(this).val();
          $.ajax({
            type: 'get',
            url: "{{ route('expenses.category') }}",
            data: {main_id:main_id},
            dataType:'json',
            success:function(response){
              console.log(response);
              $('#category_id').empty();
              $('#category_id').append('<option value="">Select category</option>');
              $.each(response, function (index, category) {
                $('#category_id').append('<option value="'+category.id+'">'+category.name+'</option>');
               // $('#category_id').append(option);
            });
            }
          })
        })
        </script>
    @endsection
