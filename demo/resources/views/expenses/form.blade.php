@extends('layouts/contentNavbarLayout')

@section('title', 'Unpaid Expenses | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
    <!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->

    <!-- Basic Layout & Basic with Icons -->
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Unpaid Expenses
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
                    <form name="UnpaidSubmit" action="{{ route('unpaidex.store') }}" id="UnpaidSubmit" method="post">
                        @csrf
                        <input type="hidden" name="user_id" id="user_id" value="{{ Auth::user()->id }}">
                        <div class="col-xl">
                            <div class="card mb-4" style="margin-top:30px;">
                                <div class="card-body">
                                    <div class="row">
                                        <input type="hidden" value="{{ $unpaid->id }}" name="expense_id">
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-email">Paid amount</label>

                                            <input type="text" oninput="amountcheck(this.value)"
                                                onkeypress="allowNumbersOnly(event)" id="amount" name="unpaid_amt"
                                                class="form-control" placeholder="Enter amount"
                                                value="{{ $unpaid->unpaid_amt }}" />
                                            <label id="amount-error" class="error" for="basic-default-email">Amount is
                                                required</label>
                                            <input type="hidden" class="amount-check-error" value=""><br>
                                            <label id="amount-check-error" class="error"
                                                for="basic-default-phone">Insufficient Balance</label>
                                        </div>



                                        <div class="mb-3">
                                            <label class="form-label" for="datetimepicker1">Date</label><br>
                                            <input type="date" class="form-control" id="datetimepicker1"
                                                name="current_date" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="appt">Time:</label><br>
                                            <input type="time" id="appt" class="form-control" name="time"
                                                value="{{ Carbon\Carbon::now()->format('h:i:s') }}">
                                        </div>
                                        <center> <button type="submit" class="btn btn-primary"
                                                style="margin-top: 20px;">Submit</button>
                                            <button type="reset" id="resetform" class="btn btn-danger"
                                                style="background-color: red; margin-top:20px;">Reset</button>
                                        </center>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Basic with Icons -->
    </div>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>

    <script>
        $(document).ready(function() {
            $('.error').addClass('hide');
            var amount = $('#amount').val();
            amountcheck(amount);
        });

        function allowNumbersOnly(e) {
            var code = (e.which) ? e.which : e.keyCode;
            if (code > 31 && (code < 48 || code > 57)) {
                e.preventDefault();
            }
        }
        $('#UnpaidSubmit').submit(function(e) {
            e.preventDefault();

            var amount = $('#amount').val();
            var test = $('.amount-check-error').val();

            console.log('amount', amount);
            var amountname = false;

            if (amount == "") {
                $('#amount-error').removeClass('hide');
            } else {
                $('#amount-error').addClass('hide');
                amountname = true;
            }
            if (amountname == true && (test == false || test == "false")) {
                document.getElementById("UnpaidSubmit").submit();
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
    </script>
@endsection

