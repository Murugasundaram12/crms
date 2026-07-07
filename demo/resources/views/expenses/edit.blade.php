@extends('layouts/contentNavbarLayout')

@section('title', 'Edit | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
    <!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->

    <!-- Basic Layout & Basic with Icons -->
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Edit Expenses
    </h4>
    <div class="row" style="position:absolute; top:150px; right:50px ">
        <div class="col-md-12">
            <ul class="nav nav-pills flex-column flex-md-row mb-3">
                <li class="nav-item"><a class="nav-link active" href="{{ route('expenses-history') }}"><i class="bx me-1"></i>
                        Back</a></li>

            </ul>
        </div>
    </div>
    <div class="row">
        <!-- Basic Layout -->
        <div class="col-xxl">
            <div class="card mb-4" style="top:30px">


                <div class="card-body">
                    <form name="editExpenses" id="editExpenses" action="{{ route('expenses.update', $expense->id) }}"
                        method="post" enctype="multipart/form-data">
                        @csrf
                        {{ method_field('PUT') }}
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3 ">
                                    <label class="form-label" for="basic-default-phone">Main Category Name</label>
                                    <div> <select class="form-control" name="main_category_id" id="main_category_id"
                                            style="width:90%">
                                            <option value="">Select main category </option>
                                            @foreach ($maincategory as $mcategory)
                                                <option value="{{ $mcategory->id }}"
                                                    {{ $mcategory->id == $expense->main_category_id ? 'selected' : '' }}>
                                                    {{ $mcategory->name }}</option>
                                            @endforeach
                                        </select></div>
                                    <label id="maincategory-error" class="error hide" for="basic-default-role">Main category
                                        is
                                        required</label>
                                </div>
                                <div class="mb-3" id="here">
                                    <label class="form-label" for="basic-default-phone">Category Name</label>
                                    <select class="form-control" name="category_id" id="category_id" style="width:90%">
                                        <option value="">Select category </option>
                                        @foreach ($category as $category)
                                            <option value="{{ $category->id }}"
                                                {{ $category->id == $expense->category_id ? 'selected' : '' }}>
                                                {{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="bi bi-plus d-flex justify-content-end"
                                        style="margin-top: -35px;margin-bottom: 40px; font-size: 28; color: blueviolet; "
                                        id="new_category_click"></span>

                                    <span class="d-flex addcategory"
                                        style="margin-top:-15px; cursor:pointer; display:none !important"
                                        data-toggle="tooltip" data-placement="top" title="Add category"><input
                                            type="text" class="form-control" style="width:70%" name="new_category"
                                            id="new_category" placeholder="Enter category">
                                        <p class="savecategory" style="cursor:pointer" data-toggle="tooltip"
                                            data-placement="top" title="save"><img
                                                src="{{ asset('assets/img/icons/save.png') }}" alt="slack" class="me-3"
                                                height="40"></p>
                                    </span>
                                    <label id="showing_error_msg" class="error hide">Category name already exists.</label>
                                    <label id="showing_success_msg" class="success hide" style="color:green">Category added
                                        successfully.</label>
                                    <label id="category-error" class="error" for="basic-default-role">Category is
                                        required</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-phone">Project Name</label>
                                    <select class="form-control" name="project_id" id="project_id">
                                        <option value="">Select project </option>
                                        @foreach ($project as $project)
                                            <option value="{{ $project->id }}"
                                                {{ $project->id == $expense->project_id ? 'selected' : '' }}>
                                                {{ $project->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-email">amount</label>

                                    <input type="text" id="amount" name="amount" class="form-control"
                                        placeholder="Enter amount" onkeypress="allowNumbersOnly(event)"
                                        value="{{ $expense->amount }}" />
                                    <input type="hidden" name="user_id" id="user_id" value="{{ $expense->user_id }}">
                                    <p style="color:blue">wallet balance : {{ $expense->wallet }}</p>
                                    <label id="amount-error" class="error" for="basic-default-email">Amount is
                                        required</label>
                                    <input type="hidden" class="amount-check-error" value=""><br>

                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-phone">Payment Mode</label>
                                    <select class="form-control" name="payment_mode" id="payment_mode">
                                        <option value="">Select payment </option>
                                        @foreach ($payment as $payment)
                                            <option value="{{ $payment->id }}"
                                                {{ $payment->id == $expense->payment_mode ? 'selected' : '' }}>
                                                {{ $payment->name }}</option>
                                        @endforeach
                                    </select>
                                    <label id="payment-error" class="error" for="basic-default-role">Payment mode is
                                        required</label>
                                </div>

                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-phone">Image</label>
                                    <input type="file" name="image" class="form-control" placeholder="image"
                                        accept="application/pdf,image/*">
                                    @if ($expense->image != '' || $expense->image != null)
                                        <input type="hidden" name="image_status" value="{{ $expense->image }}">
                                        <?php $extension = explode('.', $expense->image); ?>
                                        @if ($extension[1] == 'pdf')
                                            <embed src="{{ url('images/' . $expense->image) }}" />
                                        @else
                                            <img src="{{ url('images/' . $expense->image) }}" width="30px">
                                            <span class="deleteImage" style=" cursor: pointer;"
                                                data-id="{{ $expense->id }}"><img
                                                    src="{{ asset('assets/img/icons/cancel.png') }}"
                                                    width="10px" /></span>
                                        @endif
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-phone">Description</label>
                                    <textarea type="text" name="description" id="description" class="form-control phone-mask">{{ $expense->description }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-message">Paid Amount</label><br>
                                    <input type="text" class="form-control" value="{{ $expense->paid_amt }}"
                                        id="paid_amt" name="paid_amt" oninput="amountcheck(this.value)"
                                        onkeypress="allowNumbersOnly(event)">
                                    <label id="paid-error" class="error" for="basic-default-amt">Paid amount is
                                        required.</label>
                                    <label id="amount-check-error" class="error" for="basic-default-phone">Insufficient
                                        Balance</label>
                                </div>
                                <!-- <div class="mb-3">
                    <label class="form-label" for="basic-default-message">Unpaid Amount</label><br>
                    <input  type="text" class="form-control" value="" id="unpaid_amt" name="unpaid_amt" readonly>
                  </div> -->

                                <div class="mb-3">
                                    <label class="form-label" for="datetimepicker1">Date</label><br>
                                    <input type="date" class="form-control" id="datetimepicker1" name="current_date"
                                        value="{{ \Carbon\Carbon::parse($expense->current_date)->format('Y-m-d') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="appt">Time:</label><br>
                                    <input type="time" id="appt" class="form-control" name="time"
                                        value="<?php echo date('H:i', strtotime($expense->current_date)); ?>">

                                </div>
                            </div>

                        </div>

                        <div class="row">
                            <div class="col-sm-10 text-right">
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Basic with Icons -->
    </div>

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


    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.error').addClass('hide');
            $('.success').addClass('hide');
            $('.addcategory').hide();
            maincategory();
        });

        function allowNumbersOnly(e) {
            var code = (e.which) ? e.which : e.keyCode;
            if (code > 31 && (code < 48 || code > 57)) {
                e.preventDefault();
            }
        }

        $('.deleteImage').click(function() {
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
            var url = '{{ route('image-delete', ':id') }}';
            url1 = url.replace(':id', roleid);
            window.location.href = url1;
        });
        $('#new_category_click').click(function() {
            $('.addcategory').css('display', 'block');
        });
        $('.savecategory').click(function() {
            var maincategory = $('#main_category_id').find(":selected").val();
            var category = $('#new_category').val();
            var categoryname = false;
            if (category == "") {
                $('#category-error').removeClass('hide');
            } else {
                $('#category-error').addClass('hide');
                categoryname = true;
            }
            if (categoryname == true) {
                $.ajax({
                    url: "{{ route('new-category') }}",
                    data: {
                        'name': category,
                        'main_id': maincategory
                    },
                    type: 'GET',
                    dataType: 'json',
                    success: function(result) {
                        console.log("result", result);
                        if (result == 'true' || result == true) {
                            $('.addcategory').attr("style", "display: none !important");
                            $('#showing_error_msg').addClass('hide');
                            $('#showing_success_msg').removeClass('hide');

                            $("#here").load(window.location.href + " #here");

                        }
                        if (result == 'false' || result == false) {
                            $('#showing_error_msg').removeClass('hide');
                        }
                    }
                });
            }
        });

        //   function unpaid_amt(){
        //     var amount = $('#amount').val();
        //     var paid = $('#paid_amt').val();
        //   var unpaid = parseInt(amount) - parseInt(paid);
        //   console.log('unpaid',unpaid);
        //   if(parseInt(amount) == null && parseInt(paid) == null){
        //   if(unpaid != "null" && unpaid != null && unpaid != '' && (unpaid > 0 || unpaid == 0) ){
        //     console.log('unpaid',unpaid);
        //     $('#unpaid_amt').val(unpaid);
        //   }
        // }
        // }
        $('#editExpenses').submit(function(e) {
            e.preventDefault();
            var main = $('#main_category_id').find(':selected').val();
            var category = $('#category_id').find(":selected").val();
            var payment = $('#payment_mode').find(":selected").val();
            var amount = $('#amount').val();
            var test = $('.amount-check-error').val();
            var paid = $('#paid_amt').val();
            // var unpaid = parseInt(amount) - parseInt(paid);
            // console.log('unpaid',unpaid);
            console.log('category_id', category);
            console.log('amount', amount);
            console.log('paid', paid);

            var clientname = false,
                amountname = false,
                pamtname = false,
                paymentmode = false,
                paidname = false,
                mainname = false;
            if (main == '') {
                $('#maincategory-error').removeClass('hide');
            } else {
                $('#maincategory-error').addClass('hide');
                mainname = true;
            }
            if (category.length < 1) {
                $('#category-error').removeClass('hide');
            } else {
                $('#category-error').addClass('hide');
                clientname = true;
            }
            if (paid == "") {
                $('#paid-error').removeClass('hide');
            } else {
                $('#paid-error').addClass('hide');
                paidname = true;
            }
            if (payment.length < 1) {
                $('#payment-error').removeClass('hide');
            } else {
                $('#payment-error').addClass('hide');
                paymentmode = true;
            }
            if (amount == "") {
                $('#amount-error').removeClass('hide');
            } else {
                $('#amount-error').addClass('hide');
                $('#paid_amt-error').addClass('hide');
                amountname = true;
                pamtname = true;
            }
            console.log(clientname);
            console.log(amountname);
            console.log(test);
            console.log(paymentmode);
            console.log(paidname);
            if (clientname == true && amountname == true && (test == false || test == "false") && paymentmode ==
                true && paidname == true && mainname == true) {
                document.getElementById("editExpenses").submit();
            }
        });

        function amountcheck(amount) {
            console.log(amount, "amount check");
            var user_id = $('#user_id').val();
            $.ajax({
                url: "{{ route('amount-check') }}",
                data: {
                    'amount': amount,
                    'user_id': user_id
                },
                type: 'GET',
                dataType: 'json',
                success: function(result) {
                    console.log("result", result);
                    $('.amount-check-error').val(result);
                    if (result == true)
                        $('#amount-check-error').removeClass('hide');
                    else
                        $('#amount-check-error').addClass('hide');
                }
            });
        }
        $('#main_category_id').change(function() {
            maincategory();
        })

        function maincategory() {
            var main_id = $('#main_category_id').find(':selected').val();
            var selected = "<?php echo $expense->category_id; ?>";
            console.log(selected, 'selected');
            $.ajax({
                type: 'get',
                url: "{{ route('expenses.category') }}",
                data: {
                    main_id: main_id
                },
                dataType: 'json',
                success: function(response) {
                    console.log(response);
                    $('#category_id').empty();
                    $('#category_id').append('<option value="">Select category</option>');
                    $.each(response, function(index, category) {
                        var isSelected = (category.id == selected) ? 'selected' : '';
                        $('#category_id').append('<option value="' + category.id + '" ' + isSelected +
                            '>' + category.name + '</option>');
                    });
                }
            });
        }
    </script>
@endsection
