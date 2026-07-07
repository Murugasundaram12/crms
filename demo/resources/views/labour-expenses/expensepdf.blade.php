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
          <th style="width:30px">Main Category</th>
          <th style="width:30px">Category Name</th>
          <th style="width:30px">Project Name</th>
          <th>Labour Name</th>
          <th>Amount</th>


          <th>Payment Mode</th>
          <th style="width:30px">Description</th>
          <th>Paid</th>
          <th>Unpaid</th>
          <th>Advanced <br/> Amount</th>
          <th>Added By</th>

          <th>Edited By</th>
          <th>Advance <br/>EditedBy</th>


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
        <td>{{ $expense->labour_name }}</td>
        <td>{{$expense->amount}}</td>

        <td>{{$expense->payment_name}}</td>
        <td style="width:30px">{{$expense->description? $expense->description : '--'}}</td>
        <td><span style="color: green;">{{$expense->paid_amt}}</td>
        <td>@if($expense->unpaid_amt !=0)<a style="color:red">{{$expense->unpaid_amt}}</a> @else {{$expense->unpaid_amt}}@endif</td>
        <td>{{$expense->extra_amt}}</td>
        <td>{{$expense->first.''.$expense->last}}</td>
        <td>{{$expense->first_name.''.$expense->last_name}}</td>
        <td>{{ $expense->labour_first.''.$expense->labour_last }}</td>



       </tr>
       @endforeach

      </tbody>
    </table>
  </div>
</div>


</body>
</html>
