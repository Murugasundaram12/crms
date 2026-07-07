@extends('layouts/contentNavbarLayout')

{{-- Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400&display=swap" rel="stylesheet">

{{-- Bootstrap 4 + DataTables + Icons --}}
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    @media only screen and (max-width:320px) {
        .aa {
            display: inline !important;
        }
    }

    .dataTables_wrapper {
        font-family: 'Source Sans 3', sans-serif;
        font-size: 13px;
        clear: both;
    }

    .dataTables_length select {
        width: 120px;
    }

    .table-responsive {
        margin-top: 5px;
        margin-left: 5px;
        overflow: visible !important;
        /* Ensure dropdown is visible */
    }

    .action-menu {
        position: relative;
        display: inline-block;
        font-size: 20px;
        cursor: pointer;
    }

    .action-menu .menu {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: #fff;
        border-radius: 6px;
        min-width: 160px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        padding: 6px 0;
        list-style: none;
        margin: 6px 0 0;
        z-index: 1000;
        overflow: hidden;
    }

    .action-menu .menu li a {
        display: block;
        padding: 10px 15px;
        text-decoration: none;
        color: #333;
        font-size: 14px;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .action-menu .menu li a:hover {
        background: #f0f4ff;
        color: #0056b3;
    }

    .action-menu .menu li a.text-danger:hover {
        background: #ffe5e5;
        color: #d60000;
    }

    /* show on hover */
    .action-menu:hover .menu {
        display: block;
    }
</style>
<style>
    .switch {
        margin: 0;
        cursor: pointer;
    }

    .switch>span {
        line-height: 20px;
        margin: 0 0 0 4px;
        vertical-align: top;
    }

    .switch input {
        display: none;
    }

    .switch input+div {
        width: 40px;
        height: 20px;
        border: 1px solid #D1D7E3;
        background: #D1D7E3;
        border-radius: 10px;
        vertical-align: top;
        position: relative;
        display: inline-block;
        user-select: none;
        transition: all .4s ease;
    }

    .switch input+div:before {
        content: '';
        float: left;
        width: 14px;
        height: 14px;
        background: #fff;
        pointer-events: none;
        margin-top: 2px;
        margin-left: 2px;
        border-radius: inherit;
        transition: all .4s ease 0s;
    }

    .switch input+div:after {
        content: '';
        left: -1px;
        top: -1px;
        width: 20px;
        height: 20px;
        border: 3px solid transparent;
        border-top-color: #5D9BFB;
        border-radius: 50%;
        position: absolute;
        opacity: 0;
    }

    .switch input:checked+div {
        background: #9EC4FF;
        border: 1px solid #5D9BFB;
    }

    .switch input:checked+div:before {
        transform: translate(20px, 0);
    }

    .switch.load input+div {
        width: 20px;
        margin: 0 10px;
    }

    .switch.load input+div:after {
        opacity: 1;
        animation: rotate .9s infinite linear;
        animation-delay: .2s;
    }

    @keyframes rotate {

        0%,
        15% {
            transform: rotate(0deg);
        }

        50% {
            transform: rotate(290deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .switch:hover input:checked+div {
        background: #5D9BFB;
    }
</style>

@section('title', 'List | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')

    {{-- Success / Error Alerts --}}
    @if (session()->has('message'))
        <script>
            $(function() {
                toastr.success('{{ session('message') }}', {
                    timeOut: 1000,
                    fadeOut: 1000
                });
            });
        </script>
    @endif
    @if (session()->has('msg'))
        <script>
            $(function() {
                toastr.error('{{ session('msg') }}', {
                    timeOut: 1000,
                    fadeOut: 1000
                });
            });
        </script>
    @endif

    <div style="margin-top: 30px;">
        <h4 class="fw-bold py-3 mb-4" style="margin-top:-49px;font-size:16px;color:black">
            <span class="fw-light">Member </span>
        </h4>
        <div class="row" style="position:absolute; top:90px; right:50px ">
            <div class="col-md-12">
                @can('user-create')
                    <ul class="nav nav-pills flex-column flex-md-row mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" href="{{ route('user-create') }}">
                                <i class="bx bx-user me-1"></i> Add Member
                            </a>
                        </li>
                    </ul>
                @endcan
            </div>
        </div>
    </div>

    <!-- Basic Bootstrap Table -->
    <div class="card" style="max-width: 1200px; margin: 40px auto;top:-26px;">
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
                    <span class="me-2">Search:</span>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                        class="form-control me-2" style="width: 200px;">
                    <button class="btn btn-primary me-2 client_search" type="submit">
                        <i class="bx bx-search" style="font-size: 18px;"></i>
                    </button>
                    <a class="btn btn-danger" href="{{ route('user-index') }}">
                        <i class="bx bx-x-circle" style="font-size: 18px;"></i>
                    </a>
                </div>
            </div>
        </form>

        <div class="table-responsive text-nowrap" style="width:99%;">
            <table class="table" id="user_listing_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Profile</th>
                        <th>Name</th>
                        <th>Job Title</th>
                        <th>Email</th>
                        <th>Phone</th>
                        @role('Admin')
                            <th>Wallet</th>
                            <th>Unpaid Amount</th>
                            <th>Status</th>
                        @endrole

                        @canany(['user-view', 'user-edit', 'user-delete'])
                            <th>Action</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if (count($users) > 0)
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>
                                    @if ($user->image != '' || $user->image != null)
                                        <img class="rounded float-left" src="public/images/{{ $user->image }}"
                                            width="30px">
                                    @else
                                        <img class="rounded float-left"
                                            src="{{ asset('assets/img/icons/gray-user-profile-icon.png') }}"
                                            width="30px">
                                    @endif
                                </td>
                                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                <td>{{ $user->job_title }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone }}</td>
                                @role('Admin')
                                    <td>{{ $user->wallet }}</td>
                                    <td>{{ App\Models\Expenses::where('user_id', $user->id)?->sum('unpaid_amt') }}</td>
                                    <td> <label class="switch" data-id="{{ $user->id }}">
                                            <input type="checkbox" {{ $user->status == 1 ? 'checked' : '' }}>
                                            <div></div>
                                           
                                        </label>
                                    </td>
                                @endrole
                                @canany(['user-view', 'user-edit', 'user-delete'])
                                    <td>
                                        <div class="action-menu">
                                            <i class="bi bi-three-dots-vertical"></i>
                                            <ul class="menu">
                                                @can('user-view')
                                                    <li><a href="{{ route('user-show', $user->id) }}">View</a></li>
                                                @endcan
                                                @can('user-edit')
                                                    <li><a href="{{ route('user-edit', $user->id) }}">Edit</a></li>
                                                @endcan
                                                @role('Admin')
                                                <li><a href="#" class="change-password"
                                                        data-id="{{ $user->id }}">Change Password</a></li>
                                               @endrole
                                                @can('user-delete')
                                                    <li><a href="#" class=" deleteUser"
                                                            data-id="{{ $user->id }}">Delete</a></li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </td>
                                @endcanany
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="text-center">No data found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <div class="paginatestyle mt-4">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="myModal" role="dialog">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirmation</h4>
                </div>
                <div class="modal-body">
                    <p class="text-center">Are you sure want to delete this?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary yes-delete" data-dismiss="modal">Yes</button>
                    <button type="button" class="btn btn-danger no-delete" data-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>


    <!--- change password -->
    <div class="modal fade" id="changepassword" tabindex="-1" role="dialog" data-bs-keyboard="false"
        data-bs-backdrop="static">
        <div class="modal-dialog" role="document">
            <form id="changePasswordForm" method="POST" action="">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Change Password</h4>
                        <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-control"
                                minlength="6">
                        </div>

                        <div class="form-group mt-2">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                                minlength="6">
                            <small id="password-error" class="text-danger" style="display:none;">Passwords do not
                                match</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    {{-- Scripts --}}
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js"></script>
    <script>
        function submitform() {
            $('#submit-form').submit();
        }

        $(document).ready(function() {
            var userid;

            setTimeout(function() {
                $("div.alert").remove();
            }, 5000);

            $("#user_listing_table").on("click", ".deleteUser", function() {
                userid = $(this).data('id');
                $("#myModal").modal('show');
            });

            $('.no-delete').click(function() {
                $("#myModal").modal('hide');
            });

            $('.yes-delete').click(function() {
                var url = '{{ route('user-delete', ':id') }}';
                var url1 = url.replace(':id', userid);
                window.location.href = url1;
            });
        });
        $(document).on('click', '.change-password', function() {
            var user_id = $(this).attr('data-id');
            var url = "{{ route('admin.changepassword', ':id') }}";
            var url1 = url.replace(':id', user_id);
            console.log('url1', url1);
            $('#confirm_password').val('');
            $('#new_password').val('');
            $('#changePasswordForm').attr('action', url1);
            $('#changepassword').modal('show');
        });
        $('#changePasswordForm').validate({
            rules: {
                new_password: {
                    required: true,
                    minlength: 8
                },
                confirm_password: {
                    required: true,
                    minlength: 8,
                    equalTo: new_password
                }
            },
            messages: {
                new_password: {
                    required: 'New password is required',
                    minlength: 'Enter minimum 8 characters'
                },
                confirm_password: {
                    required: 'Confirm Password is required',
                    minlength: 'Enter minimum 8 characters',
                    equalTo: 'Passwords do not match'

                }
            }

        })
    </script>
    <script>
$(document).ready(function() {
    $('.switch').on('click', function(e) {
        e.preventDefault();

        var self = $(this); // keep context
        var user_id = self.attr('data-id'); // get id
        var input = self.find('input');
        var newStatus = input.is(':checked') ? 0 : 1; // toggle

        self.addClass('load'); // animation

        $.ajax({
            type: 'GET', // or POST
            url: "{{ route('admin.changestatus') }}",
            data: {
                id: user_id,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                console.log(response);

                setTimeout(function() {
                    self.removeClass('load');
                    input.prop('checked', newStatus ? true : false);
                }, 200); // shorter delay for smoother experience
            },
            error: function(xhr, status, error) {
                console.error(error);
                self.removeClass('load');
                alert('Status update failed');
            }
        });
    });
});


    </script>

@endsection
