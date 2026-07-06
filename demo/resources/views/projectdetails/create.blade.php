@extends('layouts/contentNavbarLayout')

@section('title', 'Create | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
<!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->

<!-- Basic Layout & Basic with Icons -->
<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Add Project Details
</h4>
        <div class="row" style="position:absolute; top:150px; right:50px;  ">
  <div class="col-md-12">
        <ul class="nav nav-pills flex-column flex-md-row mb-3">
      <li class="nav-item"><a class="nav-link active" href="{{route('project-index')}}"><i class="bx me-1"></i> Back </a></li>

    </ul>
  </div></div>

<div class="row">
<form name="createProject" action="{{route('project.store')}}" id="createProject" method="post" >
            @csrf
  <div class="col-xl">
    <div class="card mb-4" style="margin-top:30px;">
     

      <div class="card-body">
        <div class="row">
            <div class="col-6"><div class="mb-3">
            <label class="form-label" for="basic-default-fullname">Project Name</label>
            <input type="text" id="name" name="name" class="form-control" id="basic-default-fullname" placeholder="Enter project name" />
            <!-- onkeydown="return /[a-z, ]/i.test(event.key)"
                 onblur="if (this.value == '') {this.value = '';}"
                onfocus="if (this.value == '') {this.value = '';}"  -->
                <label id="name-error" class="error" for="basic-default-first_name">Project Name is required</label>
          </div>
          <div class="mb-3">
            <label class="form-label" for="basic-default-phone">Client</label>
            <select class="form-control" name="client_id" id="client_id">
            <option value="">Select client </option>
            @foreach($client as $client)
            <option value="{{$client->id}}">{{$client->first_name}} {{$client->last_name}}</option>
            @endforeach
            </select>
            <label id="client-error" class="error" for="basic-default-role">Client is required</label>
          </div>
          <!-- <div class="mb-3">
            <label class="form-label" for="basic-default-company">Advanced Amount</label>
            <input type="text" id="advance_amt" name="advance_amt" class="form-control" id="basic-default-company" onkeydown="allowNumbersOnly(this.event)" placeholder="Enter advanced amount" />
          <label id="last-error" class="error" for="basic-default-last_name">last Name is required</label>
          </div> -->


            <!-- <div class="mb-3">
          <label class="form-label" for="basic-default-phone">Payment Mode</label>
            <select class="form-control" name="payment_mode" id="payment_mode">
            <option value="">Select payment </option>
            @foreach($payment as $payment)
            <option value="{{$payment->id}}">{{$payment->name}}</option>
            @endforeach
            </select>
            <label id="payment-error" class="error" for="basic-default-role">Payment mode is required</label>
            </div> -->
            </div>
            <div class="col-6">
            <div class="mb-3">
            <label class="form-label" for="basic-default-phone">Total Estimation</label>
            <input type="text"  name="total_amt"  class="form-control phone-mask" id="total_amt" placeholder="Enter total estimation" />
    <label id="total-error" class="error" for="basic-default-phone">Total estimation is required</label>
    <label id="amt-error" class="error" for="basic-default-amt">Total amount must be greater than advanced amount.</label>
          </div>
          <div class="mb-3">
            <label class="form-label" for="basic-default-message">Project Status</label><br>
            <input  type="radio" class="gender" value="0" checked id="active" name="project_status">
            <label  class="form-label" for="active">Active</label> &nbsp;
            <input type="radio" class="gender" value="1" id="deactive" name="project_status">
            <label class="form-label" for="deactive">De-active</label> &nbsp;
            <!-- <label id="gender-error" class="error" for="basic-default-gender">Gender  is required</label> -->
          </div>

      <center>  <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Submit</button>
      <button type="reset" id="resetform" class="btn btn-danger" style="background-color: red; margin-top:20px;">Reset</button>
    </center>
        </div>

        </div>



      </div>
    </div>
  </div>
</form>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
<script>
    $(document).ready(function(){
        $('.error').addClass('hide');
    });
    function allowNumbersOnly(e) {
    var code = (e.which) ? e.which : e.keyCode;
    if (code > 31 && (code < 48 || code > 57)) {
        e.preventDefault();
    }
  }
    $('#createProject').submit(function(e) {
    e.preventDefault();
    var name = $('#name').val();
    var client = $('#client_id').find(":selected").val();
    var total = $('#total_amt').val();
    var amt = $('#advance_amt').val();
    var payment = $('#payment_mode').find(":selected").val();
 var pname=false,pclient=false,ptotal=false,pamt=false,paymentmode=false;
    console.log('name',name);
    console.log('client',client);
    console.log('total',total);
    console.log('amt',amt);
    console.log('parseamt',parseInt(amt));

    if (name =="") {
      $('#name-error').removeClass('hide');
    }
    else{
      $('#name-error').addClass('hide');
      pname = true;
    }
    // if(payment.length < 1){
    //   $('#payment-error').removeClass('hide');
    // }
    // else{
    //   $('#payment-error').addClass('hide');
    //   paymentmode = true;
    // }
    if (total == "") {
      $('#total-error').removeClass('hide');
    }
    else if((parseInt(amt) < parseInt(total))){
      console.log(amt,total);
      $('#amt-error').addClass('hide');
      $('#total-error').addClass('hide');
      pamt = true;
      ptotal = true;

    }
    else if((parseInt(amt) > parseInt(total))){
      console.log(amt,total);
      $('#total-error').addClass('hide');
        $('#amt-error').removeClass('hide');
    }
    else{
      $('#total-error').addClass('hide');
      $('#amt-error').addClass('hide');
      ptotal = true;
      pamt = true;
    }
    if(client.length < 1){
      $('#client-error').removeClass('hide');
    }
    else{
      $('#client-error').addClass('hide');
      pclient = true;
    }
   console.log(pname);
   console.log(ptotal);
   console.log(pclient);
   console.log(pamt);
   console.log(paymentmode);
    if(pname == true && ptotal == true && pclient == true && pamt == true ){
      document.getElementById("createProject").submit();
    }
  });
//     $(document).ready(function(){
//         $('#basic-default-role-error').hide();
//     });
// $('form[name="createMember"]').validate({
//   rules: {
//     first_name:{
//         required: true,
//     },
//     last_name:{
//         required: true,

//     },
//     email: {
//       required: true,
//       email: true,
//     },
//     password: {
//       required: true,
//       minlength: 8,
//     },
//     confirm_password: {
//       required: true,
//       minlength: 8,
//       equalTo: "#password"
//     },
//     phone:{
//         required:true,
//         maxLength:10,
//     },
//     gender:{
//         required:true,
//     },
//     job_title:{
//         required:true,
//     }
//     // roles:{
//     //     require: true
//     // }

//   },
//   messages: {
//     first_name:{
//         required: "First Name is required",
//     },
//     last_name:{
//         required: "Last Name is required",

//     },
//     email: {
//       required: "Email is required",
//       email: "Enter valid email",
//     },
//     password: {
//         required: 'Password is required',
//       minlength: 'Password must be at least 8 characters long'
//     },
//     confirm_password: {
//         required: 'Confirm password is required',
//       minlength: 'Password must be at least 8 characters long',
//       equalTo : 'Confirm password must match to Password'
//     },
//     phone:{
//         required:"Phone number is required",
//         maxLength:"Enter valid phone number",
//     },
//     gender:{
//         required:"Gender is required",
//     },
//     job_title:{
//         required:"Job title is required"
//     }
// //  roles:{
// //         require: "Roles is required"
// //     }
//   },
//   submitHandler: function(form) {
//     var e = document.getElementById("roles");
//     var mobileVal = e.options[e.selectedIndex].value;
//     if(mobileVal == "") {
//         console.log('mobile',mobileVal);
//     $('#basic-default-role-error').show();
//     }
//     else{
//     form.submit();
//     }
//   }
// });
</script>
@endsection
