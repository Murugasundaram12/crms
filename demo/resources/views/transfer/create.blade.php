@extends('layouts/contentNavbarLayout')

@section('title', 'Create | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Add Transfer
    </h4>
    <form name="transferSumbmit" action="{{ route('transfer.store') }}" id="transferSubmit" method="post">
        @csrf
        <div class="col-xl">
            <div class="card mb-4" style="margin-top:30px;">
                <div class="card-body">
                    <div class="row">
                        <div class="col-4">
                          <div class="mb-3">
                            <label class="form-label" for="basic-default-message">User Type</label><br>
                            <input  type="radio" class="user_type" value="0" checked id="active" name="user_type">
                            <label  class="form-label" for="active">Member</label> &nbsp;
                            <input type="radio" class="user_type" value="1" id="deactive" name="user_type">
                            <label class="form-label" for="deactive">Vendor</label>
                            <!-- <label id="gender-error" class="error" for="basic-default-gender">Gender  is required</label> -->
                          </div>
                        </div><div class="col-4">
                            <div class="mb-3">
                                <label class="form-label" for="basic-default-phone">Member Name</label>
                                <select class="form-control" name="member_id" id="member_id">
                                    <option value="">Select </option>
                                    @foreach ($member as $client)
                                        <option value="{{ $client->id }}">{{ $client->first_name }}
                                            {{ $client->last_name }}</option>
                                    @endforeach
                                </select>
                                <label id="member-error" class="error" for="basic-default-role">Member is required</label>
                            </div>
                            </div>
                    
                      <div class="col-4">

                            <div class="mb-3">
                                <label class="form-label" for="basic-default-email">Amount</label>

                                <input type="text" onkeypress="allowNumbersOnly(event)" id="amount" name="amount"
                                    class="form-control" placeholder="Enter amount" value=""
                                    oninput="amountcheck(this.value)" />
                                <label id="amount-error" class="error" for="basic-default-email">Amount is required</label>
                                <input type="hidden" class="amount-check-error" value="">
                                <label id="amount-check-error" class="error" for="basic-default-phone">Insufficient
                                    Balance</label>
                            </div>
                      </div>
                      <div class="row">
                      <div class="col-4">
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
                        <div class="col-4">
                          <div class="mb-3">
                              <label class="form-label" for="datetimepicker1">Date</label><br>
                              <input type="date" class="form-control" id="datetimepicker1" name="current_date"
                                  value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                          </div>
                      </div>
                 <div class="col-4">
                          <div class="mb-3">
                              <label for="appt">Time:</label><br>
                              <input type="time" id="appt" class="form-control" name="time"
                                  value="{{ Carbon\Carbon::now()->format('h:i:s') }}">
                          </div>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-12">

                            <div class="mb-3">
                                <label class="form-label" for="basic-default-phone">Description</label>
                                <textarea type="text" name="description" id="description" class="form-control phone-mask"  row="4"></textarea>
                            </div>
                        </div>
                       
                            <div class="text-end"> <button type="submit" class="btn btn-primary"
                                    style="margin-top: 20px;">Submit</button>
                                <button type="reset" id="resetform" class="btn btn-danger"
                                    style="background-color: red; margin-top:20px;">Reset</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
    <script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.3.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.error').addClass('hide');
        });

        function allowNumbersOnly(e) {
            var key = e.key;
            if (isNaN(key) || key === ' ' || key === null) {
                e.preventDefault();
            }
        }
        $('#transferSubmit').submit(function(e) {
            e.preventDefault();
            var client = $('#member_id').find(":selected").val();
            var amount = $('#amount').val();
            var test = $('.amount-check-error').val();
            var payment = $('#payment_mode').find(":selected").val();
            console.log('client_id', client);
            console.log('amount', amount);
            console.log('test', test);
            var clientname = false,
                amountname = false,
                paymentmode = false;
            if (client.length < 1) {
                $('#member-error').removeClass('hide');
            } else {
                $('#member-error').addClass('hide');
                clientname = true;
            }
            if (amount == "") {
                $('#amount-error').removeClass('hide');
            } else {
                $('#amount-error').addClass('hide');
                amountname = true;
            }
            if (payment.length < 1) {
                $('#payment-error').removeClass('hide');
            } else {
                $('#payment-error').addClass('hide');
                paymentmode = true;
            }
            if (clientname == true && amountname == true && paymentmode == true && (test == false || test ==
                    "false")) {
                document.getElementById("transferSubmit").submit();
            }
        });

        function amountcheck(amount) {
            console.log(amount, "amount check");
            $.ajax({
                url: "{{ route('transfer.amount-check') }}",
                data: {
                    'amount': amount
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
        $('.user_type').change(function(){
          var id = $(this).val();  
          userType(id);     
        });
        function userType(id){
          $.ajax({
                url: "{{ route('transfer.userdetail') }}",
                data: {
                    'id': id
                },
                type: 'GET',
                dataType: 'json',
                success: function(result) {
                    console.log("result", result);
                    $('#member_id').empty();
                    $('#member_id').append('<option>Select</option>');
                    if(id == 0){
                      $.each(result,function(index,item){
                    $('#member_id').append("<option value='"+item.id+"'>"+item.first_name+"</option>");
                  });
                    }else{
                      $.each(result,function(index,item){
                    $('#member_id').append("<option value='"+item.id+"'>"+item.name+"</option>");
                  });
                    }
                
                }
            });
        }
    </script>
@endsection
