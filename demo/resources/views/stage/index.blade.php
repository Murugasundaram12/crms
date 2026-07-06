@extends('layouts/contentNavbarLayout')

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">


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

    .dropdown-toggle {
        width: 146px !important;
    }

    .bs-caret::after {
        color: #f7f7f7 !important;
        content: "";
        display: none !important;
    }

    td,
    th {
        padding: 5px;
        /* Reduce cell padding */
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
    <div style="margin-top: 30px;">
        <h4 class="fw-bold py-3 mb-4" style="margin-top:-49px;font-size:16px;color:black;">
            <span class="fw-light">Stage
        </h4>
        <div class="row" style="position:absolute; top:90px; right:50px ">
            <div class="col-md-12">
                @can('stage-create')
                    <ul class="nav nav-pills flex-column flex-md-row mb-3">
                        <li class="nav-item"><a class="nav-link active" href="{{ route('stage-create') }}"> Add Stage</a></li>

                    </ul>
                @endcan
            </div>
        </div>
    </div>
    <!-- Basic Bootstrap Table -->
    <div class="card" style="max-width: 1200px; margin: 40px auto;top:-32px;">
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
                <a class="btn btn-danger" href="{{ route('stage-index') }}">
                    <i class="bx bx-x-circle" style="font-size: 18px;"></i>
                </a>
                {{-- </form> --}}
            </div>
    
        </div>
    </form>
        <div class="table-responsive text-nowrap" style="width: 99%;">
            <table class="table" id="stage_listing_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        @canany(['stage-edit', 'stage-delete'])
                            <th>Action</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @if(count($stages) > 0)
                    @foreach ($stages as $stage)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>{{ $stage->name }}</td>
                            @canany(['stage-edit', 'stage-delete'])
                                <td>
                                    @can(['stage-edit'])
                                        <a class="" href="{{ route('stage-edit', $stage->id) }}"><i class="bi bi-pencil-square"
                                                style="font-size:24px;color:green"></i></a>
                                    @endcan
                                    @can('stage-delete')
                                        @if (!in_array($stage->id, $wallet))
                                            <a data-toggle="modal" href="javascript:void(0)" data-id="{{ $stage->id }}"
                                                class="deleteStage"><i class="bi bi-trash" style="font-size:24px; color:red"></i>
                                            </a><br />
                                        @endif
                                    @endcan
                                </td>
                            @endcanany
                        </tr>
                    @endforeach
                    @else
                    <tr><td><center>No data found.</center></td></tr>
                    @endif

                </tbody>
            </table>
            <div class="paginatestyle mt-4">
              {{ $stages->links('pagination::bootstrap-5') }}
          </div>
        </div>
    </div>

    <!--/ Basic Bootstrap Table -->



    <!--- modal popup for delete role started--->



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


    <!-- modal popup for delete role ended -->

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
          function submitform() {
            $('#submit-form').submit();
        }
        $("document").ready(function() {
            var categoryid;
            setTimeout(function() {
                $("div.alert").remove();
            }, 5000); // 5 secs
            $("#stage_listing_table").on("click", ".deleteStage", function() {

                categoryid = $(this).attr('data-id');
                $("#myModal").removeClass('fade');
                $("#myModal").modal('show');
            });
            $('.no-delete').click(function() {
                $("#myModal").addClass('fade');
                $("#myModal").modal('hide');
            });
            $('.yes-delete').click(function() {
                console.log('categoryid', categoryid);
                $("#myModal").modal('hide');
                var url = '{{ route('stage-delete', ':id') }}';
                url1 = url.replace(':id', categoryid);
                window.location.href = url1;
            });
        });
    </script>

@endsection
