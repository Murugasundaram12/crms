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

.modal.fade .modal-dialog {
  transform: translateY(-100px); /* Start above the screen */
  opacity: 0;
  transition: transform 0.3s ease-out, opacity 0.3s ease-out;
}

.modal.fade.show .modal-dialog {
  transform: translateY(0); /* Move to original position */
  opacity: 1;
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

    <div style="margin-top: -31px;">
        <h4 class="fw-bold py-3 mb-4" style="color: black;">
            <span class="fw-light">Vendor </span>
        </h4>
    </div>

    <!-- Basic Bootstrap Table -->
    <div class="card" style="max-width: 1200px;top:-28px;">
        <form>
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

                    <button class="btn btn-primary me-2 " type="submit">
                        <i class="bx bx-search" style="font-size: 18px;"></i>
                    </button>
                    <a class="btn btn-danger" href="{{ route('vendor-expenses-advance-history') }}">
                        <i class="bx bx-x-circle" style="font-size: 18px;"></i>
                    </a>
                    {{-- </form> --}}
                </div>

            </div>
        </form>
        <!-- <h5 class="card-header">Table Basic</h5> -->
        <div class="table-responsive text-nowrap">
            <table class="table" id="user_datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone Number</th>
                        <th>Advance Amount</th>
                        @can('vendor expenses-vendor advance amount edit')
                            <th>Action</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">

                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->address }}</td>
                            <td>{{ $user->phone }}</td>
                            <td>{{ $user->advance_amt }}</td>
                            @can('vendor expenses-vendor advance amount edit')
                                <td>
                                    <a class="" href="{{ route('vendor.vendor-history', $user->id) }}"
                                        data-toggle="tooltip" data-placement="bottom" title="History"><i
                                            class="bi bi-clock-history"></i></a>
                                @if($user->advance_amt > 0)
                                    <a class="withdraw" href="#" data-id ="{{ $user->id }}"
                                        data-toggle="tooltip" data-placement="bottom" title="Amount reduction">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" class="bi bi-currency-exchange" viewBox="0 0 16 16">
                                            <path
                                                d="M0 5a5 5 0 0 0 4.027 4.905 6.5 6.5 0 0 1 .544-2.073C3.695 7.536 3.132 6.864 3 5.91h-.5v-.426h.466V5.05q-.001-.07.004-.135H2.5v-.427h.511C3.236 3.24 4.213 2.5 5.681 2.5c.316 0 .59.031.819.085v.733a3.5 3.5 0 0 0-.815-.082c-.919 0-1.538.466-1.734 1.252h1.917v.427h-1.98q-.004.07-.003.147v.422h1.983v.427H3.93c.118.602.468 1.03 1.005 1.229a6.5 6.5 0 0 1 4.97-3.113A5.002 5.002 0 0 0 0 5m16 5.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0m-7.75 1.322c.069.835.746 1.485 1.964 1.562V14h.54v-.62c1.259-.086 1.996-.74 1.996-1.69 0-.865-.563-1.31-1.57-1.54l-.426-.1V8.374c.54.06.884.347.966.745h.948c-.07-.804-.779-1.433-1.914-1.502V7h-.54v.629c-1.076.103-1.808.732-1.808 1.622 0 .787.544 1.288 1.45 1.493l.358.085v1.78c-.554-.08-.92-.376-1.003-.787zm1.96-1.895c-.532-.12-.82-.364-.82-.732 0-.41.311-.719.824-.809v1.54h-.005zm.622 1.044c.645.145.943.38.943.796 0 .474-.37.8-1.02.86v-1.674z" />
                                        </svg>
                                    </a>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @endforeach

                </tbody>
            </table>
            <div class="paginatestyle  mt-4" style="align-items:center;justify-content:center">
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
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
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

    <!-- modal popup for withdraw -->
<div class="modal fade" id="staticBackdrop1" tabindex="-1" aria-labelledby="varyingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="varyingModalLabel">Amount Reduction</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
      </div>
      <div class="load-form">
      
       
     
      </div>
    </div>
  </div>
</div>
    <!-- modal popup for withdraw -->

    <!-- modal popup for salary details -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            var data = new DataTable('#user_listing_table', {
                "lengthMenu": [15, 25, 50, 100],
                processing: true,

            });
        });
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
        $('#user_datatable').on('click','.withdraw',function(){
            var id = $(this).attr('data-id');
               $.ajax({
                    url: "{{ route('vendor-withdraw') }}",
                    data: {
                        'id': id,
                    },
                    type: 'GET',
                    dataType: 'json',
                    success: function(result) {
                      console.log("result", result);
                      $('.load-form').html(result);
                    $('#staticBackdrop1').modal('show');
                    }
                });
           
        });
    </script>

@endsection
