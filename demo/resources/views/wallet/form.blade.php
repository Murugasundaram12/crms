@extends('layouts/contentNavbarLayout')

@section('title', 'Create | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Add Wallet</span>
</h4>
<form name="walletSubmit" action="{{ route('wallet.store') }}" id="walletSubmit" method="post">
    @csrf
    <div class="col-xl">
        <div class="card mb-4" style="margin-top:30px;">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <input type="hidden" id="wallet-balance" value="{{ Auth::user()->wallet }}"
                            <label class="form-label" for="client">Client Name</label>
                            <select class="form-control" name="client_id" id="client_id">
                                <option value="">Select client</option>
                                @foreach($client as $client)
                                <option value="{{ $client->id }}">{{ $client->first_name }} {{ $client->last_name }}</option>
                                @endforeach
                            </select>
                            <label id="client-error" class="error hide" for="client">Client is required</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="project_id">Project Name</label>
                            <select class="form-control" name="project_id" id="project_id">
                                <option value="">Select project</option>
                                @foreach($project as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                            <label id="project-error" class="error hide" for="project_id">Project is required</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="amount">Amount</label>
                            <input type="text" onkeypress="allowNumbersOnly(event)" id="amount" name="amount" class="form-control" placeholder="Enter amount" value="" />
                            <label id="amount-error" class="error hide" for="amount">Amount is required</label>
                            <label id="amount-sufficient-error" class="error hide" for="amount">Amount is insufficient</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="payment_mode">Payment Mode</label>
                            <select class="form-control" name="payment_mode" id="payment_mode">
                                <option value="">Select payment</option>
                                @foreach($payment as $payment)
                                <option value="{{ $payment->id }}">{{ $payment->name }}</option>
                                @endforeach
                            </select>
                            <label id="payment-error" class="error hide" for="payment_mode">Payment mode is required</label>
                        </div>
                        <div class="mb-3">
                          <label class="form-label" for="basic-default-message">Type</label><br>
                          <input  type="radio" class="gender" value="0" id="male" name="transfer_type">
                          <label  class="form-label" for="male">Add</label> &nbsp;
                          <input type="radio" class="gender" value="1" id="female" name="transfer_type">
                          <label class="form-label" for="female">Subtract</label> &nbsp;
                          <br/>
                          <label id="gender-error" class="error" for="basic-default-gender">Type  is required</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label" for="description">Description</label>
                            <textarea type="text" name="description" id="description" class="form-control phone-mask" style="height:28px"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="datetimepicker1">Date</label><br>
                            <input type="date" class="form-control" id="datetimepicker1" name="current_date" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label for="appt">Time:</label><br>
                            <input type="time" id="appt" class="form-control" name="time" value="{{ Carbon\Carbon::now()->format('h:i:s') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="stage">Stages </label>
                            <select class="form-control" name="stage_id" id="stage_id">
                                <option value="">Select Stage</option>
                                @foreach($stages as $stage)
                                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                            <!-- <label id="client-error" class="error hide" for="client_id">Client is required</label> -->
                        </div>
                        <center>
                            <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Submit</button>
                            <button type="reset" id="resetform" class="btn btn-danger" style="background-color: red; margin-top:20px;">Reset</button>
                        </center>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript">
    $(document).ready(function () {
        // Initialize the datepicker


        // Hide error messages initially
        $('.error').addClass('hide');

        // Form submission and validation
        $('#walletSubmit').submit(function (e) {
            e.preventDefault();
            var client = $('#client_id').val();
            var project = $('#project_id').val();
            var amount = $('#amount').val();
            var payment = $('#payment_mode').val();
            var type = $('.gender:checked').length;
            var type_id = $('.gender:checked').val();
            var balance = $('#wallet-balance').val();
            console.log(type_id);
            console.log(balance);
            var clientname = false,projectname = false, amountname = false,paymentname = false, typename = false,typeidname = false;
console.log(type);
            // Validation
            if (client === '') {
                $('#client-error').removeClass('hide');
            } else {
                $('#client-error').addClass('hide');
                clientname = true;
            }
            if (project === '') {
                $('#project-error').removeClass('hide');
            } else {
                $('#project-error').addClass('hide');
                projectname = true;
            }
            if (amount === '') {
                $('#amount-error').removeClass('hide');
            } else {
                $('#amount-error').addClass('hide');
                amountname = true;
            }
            if (payment === '') {
                $('#payment-error').removeClass('hide');
            } else {
                $('#payment-error').addClass('hide');
                paymentname = true;
            }
            if (type < 1) {
                $('#gender-error').removeClass('hide');
            } else {
                $('#gender-error').addClass('hide');
                typename = true;
            }
            if(type_id == 1){
                if(parseInt(amount) <= parseInt(balance)){
                    typeidname = true;
                    $('#amount-sufficient-error').addClass('hide');
                }else{
                    $('#amount-sufficient-error').removeClass('hide');
                }
            }else{
                typeidname = true;
                    $('#amount-sufficient-error').addClass('hide');
            }

            // Submit the form if all fields are valid
            if (clientname == true && projectname == true && amountname == true && paymentname == true && typename == true && typeidname == true) {
                document.getElementById("walletSubmit").submit();
            }
        });
    });
    function allowNumbersOnly(e) {
    var code = (e.which) ? e.which : e.keyCode;
    if (code > 31 && (code < 48 || code > 57)) {
        e.preventDefault();
    }
  }
</script>
@endsection

