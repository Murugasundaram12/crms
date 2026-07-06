@extends('layouts/contentNavbarLayout')

@section('title', 'Create | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
<!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->

<!-- Basic Layout & Basic with Icons -->
<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Add Client
</h4>
        <div class="row" style="position:absolute; top:150px; right:50px;  ">
  <div class="col-md-12">
        <ul class="nav nav-pills flex-column flex-md-row mb-3">
      <li class="nav-item"><a class="nav-link active" href="{{route('client-index')}}"><i class="bx me-1"></i> Back </a></li>

    </ul>
  </div></div>

<div class="row">
<form name="createClient" action="{{route('client.store')}}" id="createClient" method="post" >
            @csrf
  <div class="col-xl">
    <div class="card mb-4" style="margin-top:30px;">
    

      <div class="card-body">
        <div class="row">
            <div class="col-6"><div class="mb-3">
            <label class="form-label" for="basic-default-fullname">First Name</label>
            <input type="text" id="first_name" name="first_name" class="form-control" id="basic-default-fullname" onkeydown="return /[a-z, ]/i.test(event.key)"
                 onblur="if (this.value == '') {this.value = '';}"
                onfocus="if (this.value == '') {this.value = '';}" placeholder="Enter First name" />
                <label id="first-error" class="error" for="basic-default-first_name">First Name is required</label>
          </div>
          <div class="mb-3">
            <label class="form-label" for="basic-default-company">Last Name</label>
            <input type="text" id="last_name" name="last_name" class="form-control" id="basic-default-company" onkeydown="return /[a-z, ]/i.test(event.key)"
            onblur="if (this.value == '') {this.value = '';}"
            onfocus="if (this.value == '') {this.value = '';}" placeholder="Enter Last name" />
            <label id="last-error" class="error" for="basic-default-last_name">last Name is required</label>
          </div>
          <div class="mb-3">
            <label class="form-label" for="basic-default-email">Email</label>

              <input  type="text" id="email" name="email" class="form-control" placeholder="Enter Email"  value=""/>
              <label id="email-error" class="error" for="basic-default-email">Email is required</label>
              <label id="email-invalid-error" class="error" for="basic-default-email">Email is invalid</label>

          </div>

</div>
            <div class="col-6">
            <div class="mb-3">
            <label class="form-label" for="basic-default-phone">Company Name</label>
            <input type="text"  name="company_name"  class="form-control phone-mask"  placeholder="Enter company name" />
    <!-- <label id="phone-error" class="error" for="basic-default-phone">Phone number is required</label> -->
          </div>
                <div class="mb-3">
            <label class="form-label" for="basic-default-phone">Phone Number</label>
            <input type="text" maxlength="10" name="phone" id="phone" class="form-control phone-mask" onkeypress="allowNumbersOnly(event)" placeholder="Enter Phone number" />
    <label id="phone-error" class="error" for="basic-default-phone">Phone number is required</label>
          </div>

          <div class="mb-3">
            <label class="form-label" for="basic-default-phone">Address</label>
            <textarea type="text" name="address"  class="form-control phone-mask" placeholder="Enter address" ></textarea>
            <!-- <label id="job_title-error" class="error" for="basic-default-job_title">Job title  is required</label> -->
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
    var key = e.key;
    if (isNaN(key) || key === ' ' || key === null) {
        e.preventDefault();
    }
}
    $('#createClient').submit(function(e) {
    e.preventDefault();
    var first_name = $('#first_name').val();
    var last_name = $('#last_name').val();
    var email = $('#email').val();
    var phone = $('#phone').val();
 var fname=false,lname=false,emailname=false,phonename=false;
    console.log('first',first_name);
    console.log('last',last_name);
    console.log('email',email);
    console.log('phone',phone);

    if (first_name =="") {
      $('#first-error').removeClass('hide');
    }
    else{
      $('#first-error').addClass('hide');
      fname = true;
    }
    if (last_name == "") {
      $('#last-error').removeClass('hide');
    }
    else{
      $('#last-error').addClass('hide');
      lname = true;
    }
    if (email == "") {
      console.log('email length',email);
      $('#email-error').removeClass('hide');
    } else  {
      var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if(!regex.test(email)) {
        $('#email-error').addClass('hide');
        $('#email-invalid-error').removeClass('hide');
      }
      else{
        $('#email-error').addClass('hide');
        $('#email-invalid-error').addClass('hide');
        emailname = true;
      }
    }
    if(phone.length < 1){
      $('#phone-error').removeClass('hide');
    }
    else{
      $('#phone-error').addClass('hide');
      phonename = true;
    }
    if(fname == true && lname == true && emailname == true && phonename == true){
      document.getElementById("createClient").submit();
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
