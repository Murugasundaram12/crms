@extends('layouts/contentNavbarLayout')

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
@section('title', 'List | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')
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

    .dropdown-toggle {
        width: 146px !important;
    }

    .bs-caret::after {
        color: #f7f7f7 !important;
        content: "";
        display: none !important;
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

    /* Toggle container */
    .toggle {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 26px;
    }

    /* Hide checkbox */
    .toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    /* Slider background */
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: background-color 0.4s, box-shadow 0.4s;
        border-radius: 26px;
    }

    /* Circle knob */
    .slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: transform 0.4s ease, background-color 0.4s;
        border-radius: 50%;
    }

    /* ON state */
    .toggle input:checked+.slider {
        background-color: #22c55e;
        box-shadow: 0 0 8px #22c55e80;
        /* glow effect */
    }

    /* Move knob when ON */
    .toggle input:checked+.slider:before {
        transform: translateX(24px);
        background-color: #fff;
    }

    /* Optional hover effect */
    .toggle:hover .slider {
        box-shadow: 0 0 6px rgba(0, 0, 0, 0.2);
    }
</style>
@section('content')

    <div style="margin-top: 30px;">
        <h4 class="fw-bold py-3 mb-4" style="margin-top:-49px;font-size:16px;color:black">
            <span class="fw-light">Main Category</span>
        </h4>
        <div class="row" style="position:absolute; top:90px; right:50px ">
            <div class="col-md-12">
                @can('maincategory-create')
                    <ul class="nav nav-pills flex-column flex-md-row mb-3">
                        <li class="nav-item"><a class="nav-link active add-category" href="#"><i
                                    class="bx bxs-category me-1"></i> Add Main Category</a></li>

                    </ul>
                @endcan
            </div>
        </div>
    </div>
    <!-- Basic Bootstrap Table -->
    <div class="card" style="max-width: 1200px; margin: 46px auto;top:-32px;">
        <!-- <h5 class="card-header">Table Basic</h5> -->
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
                    <a class="btn btn-danger" href="{{ route('maincategory.index') }}">
                        <i class="bx bx-x-circle" style="font-size: 18px;"></i>
                    </a>
                    {{-- </form> --}}
                </div>

            </div>
        </form>
        <div class="table-responsive text-nowrap" style="width: 99%;">
            <table class="table" id="category_listing_table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Status</th>
                        @canany(['maincategory-edit', 'maincategory-delete'])
                            <th>Action</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if (count($categorys) > 0)
                        @foreach ($categorys as $category)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $category->name }}</td>
                                <td>
                                    <label class="toggle">
                                        <input type="checkbox" {{ $category->status == 1 ? "checked" : '' }} class="toggle-status" data-id="{{ $category->id }}">
                                        <span class="slider"></span>
                                    </label>
                                </td>


                                @canany(['maincategory-edit', 'maincategory-delete'])
                                    <td>

                                        @can(['maincategory-edit'])
                                            <a class="editcategory" href="#" data-id="{{ $category->id }}"><i
                                                    class="bi bi-pencil-square" style="font-size:24px;color:green"></i></a>
                                        @endcan
                                        @can('maincategory-delete')
                                            @if ($category->category->count() == 0)
                                                <a data-toggle="modal" href="javascript:void(0)" data-id="{{ $category->id }}"
                                                    class="deleteCategory"><i class="bi bi-trash"
                                                        style="font-size:24px; color:red"></i> </a><br />
                                            @endif
                                            <form id="delete-form" method="POST">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endcan

                                    </td>
                                @endcanany
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5">
                                <center>No data found.</center>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <div class="paginatestyle mt-4">
                {{ $categorys->links('pagination::bootstrap-5') }}
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

    <div class="modal fade" id="addModal" role="dialog" data-bs-keyboard="false" data-bs-backdrop="static">
        <div class="modal-dialog ">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body">
                    <form name="createCategory" action="{{ route('maincategory.store') }}" method="post"
                        id="createCategory">
                        @csrf
                        <div class="row mb-3 ">
                            <label class="col-sm-3 col-form-label text-end" for="basic-default-name">Name</label>
                            <div class="col-sm-7">
                                <input type="text" name="name" autofocus class="form-control"
                                    id="basic-default-name" placeholder="Enter Main Category" />
                            </div>
                        </div>

                        {{-- <div class="row justify-content-center">
                            <div class="col-sm-10 text-center">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="{{ route('maincategory.index') }}" class="btn btn-secondary ms-2">Back</a>
                            </div>
                        </div> --}}

                </div>
                <div class="modal-footer">
                    <a href="{{ route('maincategory.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                    <button type="submit" class="btn btn-primary " data-dismiss="modal">Save</button>

                </div>
                </form>
            </div>

        </div>
    </div>
    <div class="modal fade" id="editModal" role="dialog" data-bs-keyboard="false" data-bs-backdrop="static">
        <div class="modal-dialog ">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body">
                    <form name="editCategory" action="" method="post" id="editCategory">
                        @csrf
                        @method('PUT')
                        <div class="row mb-3 ">
                            <label class="col-sm-3 col-form-label text-end" for="basic-default-name">Name</label>
                            <div class="col-sm-7">
                                <input type="text" name="name" id="editmainname" autofocus class="form-control"
                                    id="basic-default-name" placeholder="Enter Main Category" />
                            </div>
                        </div>

                        {{-- <div class="row justify-content-center">
                            <div class="col-sm-10 text-center">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="{{ route('maincategory.index') }}" class="btn btn-secondary ms-2">Back</a>
                            </div>
                        </div> --}}

                </div>
                <div class="modal-footer">
                    <a href="{{ route('maincategory.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                    <button type="submit" class="btn btn-primary " data-dismiss="modal">Update</button>

                </div>
                </form>
            </div>

        </div>
    </div>


    <!-- modal popup for delete role ended -->

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
    <script>
        function submitform() {
            $('#submit-form').submit();
        }
        $("document").ready(function() {
            var categoryid;
            setTimeout(function() {
                $("div.alert").remove();
            }, 5000); // 5 secs
            $('#category_listing_table').on('click', '.deleteCategory', function() {

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
                $('#delete-form').attr('action', '{{ route('maincategory.destroy', ':id') }}'.replace(
                    ':id', categoryid));
                $('#delete-form').submit();
            });
        });
        $('.add-category').click(function() {
            $('.modal-title').text('Add Main Category');
            $('#addModal').modal('show');
        });
        $('.editcategory').click(function() {
            var id = $(this).attr('data-id');
            var url = "{{ route('maincategory.edit', ':id') }}";
            var url1 = url.replace(':id', id);
            $.ajax({
                type: 'get',
                url: url1,
                dataType: 'json',
                success: function(response) {
                    console.log('response edit', response);
                    var url11 = "{{ route('maincategory.update', ':id') }}";
                    var url12 = url11.replace(':id', response.id);
                    $('#editCategory').attr('action', url12);
                    $('#editmainname').val(response.name);
                    $('.modal-title').text('Edit Main Category');
                    $('#editModal').modal('show');
                }
            })

        });
        $('#createCategory').validate({
            rules: {
                name: {
                    required: true
                }
            },
            messages: {
                name: {
                    required: "Enter the main category name"
                }
            },
            submitHandler: function(form) {
                form.submit();
            }
        });
        $('form[name="editCategory"]').validate({
            rules: {
                name: {
                    required: true,
                }
            },
            messages: {
                name: {
                    required: "Enter the main category name"
                }
            },
            submitHandler: function(form) {
                form.submit();
            }
        });
        $(document).on('change','.toggle-status',function(){
            let id = $(this).attr('data-id');
            let status =$(this).is(':checked') ? 1 : 0; 
            //alert(status);
            $.ajax({
                type:'get',
                url: "{{ route('admin.main.status') }}",
                data:{id:id, status:status},
                dataType:'json',
                success: function(response){
                    console.log(response);
                    toastr.success('Status Updated Successfully', {
            timeOut: 1000,
            fadeOut: 1000,
        });
                }
            })
        })
    </script>

@endsection

