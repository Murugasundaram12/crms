@extends('layouts/contentNavbarLayout')

{{-- Styles --}}
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/css/bootstrap-select.css">

<style>
    @media only screen and (max-width: 320px) {
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
    }

    table {
        width: 100%;
        border-spacing: 0;
        border-collapse: collapse;
    }

    td,
    th {
        padding: 5px;
    }

    .dropdown-toggle {
        width: 146px !important;
    }

    .bs-caret::after {
        display: none !important;
    }

    .card {
        margin-top: 30px;
        padding: 15px;
    }

    /* .paginatestyle {
        display: flex;
        justify-content: center;
        align-items: center;
    } */

    @media (max-width: 768px) {

        #filter-section .col-md-3,
        #filter-section .col-md-2,
        #filter-section .col-md-1 {
            margin-bottom: 10px;
        }
    }
</style>

@section('title', 'List | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
    <div class="container-fluid">
        <h4 class="fw-bold py-3 mb-4" style="margin-top: -25px;font-size:13px;">
            <span class="fw-light text-dark">Vendor History</span>
        </h4>

        <div class="card">
            {{-- Filter Section --}}
            <div id="filter-section" >
                <div class="row">
                    <div class="col-md-1">
                        <form>
                            <label for="entries">Entities</label>
                            <select id="entries"  class="form-control">
                                <option value="10" {{ request('paginate') == '10' ? 'selected' : '' }}>10</option>
                                <option value="25" {{ request('paginate') == '25' ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('paginate') == '50' ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('paginate') == '100' ? 'selected' : '' }}>100</option>
                            </select>
                    </div>

                    <div class="col-md-3">
                        <label for="from_date">From Date</label>
                        <input type="date" id="from_date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>

                    <div class="col-md-3">
                        <label for="to_date">To Date</label>
                        <input type="date" id="to_date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>

                    <div class="col-md-2">
                        <label for="member">Member</label>
                        <select id="member" name="member_id" class="form-control">
                            <option value="">Select Member</option>
                            @foreach ($vendor_list as $member)
                                <option value="{{ $member->id }}" {{ request('member_id') == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}" class="form-control">
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button class="btn btn-primary client_search" type="submit">
                        <i class="bx bx-search"></i>
                    </button>
                    <a class="btn btn-danger" href="{{ route('transfer.vendor.history') }}">
                        <i class="bx bx-x-circle"></i>
                    </a>
                </div>
              </form>
            </div>
        </div>
        <div class="card">

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table " id="transfer_listing_table">
                    <thead class="">
                        <tr>
                            <th>ID</th>
                            <th>From Member Name</th>
                            <th>To Member Name</th>
                            <th>Amount</th>
                            <th>Payment Mode</th>
                            <th>Description</th>
                            <th>Received Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vendor as $transfer)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $transfer->first_name }} {{ $transfer->last_name }}</td>
                                <td>{{ $transfer->name }}</td>
                                <td>{{ $transfer->amount }}</td>
                                <td>{{ $transfer->payment_mode }}</td>
                                <td>{{ $transfer->description ?? '--' }}</td>
                                <td>{{ $transfer->current_date }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="paginatestyle mt-4">
                    {{ $vendor->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>

        <p class="text-end mt-4" style="font-size: medium;">
            <b>Total Amount:</b>&nbsp;{{ $sum }}<br />&nbsp;
        </p>
    </div>

    {{-- Modal --}}
    <div id="walletsuccess" class="modal fade">
        <div class="modal-dialog modal-confirm modal-sm">
            <div class="modal-content">
                <div class="modal-header text-center">
                    <h4 class="modal-title w-100">Success</h4>
                </div>
                <hr>
                <div class="modal-body text-center">
                    <p class="success-msg"></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success btn-block" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/js/bootstrap-select.js"></script>

    <script>
        $(document).ready(function() {
            $('.selectpicker').selectpicker();

            $('#unpaid-popup').modal('hide');

            let user = '';
            let from_date = '';
            let end_date = '';

            $('#user_id, #from_date, #to_date').on('change', function() {
                user = $('#user_id').val();
                from_date = $('#from_date').val();
                end_date = $('#to_date').val();

                if (user || from_date || end_date) {
                    reset_table(from_date, end_date, user);
                }
            });

            function reset_table(from_date, to_date, user) {
                let url = '{{ route('transfer-history') }}';
                window.location.href = url + '?from_date=' + from_date + '&to_date=' + to_date + '&user_id=' + user;
            }


        });
        $('#entries').change(function() {
            var paginate = $(this).val();
            console.log('entries', paginate);
            var url = '{{ route('transfer.vendor.history') }}';
            window.location.href = url + '?paginate=' + paginate;
        });
    </script>
@endsection
