@extends('layouts/contentNavbarLayout')

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<!-- DATATABLE -->


<style>
    @media only screen and (max-width:320px) {
        .aa {
            display: inline !important;
        }
    }

    .dataTables_wrapper {

        font-size: 13px;
        clear: both;

    }

    .dropdown-toggle {
        width: 146px !important;
    }

    .bs-caret::after {
        color: #f7f7f7 !important;
        content: "";
        display: none !important;
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

    .paginatestyle .text-muted {
        margin-top: 10px;
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
        <h4 class="fw-bold py-3 mb-4" style="margin-top:-49px;font-size:16px;">
            <span class="fw-light" style="color: orange;">User /</span> Roles
        </h4>
        <div class="row" style="position:absolute; top:90px; right:50px ">
            @can('role-create')
                <div class="col-md-12">
                    <ul class="nav nav-pills flex-column flex-md-row mb-3">
                        <li class="nav-item"><a class="nav-link active" href="{{ route('roles.create') }}"><i
                                    class="bx bx-user me-1"></i> Add Role</a></li>
                    </ul>
                </div>
            @endcan
        </div>
        <!-- Basic Bootstrap Table -->

        <div class="card" style="max-width: 1200px; margin: 40px auto;top:-32px;">
            <div class="d-flex justify-content-between align-items-center m-2">
               
                <div class="d-flex justify-content-center align-items-center">
                    <span class="me-2">Showing:</span>
                    <select class="form-control me-2" style="width:50%" id="showing_result">
                        <option value="15" {{ $paginate == 15 ? 'selected' : '' }}>15</option>
                        <option value="50" {{ $paginate == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $paginate == 100 ? 'selected' : '' }}>100</option>
                    </select>
                  
                </div>
                <div class="d-flex justify-content-center align-items-center">
                    {{-- <form method="post" action="{{ route('roles.index') }}" class="d-flex align-items-center">  --}}
                    <span class="me-2">Search:</span>
                    <input type="text" name="search" id="search" value="{{ $search }}" class="form-control me-2"
                        style="width: 200px;">

                    <button class="btn btn-primary me-2 role_search" type="submit">
                        <i class="bx bx-search" style="font-size: 18px;"></i>
                    </button>
                    <a class="btn btn-danger" href="{{ route('roles.index') }}">
                        <i class="bx bx-x-circle" style="font-size: 18px;"></i>
                    </a>
                    {{-- </form> --}}
                </div>
            </div>


            <div class="table-responsive text-nowrap" style="width: 99%;">
                <table class="table" id="role_listing_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            @canany(['role-edit', 'role-delete'])
                                <th>Action</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">

                        @foreach ($roles as $role)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $role->name }}</td>
                                @canany(['role-edit', 'role-delete'])
                                    <td>
                                        @can('role-edit')
                                            <a class="" href="{{ route('roles.edit', $role->id) }}"><i
                                                    class="bi bi-pencil-square" style="font-size:24px;color:green"></i></a>
                                        @endcan
                                        @can('role-delete')
                                            @if (!in_array($role->id, $user))
                                                <a data-toggle="modal" href="javascript:void(0)" data-id="{{ $role->id }}"
                                                    class="deleteRole"><i class="bi bi-trash"
                                                        style="font-size:24px; color:red"></i></a><br />
                                            @endif
                                        @endcan
                                    </td>
                                @endcanany
                            </tr>
                        @endforeach

                    </tbody>
                </table>
                <div class="paginatestyle  mt-4" style="align-items:center;justify-content:center">
                    {{ $roles->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>



        <!-- Modal Popup for Delete Role -->
        <div class="modal fade" id="myModal" role="dialog">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Confirmation</h4>
                    </div>
                    <div class="modal-body">
                        <p style="text-align: center;">Are you sure you want to delete this?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary yes-delete" data-dismiss="modal">Yes</button>
                        <button type="button" class="btn btn-danger no-delete" data-dismiss="modal">No</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Popup for Delete Role Ended -->

        <!-- Include jQuery from CDN -->

        <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script> -->

        <!-- DATATABLE -->
        <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

        <script>
        

            $(document).ready(function() {
                var roleid;
                setTimeout(function() {
                    $("div.alert").remove();
                }, 5000); // 5 secs
                $("#role_listing_table").on("click", ".deleteRole", function() {

                    roleid = $(this).attr('data-id');
                    $("#myModal").removeClass('fade');
                    $("#myModal").modal('show');
                });

                $('.no-delete').click(function() {
                    $("#myModal").addClass('fade');
                    $("#myModal").modal('hide');
                });

                $('.yes-delete').click(function() {
                    console.log('roleid', roleid);
                    $("#myModal").modal('hide');
                    var url = '{{ route('roles-delete', ':id') }}';
                    url1 = url.replace(':id', roleid);
                    window.location.href = url1;
                });
            });
            $('.role_search').click(function() {
                var search = $('#search').val();
                var url = "{{ route('roles.index') }}";
                window.location.href = url + '?search=' + search;
            });
            $('#showing_result').change(function() {
                var paginate = $(this).find(':selected').val();
                var url = "{{ route('roles.index') }}";
                window.location.href = url + '?paginate=' + paginate;
            });
        </script>
    @endsection
