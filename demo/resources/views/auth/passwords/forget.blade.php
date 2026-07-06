@extends('layouts/blankLayout')

@section('title', 'Forgot Password Basic - Pages')

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-auth.css')}}">
<style>
.leftside{
  margin-left: -309px !important;
}

.left{
  margin-left: -250px !important;
}
.left-side{
  margin-left: -200px !important;
}
</style>
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">

      <!-- Forgot Password -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center">
            <a href="{{url('/')}}" class="app-brand-link gap-2">
              <!-- <span class="app-brand-logo demo">@include('_partials.macros',['width'=>25,'withbg' => "#0081b8"])</span>
              <span class="app-brand-text demo text-body fw-bolder">{{ config('variables.templateName') }}</span>-->
            </a>
          </div>
          <!-- /Logo -->

<center>
          <form id="formAuthentication" name="forget-password" class="mb-3" action="javascript:void(0)" method="GET">
            <h4 class="mb-2">Forgot Password? 🔒</h4>
            <div class="mb-3">
              <label for="email" class="form-label leftside" >Email</label>
              <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email" autofocus>
              <label id="email-error" class="error left" for="basic-default-email">Email is required</label>
              <label id="email-invalid-error" class="error left" for="basic-default-email">Email is invalid</label>
              <label id="email-check-invalid-error" class="error left-side" for="basic-default-email">Please check your mail id.</label>
            </div>

            <button type="submit" class="btn btn-primary d-grid w-100">Send Reset Link</button>
          </form>
        </center>
          <div class="text-center">
            <a href="{{route('login')}}" class="d-flex align-items-center justify-content-center">
              <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
              Back to login
            </a>
          </div>
        </div>
      </div>
      <!-- /Forgot Password -->
    </div>
  </div>
</div>
<div id="forgetsuccess" class="modal fade" >
  <div class="modal-dialog modal-confirm modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <center><h4 class="modal-title">Success</h4>	</center>
      </div><hr>
      <div class="modal-body">
        <p class="text-center success-msg">Check your mail for password reset instructions.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success btn-block forget_redirect" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $('.error').addClass('hide');
    });
  $('#formAuthentication').submit(function(e) {
    e.preventDefault();
    var email = $('#email').val();
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

        $.ajax({
          type:'get',
          url:"{{ route('check-mail') }}",
          data:{email:email},
          dataType:'json',
          success:function(html){
            console.log(html);
            if(html.result == true){
              $('#email-check-invalid-error').removeClass('hide');
            }
            else{
              $('#email-check-invalid-error').addClass('hide');
              $.ajax({
                type:'get',
                url:"{{ route('send-mail') }}",
                data:{email:email},
                dataType:'json',
                success:function(html){
                  console.log(html);
                  $('#forgetsuccess').removeClass('fade');
                  $('#forgetsuccess').modal('show');
                }
              });
            }
          }
        });
      }
    }
  });
  $('.forget_redirect').click(function(){
window.location.href="{{ route('login') }}";
  });
  </script>
@endsection
