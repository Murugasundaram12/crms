@extends('layouts/contentNavbarLayout')

@section('title', 'Create | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.6.3/css/bootstrap-select.min.css" />

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    @if (session()->has('expenses-popup'))
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

        <script>
            $(function() {
                toastr.success('{{ session('expenses-popup') }}', {
                    timeOut: 1000,
                    fadeOut: 1000,
                });
            });
        </script>
    @endif
    <!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->

    <!-- Basic Layout & Basic with Icons -->
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Add Expenses
    </h4>
    <div class="row">
        <!-- Basic Layout -->
        <div class="col-xxl">
            <div class="card mb-4">


                <div class="card-body">
                    <form name="createExpenses" id="createExpenses" action="{{ route('expenses.store') }}" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="user_id" id="user_id" value="{{ Auth::user()->id }}">
                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3 " >
                                    <label class="form-label" for="basic-default-phone">Main Category Name</label>
                                    <div> <select class="form-control" name="main_category_id" id="main_category_id"
                                            style="width:90%">
                                            <option value="">Select main category </option>
                                            @foreach ($maincategory as $mcategory)
                                                <option value="{{ $mcategory->id }}">{{ $mcategory->name }}</option>
                                            @endforeach
                                        </select></div>
                                         <label id="maincategory-error" class="error hide" for="basic-default-role">Main category is
                                        required</label>
                                </div>
                                <div class="mb-3" id="here">
                                    <label class="form-label" for="basic-default-phone">Category Name</label>
                                    <div> <select class="form-control" name="category_id" id="category_id"
                                            style="width:90%">
                                            <option value="">Select category </option>
                                            {{-- @foreach ($category as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach --}}
                                        </select></div>
                                    <span class="bi bi-plus d-flex justify-content-end"
                                        style="margin-top: -35px;margin-bottom: 40px; font-size: 28; color: blueviolet;"
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
                                    <label id="category-error" class="error hide" for="basic-default-role">Category is
                                        required</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-phone">Project Name</label>
                                    <select class="form-control" name="project_id" id="project_id">
                                        <option value="">Select project </option>
                                        @foreach ($project as $project)
                                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-email">amount</label>

                                    <input type="text" id="amount" name="amount" class="form-control"
                                        placeholder="Enter amount" value="" onkeypress="allowNumbersOnly(event)" />
                                    <label id="amount-error" class="error" for="basic-default-email">Amount is
                                        required</label>
                                    <input type="hidden" class="amount-check-error" value=""><br>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-phone">Payment Mode</label>
                                    <select class="form-control" name="payment_mode" id="payment_mode">
                                        <option value="">Select payment </option>
                                        @foreach ($payment as $payment)
                                            <option value="{{ $payment->id }}">{{ $payment->name }}</option>
                                        @endforeach
                                    </select>
                                    <label id="payment-error" class="error" for="basic-default-role">Payment mode is
                                        required</label>
                                </div>


                            </div>
                            <div class="col-6">

                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-phone">Image</label>
                                    <input type="file" name="image" class="form-control"
                                        accept="application/pdf,image/*" placeholder="image">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-phone">Description</label>
                                    <textarea type="text" name="description" id="description" class="form-control phone-mask" style="height:28px"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="basic-default-message">Paid Amount</label><br>
                                    <input type="text" class="form-control" value=""
                                        oninput="amountcheck(this.value)" id="paid_amt" name="paid_amt"
                                        onkeypress="allowNumbersOnly(event)">
                                    <label id="paid-error" class="error" for="basic-default-amt">Paid amount is
                                        required.</label>
                                    <label id="amount-check-error" class="error" for="basic-default-phone">Insufficient
                                        Balance</label>

                                    <!-- <label id="paid_amt-error" class="error" for="basic-default-amt">Paid amount must be less than amount.</label> -->
                                </div>
                                <!-- <div class="mb-3">
                    <label class="form-label" for="basic-default-message">Unpaid Amount</label><br>
                    <input  type="text" class="form-control" value="" id="unpaid_amt" name="unpaid_amt" readonly>
                  </div> -->

                                <div class="mb-3">
                                    <label class="form-label" for="datetimepicker1">Date</label><br>
                                    <input type="date" class="form-control" id="datetimepicker1" name="current_date"
                                        value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                                </div>
                                <div class="mb-3">
                                    <label for="appt">Time:</label><br>
                                    <input type="time" id="appt" class="form-control" name="time"
                                        value="{{ Carbon\Carbon::now()->format('h:i:s') }}">
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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.6.3/js/bootstrap-select.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.error').addClass('hide');
            $('.success').addClass('hide');
            $('.addcategory').hide();

        });
        $('#project_id').select2({
            placeholder: "Select project",
            allowClear: true,
            width: '100%',
        });
        $('#category_id').select2({
            placeholder: "Select category",
            allowClear: true,
            width: '100%',
        });
  $('#main_category_id').select2({
            placeholder: "Select main category",
            allowClear: true,
            width: '100%',
        });
        function allowNumbersOnly(e) {
            var key = e.key;
            if (isNaN(key) || key === ' ' || key === null) {
                e.preventDefault();
            }
        }
        $('#new_category_click').click(function() {
            $('.addcategory').css('display', 'block');
        });
        $('.savecategory').click(function() {
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
                        'name': category
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
        $('#createExpenses').submit(function(e) {
            e.preventDefault();
            var main_id = $('#main_category_id').find(":selected").val();
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
                paidname = false,main_name = false;
            if(main_id.length < 1){
              $('#maincategory-error').removeClass('hide');
            }else{
               $('#maincategory-error').addClass('hide');
               main_name = true;
            }
            if (category.length < 1) {
                $('#category-error').removeClass('hide');
            } else {
                $('#category-error').addClass('hide');
                clientname = true;
            }
            if (payment.length < 1) {
                $('#payment-error').removeClass('hide');
            } else {
                $('#payment-error').addClass('hide');
                paymentmode = true;
            }
            if (paid == "") {
                $('#paid-error').removeClass('hide');
            } else {
                $('#paid-error').addClass('hide');
                paidname = true;
            }
            if (amount == "") {
                $('#amount-error').removeClass('hide');
            }
            //  else if(parseInt(paid) < parseInt(amount)){
            //     console.log(amount,paid);
            //     $('#paid_amt-error').addClass('hide');
            //     pamtname = true;
            //     amountname = true;

            //   }
            //   else if(parseInt(paid) > parseInt(amount)){
            //     console.log(amount,paid);
            //       $('#paid_amt-error').removeClass('hide');
            //   }
            else {
                $('#amount-error').addClass('hide');
                $('#paid_amt-error').addClass('hide');
                amountname = true;
                pamtname = true;
            }

            if (clientname == true && amountname == true && (test == false || test == "false") && paymentmode ==
                true && paidname == true && main_name == true) {
                document.getElementById("createExpenses").submit();
            }
        });

        function amountcheck(amount) {
            var user_id = $('#user_id').val();
            console.log(amount, "amount check");
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
        $('#main_category_id').change(function(){
          var main_id = $(this).val();
          $.ajax({
            type: 'get',
            url: "{{ route('expenses.category') }}",
            data: {main_id:main_id},
            dataType:'json',
            success:function(response){
              console.log(response);
              $('#category_id').empty();
              $('#category_id').append('<option value="">Select category</option>');
              $.each(response, function (index, category) {
                $('#category_id').append('<option value="'+category.id+'">'+category.name+'</option>');
               // $('#category_id').append(option);
            });
            }
          })
        })
    </script>
@endsection

