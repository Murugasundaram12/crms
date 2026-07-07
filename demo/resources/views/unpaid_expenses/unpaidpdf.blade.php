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
<div class="card" style="margin-top: 0px;">
  <!-- <h5 class="card-header">Table Basic</h5> -->
  <div class="table-responsive text-nowrap">
    <table class="table" id="unpaid_expenses_listing_table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Paid date</th>
          <th>Main<br/>Category</th>
          <th>Category <br/> Name</th>
          <th>Project Name</th>
          <th>Amount</th>
          <th>Paid</th>
          <th>Unpaid</th>
          <th>Advanced <br/> Amount</th>
          <th>Description</th>
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
        <td>{{\Carbon\Carbon::parse($expense->current_date)->format('d-m-Y')}}<br/>{{\Carbon\Carbon::parse($expense->current_date)->format('h:i A')}}</td>
        <td>{{ $expense->main_category_name??'--' }}</td>
        <td>{{$expense->category_name ? $expense->category_name : '--'}}</td>
        <td>{{$expense->project_name ? $expense->project_name : '--'}}</td>
        <td>{{$expense->amount}}</td>
        <td><span style="color: green;">{{$expense->paid_amt}}</td>
          <td>@if($expense->unpaid_amt !=0)<a   style="color:red">{{$expense->unpaid_amt}}</a> @else {{$expense->unpaid_amt}}@endif</td>
          <td>{{$expense->extra_amt}}</td>
          <td>{{$expense->description? $expense->description : '--'}}</td>
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
