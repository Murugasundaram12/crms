@extends('layouts/commonMaster' )
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

<style>
#expenses_listing_table th,
#expenses_listing_table td {
  width: 10%; /* Adjust the width as needed */
  font-size: 13px; /* Adjust the font size as needed */
  white-space: nowrap; /* Prevent text from wrapping */
  text-overflow: ellipsis; /* Add ellipsis for long text */
  overflow: hidden; /* Hide overflowing content */
}
.preloader {
  width: 100%;
  height: 100vh;
  background-color: rgba(255, 255, 255, 0.2); /* 0.8 is the alpha channel for transparency */
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(15px); /* Add a blur effect for the glassy look */
}
.loader {
  width: 15px;
  aspect-ratio: 1;
  border-radius: 50%;
  animation: l5 1s infinite linear alternate;
}
</style>
@php
/* Display elements */
$contentNavbar = true;
$containerNav = ($containerNav ?? 'container-xxl');
$isNavbar = ($isNavbar ?? true);
$isMenu = ($isMenu ?? true);
$isFlex = ($isFlex ?? false);
$isFooter = ($isFooter ?? true);
$customizerHidden = ($customizerHidden ?? '');
$pricingModal = ($pricingModal ?? false);

/* HTML Classes */
$navbarDetached = 'navbar-detached';

/* Content classes */
$container = ($container ?? 'container-xxl');

@endphp

@section('layoutContent')
<div class="layout-wrapper layout-content-navbar {{ $isMenu ? '' : 'layout-without-menu' }}">
  <div class="layout-container">

    @if ($isMenu)
    @include('layouts/sections/menu/verticalMenu')
    @endif


    <!-- Layout page -->
    <div class="layout-page">
      <!-- BEGIN: Navbar-->
      @if ($isNavbar)
      @include('layouts/sections/navbar/navbar')
      @endif
      <!-- END: Navbar-->


      <!-- Content wrapper -->
      <div class="content-wrapper">

        <!-- Content -->
        @if ($isFlex)
        <div class="{{$container}} d-flex align-items-stretch flex-grow-1 p-0">
          @else
          <div class="{{$container}} flex-grow-1 container-p-y">
            @endif

            @yield('content')

            <!-- pricingModal -->
            @if ($pricingModal)
            @include('_partials/_modals/modal-pricing')
            @endif
            <!--/ pricingModal -->

          </div>
          <!-- / Content -->

          <!-- Footer -->
          @if ($isFooter)
          @include('layouts/sections/footer/footer')
          @endif
          <!-- / Footer -->
          <div class="content-backdrop fade"></div>
        </div>
        <!--/ Content wrapper -->
      </div>
      <!-- / Layout page -->
    </div>

    @if ($isMenu)
    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>
    @endif
    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>
  </div>

  <!-- / Layout wrapper -->
  <div class="modal fade" id="staticBackdrop4" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" >
    <div class="modal-dialog d-flex justify-content-center">
        <div class="modal-content w-75">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel4">Wallet Details</h5>
                <button type="button" class="btn-close wallet-close" data-mdb-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
               <div class="walletform"></div>
            </div>
        </div>
    </div>
</div>
<!--- modal popup for transfer -->
<div class="modal fade" id="transfer-popup" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" >
    <div class="modal-dialog d-flex justify-content-center">
        <div class="modal-content w-75">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel4">Transfer Details</h5>
                <button type="button" class="btn-close transfer-close" data-mdb-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
               <div class="transferform"></div>
            </div>
        </div>
    </div>
</div>
<!--- modal popup for transfer -->
</div>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
<script>
  $(document).ready(function(){
    $('.error').addClass('hide');

    // $('#transfer-popup').modal();
    // $('#transfer-popup').modal({backdrop:'static', keyboard:false});
  });
  // $('.transfer-close').click(function(){
  //   $('#transfer-popup').addClass('fade');
  //    window.location.reload();
  // });
    $('#wallet-click').click(function(){
      var url = '{{ route("wallet-create") }}';
      window.location.href=url;
    });

  $('#transfer-click').click(function(){

    var url = '{{ route("transfer-create") }}';
      window.location.href=url;
    });

</script>

  @endsection
