@extends('layouts/contentNavbarLayout')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

<style>
  @media only screen and (max-width:320px){
    .aa{
      display:inline !important;
    }
  }
  .dataTables_wrapper {
    font-family: tahoma;
    font-size: 13px;
    clear: both;

}
.dropdown-toggle{
  width:146px !important;
}
.bs-caret::after{
  color:#f7f7f7 !important;
  content: "";
  display:none !important;
}
.dataTables_length select {
  width: 120px;
}
.table-responsive{
  margin-top:5px;
  margin-left:5px;
}
table {
  width:50%;
  border-spacing: 0; /* Remove spacing between cells */
  border-collapse: collapse; /* Collapse cell borders */
}

td, th {
  padding: 5px; /* Reduce cell padding */
}

  </style>
@section('title', 'Report | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
<div style="margin-top: -33px;">
<h4 class="fw-bold py-3 mb-4" style="color:black;font-size:20px;">
  <span class="fw-light">Payment Summary </span>
</h4>
</div>
<!-- Basic Bootstrap Table -->
<div class="card" style="max-width: 1200px; top:-21px; ">
  <!-- <h5 class="card-header">Table Basic</h5> -->
  <div class="table-responsive text-nowrap" style="width: 99%">
    <table class="table" id="payment_summary_listing_table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Project Name</th>
          <th>Income</th>
          <th>Expenses</th>
          <!-- <th>Profit</th> -->
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
     <?php //dd($expenses); ?>
        @foreach($expenses as $expense)
       <tr>
       <td>{{ $loop->index+1}}</td>
        <td>{{$expense->project_name}}</td>
        <td>@if($expense->projectid !='' && $expense->projectid != null)<a  href="{{route('payment-income',['id' => $expense?->projectid])}}" data-id="" class="">{{$expense?->advance_amt}}</a>@else -- @endif</td>
        <td>@if($expense->projectid !='' && $expense->projectid != null)<a  href="{{route('payment-expenses',$expense?->projectid)}}" data-id="" class="">{{$expense?->paid_amt1}}</a>@else -- @endif</td>
       </tr>
       @endforeach

      </tbody>
    </table>
  </div>
</div>
<!--/ Basic Bootstrap Table -->

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
  $(document).ready(function() {
var data =  new DataTable('#payment_summary_listing_table', {
  "lengthMenu": [15, 50, 100],
  processing: true,

});
});
</script>

@endsection
