@extends('layouts/contentNavbarLayout')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" defer></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/css/bootstrap-select.css">
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
   @media (max-width: 768px) {

        #filter-section .col-md-3,
        #filter-section .col-md-2,
        #filter-section .col-md-1 {
            margin-bottom: 10px;
        }
    }
      .card {
   
        padding: 15px;
    }
  </style>
@section('title', 'Report | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
<div style="margin-top: 30px;">
<h4 class="fw-bold py-3 mb-4">
  <span class="text-muted fw-light">Payment Income </span>
</h4>
</div>
<div class="card">
     <form id="submit-form">
            <div id="filter-section">
                <!-- First Row: Filters + Search -->
                <div class="row g-3 align-items-end">
                    <div class="col-md-1">
                        <label for="entries">Entities</label>
                        <select id="entries" name="paginate" class="form-control" onchange="submitform()">
                            <option value="10" {{ request('paginate') == '10' ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('paginate') == '25' ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('paginate') == '50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('paginate') == '100' ? 'selected' : '' }}>100</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control glass-dropdown">
                            <option value="">Select Category</option>
                            @foreach ($user as $category)
                                <option value="{{ $category->id }}"
                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->first_name }} {{ $category->last_name }}
                                </option>
                            @endforeach
                        </select>
                      </div>

                    <div class="col-md-3">
                        <label for="date_range">Date Range</label>
                        <input type="text" id="date_range" name="date_range" class="form-control"
                            value="{{ request('date_range') }}">
                    </div>

                    <div class="col-md-2">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search">
                    </div>
                </div>

                <!-- Second Row: Buttons -->
                <div class="row g-2 mt-3">
                    <div class="col-md-12 text-end">
                        <div class="d-flex justify-content-end flex-wrap gap-2">
                            <button class="btn btn-primary client_search" type="submit">
                                <i class="bx bx-search"></i>
                            </button>
                            <a class="btn btn-danger" href="{{route('payment-income',$id)}}">
                                <i class="bx bx-x-circle"></i>
                            </a>
                            <button type="button" class="btn btn-success" id="paymentincome-export">
                                <i class="bi bi-file-earmark-excel-fill"></i>
                            </button>
                            <button type="button" class="btn btn-danger" id="paymentincome-pdf">
                                <i class="bi bi-file-pdf"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
</div>
<!-- Basic Bootstrap Table -->
<div class="card" style="margin-top:5px">
  <!-- <h5 class="card-header">Table Basic</h5> -->
  <input type="hidden" name="id" value="{{$id}}" id="id">
  <div class="table-responsive text-nowrap">
    <table class="table" id="payment_income_listing_table">
      <thead>
        <tr>
            <th>ID</th>
          <th>Client Name</th>
          <th>Received Amount</th>
          <th>Payment Mode</th>
          <th>Decription</th>
          <th>Stage</th>
          <th>Received Date</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @if(count($projects) > 0)
        @foreach($projects as $project)
       <tr>
       <td>{{ $loop->index+1}}</td>
        <td> {{$project->first_name}} {{$project->last_name}}</td>
        <td>{{$project->amount}}</td>
        <td>{{$project->payment_name}}</td>
        <td>{{$project->description}}</td>
        <td>{{$project->stage_name}}</td>
        <td>{{$project->current_date}}</td>
       </tr>
       @endforeach
       @else
       <tr><td colspan="7"><center>No data found.</center></td></tr>
       @endif
      </tbody>
    </table>
      <div class="paginatestyle mt-4">
                {{ $projects->links('pagination::bootstrap-5') }}
            </div>
  </div>
</div>
<!--/ Basic Bootstrap Table -->

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/js/bootstrap-select.js"></script>
       <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.selectpicker').selectpicker();
        });
             $(function() {
            $('input[name="date_range"]').daterangepicker({
                autoUpdateInput: false, // don't set default value
                opens: 'left',
                locale: {
                    cancelLabel: 'Clear'
                }
            });

            $('input[name="date_range"]').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format(
                    'MM/DD/YYYY'));
            });

            $('input[name="date_range"]').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        });
        $('#category_id').select2({
            placeholder: "Select",
            allowClear: true,
            width: '100%',
        });
  $('#paymentincome-export').click(function(){
    console.log('test');
    var category_id =$('#category_id').find(":selected").val();
    var id = $('#id').val();

     var date_range=$('#date_range').val();
       var search = $('#search').val();
    var url = '{{ route("paymentincome-export") }}';
      window.location.href=url+'?id='+id+'&date_range='+date_range+'&search='+search+'&category_id='+category_id;
  });
  $('#paymentincome-pdf').click(function(){
    console.log('test1');
    var user =$('#user_id').find(":selected").val();
    var id = $('#id').val();

     var   from_date=$('#from_date').val();
       var end_date = $('#to_date').val();
    var url = '{{ route("paymentincome-pdf") }}';
      window.location.href=url+'?id='+id+'&from_date='+from_date+'&to_date='+to_date+'&user_id='+user;
  });
</script>

@endsection
