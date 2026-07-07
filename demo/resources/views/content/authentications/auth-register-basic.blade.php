@extends('layouts/blankLayout')

@section('title', 'Register Basic - Pages')

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-auth.css')}}">
@endsection


@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">

      <!-- Register Card -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center">
           
         
          <!-- /Logo -->
          <h4 class="mb-2">Sign up</h4></div>
          <div class="display:flex;">
          <p class="mb-4">Create an account as a new client.</p>
          </div>

          <form id="formAuthentication" class="mb-3" action="{{url('/store')}}" method="post" autocomplete="off"  enctype="multipart/form-data">
            @csrf
            <!-- first name -->
          <div class="mb-3">
              <label for="firstname" class="form-label">First Name</label>
              <input type="text" class="form-control" id="firstname" name="first_name" placeholder="Enter your firstname" autofocus>
            </div>
            <!-- first name -->
            <!-- last name -->
            <div class="mb-3">
              <label for="lastname" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="lastname" name="last_name" placeholder="Enter your lastname" autofocus>
            </div>
          <!-- last name -->
          <!-- Type -->
          <div class="mb-3">
              <label for="type" class="form-label">Type</label>
              <div><input type="radio"  id="organization" name="type" value="0" style="height: 15px; width: 15px; vertical-align: middle;"> Organization
              <input type="radio"  id="individual" name="type" value="1" style="height: 15px; width: 15px;; vertical-align: middle;"> Individual</div>
            </div>
          <!-- Type -->
          <!-- email -->
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email">
            </div>
            <!-- email -->
            <!-- password -->
            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="password">Password</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
              </div>
            </div>
            <!-- password -->

            <!-- confirm password -->
            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="confirmpassword">Confirm Password</label>
              <div class="input-group input-group-merge">
                <input type="password" id="confirmpassword" class="form-control" name="confirm_password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
              </div>
            </div>
             <!-- confirm password -->
            <button type="submit" class="btn btn-primary d-grid w-100">
              Sign up
            </button>
          </form>

          <p class="text-center">
            <span>Already have an account?</span>
            <a href="{{url('/')}}">
              <span>Sign in instead</span>
            </a>
          </p>
        </div>
      </div>
    </div>
    <!-- Register Card -->
  </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
    <script>
$('form[id="formAuthentication"]').validate({
  rules: {
    first_name: 'required',
    last_name: 'required',
    type: 'required',
    email: {
      required: true,
      email: true,
    },
    password: {
      required: true,
      minlength: 8,
    },
    confirm_password: {
      required: true,
      minlength: 8,
      equalTo: "#password"
    }
  },
  messages: {
    first_name: 'First Name is required',
    last_name: 'Last Name is required',
    type: 'Type is required',
    email: {
    required:'Email is required',
    email: 'Enter a valid email',
    },
    password: {
      required: 'Password is required',
      minlength: 'Password must be at least 8 characters long'
    },
    confirm_password:{
      required: 'Confirm Password is required',
      minlength: 'Password must be at least 8 characters long',
      equalTo : 'Confirm password must match to Password'
    }
  },
  submitHandler: function(form) {
    form.submit();
  }
});
</script>
@endsection