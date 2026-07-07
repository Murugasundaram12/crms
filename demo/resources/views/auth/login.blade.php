<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="http://127.0.0.1:8000/assets/img/icons/housefix-favicon.png" />
    <title>{{ __('Login | HOUSE FIX - A DOCTOR FOR YOUR HOUSE') }}</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{asset('assets/vendor/css/pages/page-auth.css')}}">
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  </head>
<style>
  .modal-backdrop {
    opacity: 0.5 !important;
  }
  </style>
<body style="background-color: #f5f5f9">
  <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
  @if (session()->has('popup'))

  <script>
      $(function() {
        $('.success-msg').text('Password updated successfully')
        $('#walletsuccess').removeClass('fade');
        $('#walletsuccess').modal('show');
      });
  </script>
  @endif
@if(session()->has('msg'))
    {{-- <div class="alert alert-danger">
        {{ session()->get('msg') }}
    </div> --}}
          @if(session('msg'))
<script>
   Swal.fire({
    icon: 'error',
    title: 'Warning',
    text: '{{ session('msg') }}',
    showConfirmButton: true,
    confirmButtonText: 'OK',
   // position: 'top-end',
    // customClass: {
    //     popup: 'swal2-toast-like'
    // }
});

</script>
@endif
@endif
@if(session()->has('success'))
    <div class="alert alert-success">
        {{ session()->get('success') }}
    </div>
@endif

<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">
        <div class="card" >
            <div class="card-header text-center" style="background-color: white">
                <img src="{{ asset('assets/img/icons/logo12.png') }}" class="img-fluid" alt="Layout container" style="width: 55%;">

            </div>

            <div class="card-body" style="height:325px;">
              <div class="app-brand justify-content-center">

                <form method="POST" id="formloginAuth" action="{{ route('login') }}">
                    @csrf

                    <div class="form-group">
                    <label for="phone" class="col-md-4  text-left " style="font-size:13px;">{{ __('Phone number') }}</label>
                        <input id="phone" type="text" onkeypress="allowNumbersOnly(event)" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" required autocomplete="phone" autofocus>
                        @error('phone')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group">
                    <label for="password" class="col-md-4  text-left" style="font-size:13px;">{{ __('Password') }}</label>
                        <input id="password" type="password" maxlength="10" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                        <div class="col-form-label text-left">
                        <input type="checkbox" class="" onclick="myFunction()">&nbsp; Show Password
                        @error('password')
                    </div>
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary d-grid w-100">
                            {{ __('Login') }}
                        </button>
<br><br>
                        <a href="{{ route('forget-password') }}" type="submit" class="d-flex align-items-center justify-content-center">
                          {{ __('forget password') }}<i class="bx bx-chevron-right scaleX-n1-rtl bx-sm"></i>
                        </a>
                    </div>
                </form>
              </div>
            </div>
        </div>
    </div>
  </div>
</div>
<div id="walletsuccess" class="modal fade"  data-backdrop="static" data-keyboard="false" >
	<div class="modal-dialog modal-centered modal-confirm modal-sm">
		<div class="modal-content">
			<center><div class="modal-header btn-success">
				<h4 class="modal-title ">Success</h4>
			</div></center><hr>
			<div class="modal-body">
				<p class="text-center success-msg"></p>
			</div>
			<div class="modal-footer">
				<button class="btn btn-success btn-block" id="success-popup" data-bs-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>

    function allowNumbersOnly(e) {
    var key = e.key;
    if (isNaN(key) || key === ' ' || key === null) {
        e.preventDefault();
    }
}
    function myFunction() {
  var x = document.getElementById("password");
  if (x.type === "password") {
    x.type = "text";
  } else {
    x.type = "password";
  }
}
    $('form[id="formloginAuth"]').validate({
        rules: {
            phone: {
                required: true,
            },
            password: {
                required: true,
            },
        },
        messages: {
            phone: {
                required: 'Phone number is required',
            },
            password: {
                required: 'Password is required',
            },
        },
        submitHandler: function(form) {
            form.submit();
        }
    });
    $('#success-popup').click(function(){
      $('#walletsuccess').modal('hide');
    });
</script>

</body>
</html>
