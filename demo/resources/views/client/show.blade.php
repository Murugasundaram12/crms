@extends('layouts/contentNavbarLayout')

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@section('title', 'View | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')
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

    .card {
        margin-top: 30px;
        padding: 15px;
    }

    .paginatestyle {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    @media (max-width: 768px) {

        #filter-section .col-md-3,
        #filter-section .col-md-2,
        #filter-section .col-md-1 {
            margin-bottom: 10px;
        }
    }
</style>
@section('content')


    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session()->get('message') }}
        </div>
    @endif
    @if (session()->has('msg'))
        <div class="alert alert-danger">
            {{ session()->get('msg') }}
        </div>
    @endif
    <div style="margin-top: 30px;">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Project Details </span>
        </h4>
        <div class="row" style="position:absolute; top:160px; right:50px ">
            <div class="col-md-12">
                <ul class="nav nav-pills flex-column flex-md-row mb-3">
                    <li class="nav-item"><a class="nav-link active" href="{{ route('client-index') }}"> Back</a></li>

                </ul>
            </div>
        </div>
    </div>
    <div class="card">
        <div id="filter-section">
            <form>
                <div class="row align-items-end">
                    <div class="col-md-2">
                        <label for="entries">Entities</label>
                        <select id="entries" class="form-control">
                            <option value="10" {{ request('paginate') == '10' ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('paginate') == '25' ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('paginate') == '50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('paginate') == '100' ? 'selected' : '' }}>100</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="date_range">Date Range</label>
                        <input type="text" id="date_range" name="date_range" class="form-control"
                            value="{{ request('date_range') }}">
                    </div>

                    <div class="col-md-3">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                            class="form-control">
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <!-- Search button -->
                        <button class="btn btn-primary d-flex justify-content-center align-items-center" type="submit">
                            <i class="bx bx-search"></i>
                        </button>

                        <!-- Reset button -->
                        <a class="btn btn-danger d-flex justify-content-center align-items-center"
                            href="{{ route('client-show', $client_id) }}">
                            <i class="bx bx-x-circle"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>




    <!-- Basic Bootstrap Table -->
    <div class="card" style="max-width: 1200px; margin: 22px auto; height:250px">
        <input type="hidden" name="client_id" id="client_id" value="{{ $client_id }}">
        <!-- <h5 class="card-header">Table Basic</h5> -->
        <div class="table-responsive text-nowrap">
            <table class="table" id="show_expense_listing_table">
                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Project Name</th>
                        <th>Advanced Amount</th>
                        <th>Total Amount</th>
                        <th>Remaining</th>
                        <!-- <th>Payment Mode</th>  -->
                        <th>Project Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if (count($projects) > 0)
                        @foreach ($projects as $project)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $project->name ? $project->name : '--' }}</td>
                                <td><b><span style="color:#ef6a0e">{{ $project->advance_amt }}</span></b></td>
                                <td><b><span style="color: green;">{{ $project->total_amt }}</span></b></td>
                                <td><b><span style="color: red;">{{ $project->profit }}</span></b></td>
                                <!-- <td>{{ $project->payment_name }}</td> -->
                                <td>
                                    @if ($project->project_status == 0)
                                        <button type="button" class="btn btn-success">Active</button>
                                    @else
                                        <button type="button" class="btn btn-danger">De-active</button>
                                    @endif
                                </td>
                                <td>{{ $project->start_date }}</td>
                                <td>{{ $project->end_date }}</td>
                            </tr>
                        @endforeach
                        @else
                        <tr><td colspan="7"><center>No data found.</center></td></tr>
                        @endif

                </tbody>
            </table>
            <div class="paginatestyle mt-4">
                {{ $projects->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
    <!--/ Basic Bootstrap Table -->

    <p class="text-end" style="margin-top: 53px; margin-right: 147px; font-size: medium;">
        <span class="d-inline"><b>Total Advanced Amount:</b> <b><span
                    style="color:#ef6a0e">{{ $sum }}</span></b></span>
        <span class="d-inline ms-3"><b>Total Amount:</b> <b><span
                    style="color: green;">{{ $total }}</span></b></span>
        <span class="d-inline ms-3"><b>Total Remaining Amount:</b> <b><span
                    style="color: red;">{{ $remaining }}</span></b></span>

    </p>


    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" defer></script>

<script>
$(function() {
  $('input[name="date_range"]').daterangepicker({
    autoUpdateInput: false,
    opens: 'left',
    locale: {
      cancelLabel: 'Clear'
    }
  });

  $('input[name="date_range"]').on('apply.daterangepicker', function(ev, picker) {
    $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
  });

  $('input[name="date_range"]').on('cancel.daterangepicker', function(ev, picker) {
    $(this).val('');
  });
});
</script>
@endsection
