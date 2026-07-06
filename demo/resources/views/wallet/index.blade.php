@extends('layouts/contentNavbarLayout')

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>

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
    
    @media (max-width: 768px) {

        #filter-section .col-md-3,
        #filter-section .col-md-2,
        #filter-section .col-md-1 {
            margin-bottom: 10px;
        }
    }
       .card {
        margin-top: 30px;
        padding: 15px;
    }
</style>
@section('title', 'List | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')

@if (session()->has('message'))

<script>
      $(function() {
            toastr.success('{{ session('message') }}', {
                timeOut: 1000,
                fadeOut: 1000,
            });
        });
</script>
@endif
@if(session()->has('msg'))
<script>
$(function() {
  toastr.error('{{ session('msg') }}', {
      timeOut: 1000,
      fadeOut: 1000,
  });
});
</script>
@endif
    @if (session()->has('transfer-popup'))
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

        <script>
            $(function() {
              toastr.success('{{ session('transfer-popup') }}', {
                timeOut: 1000,
                fadeOut: 1000,
            });
            });
        </script>
    @endif
    <div style="margin-top: 30px;">
        <h4 class="fw-bold py-3 mb-4" style="margin-top: -55px;font-size:13px;">
            <span class="fw-light" style="color: black;">Wallet History </span>
        </h4>
    </div>

    <div class="card">
        <div id="filter-section">
            <div class="row">
                <div class="col-md-1">
                    <form id="submit-form">
                        <label for="entries">Entities</label>
                        <select id="entries" class="form-control" name="paginate" id="showing_result"
                            onchange="submitform()">
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
              
                    <div class="col-md-2">
                        <label for="member">Client</label>
                        <select id="client_id" name="client_id" class="form-control">
                            <option value="">Select Client</option>
                            @foreach ($clients as $member)
                                <option value="{{ $member->id }}"
                                    {{ request('client_id') == $member->id ? 'selected' : '' }}>{{ $member->first_name }}
                                    {{ $member->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
               
                <div class="col-md-2">
                    <label for="member">Projects</label>
                    <select id="project_id" name="project_id" class="form-control">
                        <option value="">Select project</option>
                        @foreach ($projects as $member)
                            <option value="{{ $member->id }}"
                                {{ request('project_id') == $member->id ? 'selected' : '' }}>{{ $member->name }}
                                </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                        class="form-control">
                </div>
            </div>

            <div class="mt-3 text-end">
                <button class="btn btn-primary client_search" type="submit">
                    <i class="bx bx-search"></i>
                </button>
                <a class="btn btn-danger" href="{{ route('wallet-history') }}">
                    <i class="bx bx-x-circle"></i>
                </a>
            </div>
            </form>
        </div>
    </div>
    <!-- Basic Bootstrap Table -->
    <div class="card" >
        <!-- <h5 class="card-header">Table Basic</h5> -->
        <div class="table-responsive text-nowrap">
            <table class="table" id="transfer_listing_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Received Date</th>
                        <th>Member Name</th>
                        <th>Client Name</th>
                        <th>Project Name</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Payment Mode</th>
                        <th>Description</th>
                        <th>Stage</th>

                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if(count($wallet) > 0)
                    @foreach ($wallet as $transfer)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($transfer->current_date)->format('d-m-Y') }}<br />
                                {{ \Carbon\Carbon::parse($transfer->current_date)->format('h:i A') }}</td>
                            <td>{{ $transfer->first_name }} {{ $transfer->last_name }}</td>
                            <td>{{ $transfer->client_first }} {{ $transfer->client_last }}</td>
                            <td>{{ $transfer->project_name }}</td>
                            <td>{{ $transfer->amount }}</td>
                            @if ($transfer->transfer_type == 0)
                            <td> <span style="padding: 5px 10px; background-color: #d4edda; color: #155724; border-radius: 5px;">Credited</span></td>
                            @else
                            <td><span style="padding: 5px 10px; background-color: #f8d7da; color: #721c24; border-radius: 5px;">Debited</span></td>
                            @endif
                                <td>{{ $transfer->payment_name }}</td>
                                <td>{{ $transfer->description ? $transfer->description : '--' }}</td>
                                <td>{{ $transfer->stage_name }}</td>
                        </tr>
                    @endforeach
                    @else
                    <tr><td colspan="7"><center>No data found.</center></td></tr>
                    @endif
                </tbody>
            </table>
             <div class="paginatestyle mt-4">
                {{ $wallet->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
    <!--/ Basic Bootstrap Table -->





    <p class='text-end' style="margin-top:30px;
    margin-right: 249px;
    font-size: medium;"><b>Total
            Amount:</b>{{ $sum }}
        <br />&nbsp;
    </p>
    <div id="walletsuccess" class="modal fade">
        <div class="modal-dialog modal-confirm modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <center>
                        <h4 class="modal-title">Success</h4>
                    </center>
                </div>
                <hr>
                <div class="modal-body">
                    <p class="text-center success-msg"></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success btn-block" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
          $('#client_id').select2({
            placeholder: "Select",
            allowClear: true,
            width: '100%',
        });
        $('#project_id').select2({
            placeholder: "Select",
            allowClear: true,
            width: '100%',
        });
    </script>
@endsection
