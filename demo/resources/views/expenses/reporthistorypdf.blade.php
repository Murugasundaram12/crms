<!DOCTYPE html>
<html>
<head>
  <style>
    table, th, td {
      border: 1px solid black;
      border-collapse: collapse;
      font-size: 11px;
    }

    </style>
</head>
<body>
<div class="card">
  <!-- <h5 class="card-header">Table Basic</h5> -->
  <div class="table-responsive text-nowrap">
    <table class="table table-boadered" id="expenses_listing_table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Paid date</th>
          <th style="width:30px">Main <br/> Category</th>
          <th style="width:30px">Category <br/> Name</th>
          <th style="width:30px">Project Name</th>
          <th style="width:30px">Labour Name</th>
          <th style="width:30px">Vendor Name</th>
          <th>Amount</th>
          <th>Paid</th>
          <th>Unpaid</th>
          <th>Advanced Amount</th>
          <th style="width:30px">Description</th>
          <th>Payment Mode</th>


          @role('Admin')
          <th>Added By</th>

          <th>Edited By</th>
          @endrole


        </tr>
      </thead>
      <tbody class="table-border-bottom-0">

        @foreach($expenses as $expense)
       <tr>
        <td>{{ $loop->index+1}}</td>
        <td>{{\Carbon\Carbon::parse($expense->current_date)->format('d-m-Y h:i A')}}</td>
         <td style="width:30px">{{$expense->main_category_name ? $expense->main_category_name : '--'}}</td>
        <td style="width:30px">{{$expense->category_name ? $expense->category_name : '--'}}</td>
        <td style="width:30px">{{$expense->project_name ? $expense->project_name : '--'}}</td>
        <td style="width:30px">{{$expense->labour_name ? $expense->labour_name : '--'}}</td>
        <td style="width:30px">{{$expense->vendor_name ? $expense->vendor_name : '--'}}</td>
        <td>{{$expense->amount}}</td>
        <td><span style="color: green;">{{$expense->paid_amt}}</td>
          <td>@if($expense->unpaid_amt !=0)<a style="color:red">{{$expense->unpaid_amt}}</a> @else {{$expense->unpaid_amt}}@endif</td>
          <td>{{$expense->extra_amt}}</td>
          <td style="width:30px">{{$expense->description? $expense->description : '--'}}</td>
        <td>{{$expense->payment_name}}</td>

        @role('Admin')
        <td>{{$expense->first.''.$expense->last}}</td>
        <td>{{$expense->first_name.''.$expense->last_name}}</td>
        @endrole


       </tr>
       @endforeach

      </tbody>
    </table>
  </div>
</div>


</body>
</html>
