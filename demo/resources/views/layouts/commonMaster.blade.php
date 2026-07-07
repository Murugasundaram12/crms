<!DOCTYPE html>

<html class="light-style layout-menu-fixed" data-theme="theme-default" data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{ url('/') }}" data-framework="laravel" data-template="vertical-menu-laravel-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>@yield('title') </title>
  <meta name="description" content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta name="keywords" content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}">

  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

   <!-- Canonical SEO -->
  <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/icons/housefix-favicon.png') }}" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <!-- Include Styles -->

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.0/css/bootstrap.min.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.5.0/css/bootstrap-datepicker.css" rel="stylesheet">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.5.0/js/bootstrap-datepicker.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous">
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous">
  </script>

  <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

  <!--- link bootstrap --->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link href='https://fonts.googleapis.com/css?family=Comfortaa' rel='stylesheet'>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!--- link bootstrap --->
  @include('layouts/sections/styles')

  @include('layouts/app')
  <!-- Include Scripts for customizer, helper, analytics, config -->
  @include('layouts/sections/scriptsIncludes')
  <link rel="stylesheet" href="{{ asset('css/crm-unified.css') }}">
  <style>
    @media (max-width: 576px) {

      .mob {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        justify-content: stretch;
        position: fixed;
         left: 159px !important;
        bottom: 0;
      }
    }

    @media only screen and(max-width:320px) {
      .col-md-4 .col-form-label .text-md-end {
        margin-left: -135px;
      }
    }

    .round {
      border: 3px solid #03BFCB;
      border-radius: 50%;
      padding: 7px;
      max-width: 100px;
      max-height: 100px;
    }
    body{

    }
*{
  font-family: 'Comfortaa', sans-serif !important;

}

    .image-container {
      /* position: relative; */
      /* display: inline-block; */

      text-align: center;
    }

    .mob {
      display:  flex;
      width:470px !important;
      flex-direction: column;
      align-items: stretch;
      justify-content: stretch;
      position: fixed;
      left: 1019px ;
      bottom: 0;
    }



    .offcanvas-body {
      display: flex;
      flex-direction: column;
      height: 100vh;
    }

    .overlay {
      position: absolute;
      bottom: 0;
      right: 0;
      background-color: rgba(255, 255, 255, 0.8);
      padding: 5px;
      cursor: pointer;
    }

    .plus-symbol {
      font-size: 22px;
      line-height: 1;
      /* float: right; */
      margin-top: -20px;
      color: black;
      cursor: pointer;
      margin-left: 71px !important;
    }
    .layout-menu{
      z-index: 100 !important;
    }
    .layout-navbar{
      z-index: 100 !important;
    }
    /* .card{
      overflow:  hidden !important;
    } */
  </style>
</head>

<body style="background-color:#f5f5f5">

  <!-- @guest
                                    @if (Route::has('login'))
    @include('auth/login')
    @endif
@else
    <script>
        window.location = "/dashboard";
    </script>
                        @endguest -->
  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->

  {{-- remove while creating package --}}
  <!-- <div class="buy-now">
    <a href="{{ config('variables.productPage') }}" target="_blank" class="btn btn-danger btn-buy-now">Upgrade To Pro</a>
  </div> -->
  {{-- remove while creating package end --}}

  <!-- Include Scripts -->
  @include('layouts/sections/scripts')

  <div class="offcanvas offcanvas-end" id="demo" style="width: 270px;">
    <div class="offcanvas-header">
      {{-- <h3 class="offcanvas-title">My Profile</h3> --}}
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <div class="image-container text-center">
        <img class="round" id="profile-image" @if(Auth::user()?->image!= '' || Auth::user()?->image != null)  src="{{ url('images/'.Auth::user()?->image) }}"  @else  src="{{asset('assets/img/icons/gray-user-profile-icon.png')}}" @endif alt="user" />

        <div id="refresh-image">
          <label for="image-input" class="plus-symbol">&#43;</label>
          <input type="file" id="image-input" accept="image/*" style="display: none; height: 100px; width: 100px;" />

        </div>
        <p id="image_upload" style="display:none">Image is uploading ... </p>
        <p id="image_uploaded" style="display:none">Image is uploaded <i class="bi bi-check-lg" style="color: green;"></i></p>
      </div>

      <h6 style="margin-top: 30px;color:#03BFCB; text-align:center">Name : <b style="color: black"> {{Auth::user()?->first_name}} {{Auth::user()?->last_name}}</b></h6>
      <h6 style="color: #03BFCB; text-align:center;">Role : <b style="color: black;"> {{Auth::user()?->roles->pluck('name')->first()}}</b></h6>
      @if(!empty(Auth::user()))
      <a href="{{route('user-edit',Auth::user()?->id)}}" type="submit" class="btn btn-dark" style="margin-top:20px;width:120px;text-align:center !important;margin-left:50px;"><i class="bi bi-pencil-square" style="font-size:14px;color:aliceblue;"></i> &nbsp;Edit Profile</a>
      @endif

      <div class="mob">


        <h6> <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bx bx-power-off me-2" style="color:black;font-weight:900 !important; text-shadow:black"></i></a>
          <!-- <span class="align-middle" style="color: rgb(0, 0, 0)"></span> -->
          <span class="datetime" style="color: red;font-weight:800;">{{ \Carbon\Carbon::now()->toDateTimeString() }}</span>
        </h6>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
          @csrf
        </form>
        </a>
      </div>
    </div>


    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> --}}
  </div>
      @if(session('error_sweet'))
<script>
   Swal.fire({
    icon: 'error',
    title: 'Message',
    text: '{{ session('error_sweet') }}',
    showConfirmButton: true,
    confirmButtonText: 'OK',
   // position: 'top-end',
    // customClass: {
    //     popup: 'swal2-toast-like'
    // }
});

</script>
@endif
@if($errors->any())
<script>
  Swal.fire({
    icon: 'error',
    title: 'Please check the form',
    html: @json(implode('<br>', $errors->all())),
    confirmButtonText: 'OK'
  });
</script>
@endif

  <script>
    document.getElementById('image-input').addEventListener('change', function(event) {
    //  alert('hi');
      const fileInput = event.target;
      const file = fileInput.files[0];

      if (file) {
        $('#image_upload').css('display','inline');
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('profile-image').src = e.target.result;
        };
        reader.readAsDataURL(file);
        var formData = new FormData();
        formData.append('image', file);
        $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        url: "{{route('profile_photo_upload')}}",
        data: formData,
        type: 'POST',
        contentType: false,
        processData: false,
        success: function (html) {
          console.log(html);
          $('#image_upload').css('display','none');
          $('#image_uploaded').css('display','inline');
          window.location.reload();
                }
    });
      }
      console.log(file);

    });

    const profileOverlay = document.querySelector('.overlay');
    if (profileOverlay) {
      profileOverlay.addEventListener('click', function() {
        document.getElementById('image-input')?.click();
      });
    }
  </script>
</body>

</html>
