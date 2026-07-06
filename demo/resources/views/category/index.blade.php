@extends('layouts/contentNavbarLayout')

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
    integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

    .btn-assign {
        background: linear-gradient(45deg, #22c55e, #16a34a);
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
    }

    .btn-assign:hover {
        transform: translateY(-2px);
        background: linear-gradient(45deg, #16a34a, #22c55e);
    }

    .btn-assign i.feather-arrow-right {
        transition: transform 0.3s ease;
    }

    .btn-assign:hover i.feather-arrow-right {
        transform: translateX(4px);
    }

    .select2.select2-container.select2-container--default {
        width: 100% !important;
    }
</style>
@section('content')
    @if (session()->has('message'))
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

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
    <div class="category-header d-flex justify-content-between align-items-center mt-4 mb-3">
        <h4 class="fw-bold" style="font-size:16px; color:#111827; margin:0;">
            <span class="fw-light">Category</span>
        </h4>

        <div class="d-flex align-items-center gap-3">
            @can('category-create')
                <button class="btn-assign updatecategory">
                    {{-- <i class="bi bi-tag-fill"></i> --}}
                    Assign Category
                    <i class="bi bi-arrow-right-circle"></i>
                </button>

                <ul class="nav nav-pills flex-column flex-md-row mb-0">
                    <li class="nav-item">
                        <a class="nav-link active add-category" href="#">
                            <i class="bx bxs-category me-1"></i> Add Category
                        </a>
                    </li>
                </ul>
            @endcan
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
                    <a class="btn btn-danger" href="{{ route('category-index') }}">
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
                        <th>Main Category</th>
                        <th>Name</th>
                        @canany(['category-edit', 'category-delete'])
                            <th>Action</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if (count($categorys) > 0)
                        @foreach ($categorys as $category)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $category?->maincategory?->name }}</td>
                                <td>{{ $category->name }}</td>
                                @canany(['category-edit', 'category-delete'])
                                    <td>
                                        @if ($category->name != 'salary')
                                            @can(['category-edit'])
                                                <a class="editcategory" data-id="{{ $category->id }}" href="#"><i
                                                        class="bi bi-pencil-square" style="font-size:24px;color:green"></i></a>
                                            @endcan
                                            @can('category-delete')
                                                @if (!in_array($category->id, $categorynot))
                                                    <a data-toggle="modal" href="javascript:void(0)" data-id="{{ $category->id }}"
                                                        class="deleteCategory"><i class="bi bi-trash"
                                                            style="font-size:24px; color:red"></i> </a><br />
                                                @endif
                                            @endcan
                                        @endif
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
                    <form name="createCategory" action="{{ route('category.store') }}" method="post" id="createCategory">
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
                    <a href="{{ route('category-index') }}" class="btn btn-secondary ms-2">Cancel</a>
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
                        <div class="row mb-3 align-items-center">
                            <label class="col-sm-3 col-form-label text-end" for="main_category_id">Main Category</label>
                            <div class="col-sm-8">
                                <select class="form-control" name="main_category_id" id="main_category_id">
                                    <option value="">Select Main Category</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                            <label class="col-sm-3 col-form-label text-end" for="editmainname">Name</label>
                            <div class="col-sm-8">
                                <input type="text" name="name" id="editmainname" class="form-control"
                                    placeholder="Enter Category Name">
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
                    <a href="{{ route('category-index') }}" class="btn btn-secondary ms-2">Cancel</a>
                    <button type="submit" class="btn btn-primary " data-dismiss="modal">Update</button>

                </div>
                </form>
            </div>

        </div>
    </div>

    <div class="modal fade" id="updateModal" role="dialog" data-bs-keyboard="false" data-bs-backdrop="static">
        <div class="modal-dialog ">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body">
                    <form name="updateCategory" action="" method="post" id="updateCategory">
                        @csrf
                      
                        <div class="row mb-3 align-items-center">
                            <label class="col-sm-3 col-form-label text-end" for="main_category_id">Main Category</label>
                            <div class="col-sm-8">
                                <select class="form-control" name="main_category_id" id="main_category_id">
                                    <option value="">Select Main Category</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3 align-items-center">
                            <label class="col-sm-3 col-form-label text-end" for="main_category_id">Category</label>
                            <div class="col-sm-8">
                                <select class="js-example-basic-multiple form-control" name="category_id[]"
                                    id="category_id" multiple="multiple">
                                    <option value="">Select Category</option>
                                </select>
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
                    <a href="{{ route('category-index') }}" class="btn btn-secondary ms-2">Cancel</a>
                    <button type="submit" class="btn btn-primary " data-dismiss="modal">Update</button>

                </div>
                </form>
            </div>

        </div>
    </div>
    <!-- modal popup for delete role ended -->

    <!-- jQuery must be first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
    <!-- Then DataTables and validation (optional) -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.js-example-basic-multiple').select2({
                placeholder: 'Select an option',
                width: '100%',
                dropdownParent: $("#updateModal")
            });


        });

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
                var url = '{{ route('category-delete', ':id') }}';
                url1 = url.replace(':id', categoryid);
                window.location.href = url1;
            });
        });
        $('form[name="createCategory"]').validate({
            rules: {

                name: {
                    required: true,
                }
            },
            messages: {

                name: {
                    required: "Enter the category name"
                }
            },
            submitHandler: function(form) {
                form.submit();
            }
        });
        $('.add-category').click(function() {
            $('.modal-title').text('Add Category');
            $('#addModal').modal('show');
        });
        $('.editcategory').click(function() {
            var id = $(this).attr('data-id');
            var url = "{{ route('category-edit', ':id') }}".replace(':id', id);

            $.ajax({
                type: 'GET',
                url: url,
                dataType: 'json',
                success: function(response) {
                    console.log('response edit', response);

                    // Set the form action
                    var updateUrl = "{{ route('category.update', ':id') }}".replace(':id', response
                        .category.id);
                    $('#editCategory').attr('action', updateUrl);

                    // Set the category name


                    // Populate main category dropdown
                    var select = $('#main_category_id');
                    select.empty();
                    select.append('<option value="">Select Main Category</option>');

                    $.each(response.maincategory, function(index, item) {
                        var selected = (item.id == response.category.main_category_id) ?
                            'selected' : '';
                        select.append('<option value="' + item.id + '" ' + selected + '>' + item
                            .name + '</option>');
                    });
 $('#editmainname').val(response.category.name);
                    // Show modal
                    $('.modal-title').text('Edit Category');
                    $('#editModal').modal('show');
                }
            });
        });

        $('form[name="editCategory"]').validate({
            rules: {
                main_category_id: {
                    required: true,
                },
                name: {
                    required: true,
                }
            },
            messages: {
                main_category_id: {
                    required: "Choose the main category",
                },
                name: {
                    required: "Enter the category name"
                }
            },
            submitHandler: function(form) {
                form.submit();
            }
        });
        $(document).on('click','.updatecategory',function() {
            var url = "{{ route('updateCategory') }}";

            $.ajax({
                type: 'GET',
                url: url,
                dataType: 'json',
                success: function(response) {
                    console.log('response edit', response);

                    // Set the form action dynamically
                    var updateUrl = "{{ route('updateCategoryStatus') }}";
                    $('#updateCategory').attr('action', updateUrl);

                    // Populate main category dropdown
                  var mainSelect = $('#updateModal').find('#main_category_id');
            mainSelect.empty().append('<option value="">Select Main Category</option>');

            // ✅ Append all main categories
            $.each(response.main_category, function (index, item) {
                mainSelect.append('<option value="' + item.id + '">' + item.name + '</option>');
            });

            // ✅ Fill category (for your custom multiple select or normal one)
            var catSelect = $('#updateModal').find('#category_id');
            catSelect.empty().append('<option value="">Select Category</option>');

            $.each(response.category, function (index, cat) {
                catSelect.append('<option value="' + cat.id + '">' + cat.name + '</option>');
            });

                    // Show modal
                    $('.modal-title').text('Update Category');
                    $('#updateModal').modal('show');
                }
            });
        });


        $('form[name="updateCategory"]').validate({
            rules: {
                main_category_id: {
                    required: true,
                },
                "category_id[]": {
                    required: true,
                }
            },
            messages: {
                main_category_id: {
                    required: "Choose the main category",
                },
                "category_id[]": {
                    required: "Choose the category name"
                }
            },
            submitHandler: function(form) {
                form.submit();
            }
        });
    </script>

@endsection

