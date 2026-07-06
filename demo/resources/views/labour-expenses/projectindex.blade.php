@extends('layouts/contentNavbarLayout')

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}" />
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

    .cards {
        box-shadow: 0 2px 4px rgba(0, 0, 20, .08), 0 1px 2px rgba(0, 0, 20, .08);
        border: 0;
        border-radius: 0.5rem;
        width: 220px;
    }

    .icon-shape {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        vertical-align: middle;
    }

    .bg-light-primary {
        background-color: #b2d6e5 !important;
    }

    .icon-md {
        width: 2.5rem;
        height: 2.5rem;
        line-height: 2.5rem;
    }

    .dataTables_filter {
        text-align: center !important;
    }

    .pagination {
        justify-content: center !important;
        margin-left: -50px !important;
    }

    div.dataTables_wrapper div.dataTables_length select {
        width: 60px !important;
    }

    @media (max-width: 767px) {
        .rows {
            display: block !important;
            margin-bottom: 20px !important;
        }

        .cards {
            margin-bottom: 30px !important;
        }
    }
</style>
<style>
    .info-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 15px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        background: #faf8f8;
        animation: fadeInUp 0.5s ease-in-out both;
        height: 100%;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .info-title {
        font-size: 16px;
        color: #666;
        margin-bottom: 8px;
    }

    .info-value {
        font-size: 19px;
        font-weight: bold;
        color: #222;
    }

    @keyframes fadeInUp {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .icon-shape {
        background-color: #e6f0ff;
        padding: 8px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .card-body {
        padding: 20px;
    }
</style>

@section('title', 'List | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    @if (session()->has('message'))
        <script>
            $(function() {
                $('.success-msg').text("{{ session('message') }}");
                $('#walletsuccess').removeClass('fade');
                $('#walletsuccess').modal('show');
            });
        </script>
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
    </div>
    <div class="row g-4">
        <!-- Labour Name -->
        <div class="col-md-3">
            <div class="card info-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="info-title">Labour Name</div>
                            <div class="info-value">{{ $labour?->labour_name }}</div>
                        </div>
                        <div class="icon-shape text-primary">
                            <!-- SVG Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path d="M9.828 4a.5.5 0 0 1 .354.146l2 2A.5.5 0 0 1 12.5 6.5H3a.5.5 0 0 1 0-1h7.828z" />
                                <path d="M3 3a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H3z" />
                                <path d="M3 5v8a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V5H3z" />
                            </svg>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unpaid Amount -->
        <div class="col-md-3">
            <div class="card info-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="info-title">Unpaid Amount</div>
                            <div class="info-value">{{ $labour?->unpaid }}</div>
                        </div>
                        <div class="icon-shape text-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 2a10 10 0 1 0 .001 20.001A10 10 0 0 0 12 2zm.75 14a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0zm-.75-10a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0V7a1 1 0 0 1 1-1z" />
                            </svg>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advance Amount -->
        <div class="col-md-3">
            <div class="card info-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="info-title">Advance Amount</div>
                            <div class="info-value">{{ $labour?->advance_amt }}</div>
                        </div>
                        <div class="icon-shape text-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M17 4h-2.586l-1-1H10.586l-1 1H7a2 2 0 0 0-2 2v2h14V6a2 2 0 0 0-2-2zM5 10v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8H5zm8.25 3H13a1 1 0 0 1 0 2h-.25v.5a.75.75 0 0 1-1.5 0V15H11a1 1 0 0 1 0-2h.25v-.5a.75.75 0 0 1 1.5 0v.5z" />
                            </svg>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settle Amount -->
        <div class="col-md-3">
            <div class="card info-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="info-title">Settle Amount</div>
                            <div class="info-value">{{ $labour?->unpaid - $labour?->advance_amt }}</div>
                        </div>
                        <div class="icon-shape text-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M3 0a2 2 0 0 0-2 2v13.5a.5.5 0 0 0 .74.439L4 15.058l2.26 1.38a.5.5 0 0 0 .48 0L9 15.058l2.26 1.38a.5.5 0 0 0 .48 0L14 15.058l2.26 1.38a.5.5 0 0 0 .74-.439V2a2 2 0 0 0-2-2H3zm0 1h10a1 1 0 0 1 1 1v12.692l-1.76-1.075a.5.5 0 0 0-.48 0L10 14.942l-2.26-1.38a.5.5 0 0 0-.48 0L5 14.942l-2.26-1.38a.5.5 0 0 0-.48 0L1 14.692V2a1 1 0 0 1 1-1z" />
                                <path
                                    d="M4 4.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zM4.5 7a.5.5 0 0 0 0 1h7a.5.5 0 0 0 0-1h-7z" />
                            </svg>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Basic Bootstrap Table -->
    <div class="card" style="max-width: 1200px; margin: 40px auto; height:250px">
        <form action="{{ route('labour-expenses-project') }}" method="GET" id="submit-form">
            <input type="hidden" name="labour_id" value="{{ request('labour_id') }}">
            <input type="hidden" name="start_date" value="{{ request('start_date') }}">
            <input type="hidden" name="end_date" value="{{ request('end_date') }}">
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
                    <a class="btn btn-danger"
                        href="{{ route('labour-expenses-project', [
                            'labour_id' => request('labour_id'),
                            'start_date' => request('start_date'),
                            'end_date' => request('end_date'),
                        ]) }}">
                        <i class="bx bx-x-circle" style="font-size: 18px;"></i>
                    </a>
                    {{-- </form> --}}
                </div>

            </div>
        </form>
        <div class="table-responsive text-nowrap">

            <table class="table" id="user_listing_table">

                <thead>
                    <tr>
                        <th><input type="checkbox" id="select_all" {{ $labour_disable == 0 ? 'disabled' : '' }}></th>
                        <th>Name</th>
                        <th>Salary</th>
                        <th>Unpaid Amount</th>
                        <th>Advance Amount</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">

                    @foreach ($projects as $project)
                        <tr>
                            <td><input type="checkbox" name="project_id[]" class="days"
                                    id="{{ $project->project_id }}" {{ $project->unpaid_amt <= 0 ? 'disabled' : '' }}
                                    value="{{ $project->project_id }}" data-unpaid="{{ $project->unpaid_amt }}"></td>
                            <td><a style="text-decoration: none" href="javascript:void(0)" class="labour_details_weekly"
                                    style="cursor:pointer" data-start_week="{{ $start_date }}"
                                    data-end_week="{{ $end_date }}" data-labour_id="{{ $labour->labour_id }}"
                                    data-project_id="{{ $project->project_id }}">{{ $project->project_name }}</a></td>
                            <td>{{ $project->amount }} </td>
                            <td>{{ $project->unpaid_amt }}</td>
                            <td>{{ $project->advance_amt }}</td>
                        </tr>
                    @endforeach

                </tbody>

            </table>
            <div class="paginatestyle mt-4">
                {{ $projects->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
    <button class="btn btn-primary" id="advance_submit" data-start_week="{{ $start_date }}"
        data-end_week="{{ $end_date }}" data-labour_id = "{{ $labour?->labour_id }}"
        data-wallet="{{ Auth::user()->wallet }}" disabled>Submit</button>
    <div class="float-right" style=""><span><b>Unpaid amount:</b> <span class="total_un_amt">0</span></span></div>
    <!--/ Basic Bootstrap Table -->


    <!-- modal popup for delete role ended -->
    <div class="modal fade" id="labour_weeklypopup" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Labour Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        onclick="window.location.reload();">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="loadingsalary"></div>

                </div>
            </div>
        </div>
        <!-- modal popup for salary details -->


        <!-- modal popup for salary details -->
        <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
        <script>
            function submitform() {
                $('#submit-form').submit();
            }

            $('#select_all').on('click', function() {
                if (this.checked) {
                    var amt = 0;
                    $('.days').each(function() {
                        this.checked = true;
                        var unpaid = parseInt($(this).attr('data-unpaid'), 10);
                        amt += unpaid;

                    });
                    $('#advance_submit').prop('disabled', false);
                    $('.total_un_amt').text(amt);
                    console.log('amt', amt);
                } else {
                    $('.days').each(function() {
                        this.checked = false;
                    });
                    $('#advance_submit').prop('disabled', true);
                    $('.total_un_amt').text(0);
                }
                checkWalletAndUnpaid();
            });
            $('.days').on('click', function() {
                console.log($('.days:checked').length);

                if ($('.days:checked').length != 0) {
                    $('#advance_submit').prop('disabled', false);

                } else {
                    $('#advance_submit').prop('disabled', true);
                }
                if ($('.days:checked').length == $('.days').length) {
                    $('#select_all').prop('checked', true);

                } else {
                    $('#select_all').prop('checked', false);
                }
                var amt = 0;
                $('.days:checked').each(function() {
                    this.checked = true;
                    var unpaid = parseInt($(this).attr('data-unpaid'), 10);
                    amt += unpaid;

                });
                $('.total_un_amt').text(amt);
                checkWalletAndUnpaid();
            });
            $('.labour_details_weekly').click(function() {
                var start_date = $(this).attr('data-start_week');
                var end_date = $(this).attr('data-end_week');
                var project_id = $(this).attr('data-project_id');
                var labour_id = $(this).attr('data-labour_id');
                $('.preloader').css('display', 'block');
                $.ajax({

                    type: "get",
                    url: "{{ route('labour-expenses-labour') }}",
                    data: {
                        start_date: start_date,
                        end_date: end_date,
                        project_id: project_id,
                        labour_id: labour_id
                    },
                    dataType: 'json',
                    success: function(html) {
                        console.log(html);

                        $('.loadingsalary').html(html);
                        $('.preloader').css('display', 'none');
                        $('#labour_weeklypopup').modal('show');
                    }
                });
            });
            $('#advance_submit').click(function() {
                var val = [];
                $('.days:checked').each(function(i) {
                    val[i] = $(this).val();
                });
                var start_date = $(this).attr('data-start_week');
                var end_date = $(this).attr('data-end_week');
                var labour_id = $(this).attr('data-labour_id')
                console.log('val', val);
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: "post",
                    url: "{{ route('labour-expenses-store') }}",
                    data: {
                        project_id: val,
                        end_date: end_date,
                        start_date: start_date,
                        labour_id: labour_id
                    },
                    dataType: 'json',
                    success: function(html) {
                        console.log(html);
                        if (html == 'error') {
                            toastr.error('Insufficient Balance', {
                                timeOut: 500,
                                fadeOut: 500,
                            });
                            setTimeout(function() {
                                // Do something after 5 seconds
                                location.reload(); //reload page
                            }, 5000);
                        } else {
                            toastr.success('Unpaid Successfully', {
                                timeOut: 500,
                                fadeOut: 500,
                            });
                            setTimeout(function() {
                                // Do something after 5 seconds
                                location.reload(); //reload page
                            }, 5000);
                        }
                    }
                });
            });

            function checkWalletAndUnpaid() {
                var wallet = parseFloat($('#advance_submit').attr(
                    'data-wallet')); // Assuming wallet amount is passed as a data attribute
                let totalUnpaidAmount = 0;

                $('.days:checked').each(function() {
                    let unpaidAmount = parseFloat($(this).closest('tr').find('td:eq(3)').text().trim());
                    totalUnpaidAmount += unpaidAmount;
                });
                console.log('wallet', wallet);
                console.log('totalunpaidamount', totalUnpaidAmount);

                // If wallet is 0 or the total unpaid amount is greater than the wallet, disable submit button
                if (totalUnpaidAmount == 0 || totalUnpaidAmount > wallet) {
                    console.log('if');
                    $('#advance_submit').prop('disabled', true);
                } else {
                    console.log('else');
                    $('#advance_submit').prop('disabled', false);
                }
            }
        </script>

    @endsection
