@extends('layouts/contentNavbarLayout')

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">


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
    .dropdown-toggle{
  width:146px !important;
}
.bs-caret::after{
  color:#f7f7f7 !important;
  content: "";
  display:none !important;
}
</style>
@section('title', 'List | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    @if (session()->has('popup'))
        <script>
            $(function() {
                toastr.success('Advance Amount Paid Successfully', {
                    timeOut: 1000,
                    fadeOut: 1000,
                });
            });
        </script>
    @endif
    @if (session()->has('msg'))
        <div class="alert alert-danger">
            {{ session()->get('msg') }}
        </div>
    @endif
    <div style="margin-top: -35px;">
        <h4 class="fw-bold py-3 mb-4" style="color:black;">
            <span class="fw-light">Labour </span>
        </h4>
    </div>

    <!-- Basic Bootstrap Table -->
    <div class="card" style="max-width: 1200px; top:-29px;">
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
                <a class="btn btn-danger" href="{{ route('labour-expenses-advance') }}">
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
                        <th>ID</th>
                        <th>Name</th>
                        <th>Job Title</th>
                        <th>Salary</th>
                        <th>Labour Role</th>
                        <th>Advance Amount</th>
                        @can('labour expenses-labour advance amount edit')
                            <th>Action</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">

                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td><a style="text-decoration: none" href="javascript:void(0)" class="labour_details_totally"
                                    style="cursor:pointer" data-labour_id="{{ $user->id }}">{{ $user->name }}</a></td>
                            <td>{{ $user->job_title }}</td>
                            <td>{{ $user->salary }}</td>
                            <td>{{ App\Models\LabourRole::where('id', $user->labour_role)->pluck('name')->first() }}
                            <td>{{ $user->advance_amt }}</td>
                            @can('labour expenses-labour advance amount edit')
                                <td>
                                    <a class="" href="{{ route('advanceform', $user->id) }}"><i class="bi bi-pencil-square"
                                            style="font-size:24px;color:green"></i></a>
                                </td>
                            @endcan
                        </tr>
                    @endforeach

                </tbody>
            </table>
            <div class="paginatestyle mt-4">
              {{ $users->links('pagination::bootstrap-5') }}
          </div>
        </div>
    </div>
    <!--/ Basic Bootstrap Table -->




    <!--- modal popup for delete role started--->



    <!-- Modal -->
    <div class="modal fade" id="myModal" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirmation</h4>
                </div>
                <hr>
                <div class="modal-body">
                    <p style="text-align: center;">Are you sure want to delete this?</p>
                </div>
                <hr>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary yes-delete" data-dismiss="modal">Yes</button>
                    <button type="button" class="btn btn-danger no-delete" data-dismiss="modal">No</button>
                </div>
            </div>

        </div>
    </div>
    <div id="walletsuccess" class="modal fade" data-backdrop="static" data-keyboard="false">
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


    <!-- modal popup for delete role ended -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" data-bs-backdrop="static" data-bs-keyboard="false"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Salary Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="loadingsalary"></div>
                </div>
                {{-- <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div> --}}
            </div>
        </div>
    </div>
    <!-- modal popup for salary details -->
    <div class="modal fade" id="exampleModal-1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" data-bs-backdrop="static" data-bs-keyboard="false"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Labour Expenses Details</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.reload();">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="loadingexpenses"></div>
                </div>
                {{-- <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div> --}}
            </div>
        </div>
    </div>

    <!-- modal popup for salary details -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>

        $("document").ready(function() {
            var user = $('#user_id').val();
            console.log()
            var userid;
            setTimeout(function() {
                $("div.alert").remove();
            }, 5000); // 5 sec
            $("#user_listing_table").on("click", ".deleteUser", function() {

                userid = $(this).attr('data-id');

                $("#myModal").modal('show');
            });
            $('.no-delete').click(function() {

                $("#myModal").modal('hide');
            });
            $('.yes-delete').click(function() {
                console.log('userid', userid);
                $("#myModal").modal('hide');
                var url = '{{ route('labour-delete', ':id') }}';
                url1 = url.replace(':id', userid);
                window.location.href = url1;
            });
            $("#user_listing_table").on("click", "#click_salary", function() {
                var user = $(this).attr('data-user');
                console.log($(this).attr('data-user'));
                $.ajax
                $('#exampleModal').modal('show');
            });
        });
        $('.labour_details_totally').click(function() {

            var labour_id = $(this).attr('data-labour_id');
            console.log(labour_id);
            $('.preloader').css('display', 'block');
            $.ajax({

                type: "get",
                url: "{{ route('labour-total-records') }}",
                data: {
                    labour_id: labour_id
                },
                dataType: 'json',
                success: function(html) {
                    console.log(html);

                    $('.loadingexpenses').html(html);
                    $('.preloader').css('display', 'none');
                    $('#exampleModal-1').modal('show');
                }
            });
        });

    </script>

@endsection
