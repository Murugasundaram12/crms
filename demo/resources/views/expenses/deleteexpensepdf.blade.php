<html><head>
<style>
table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
  font-size: 11px;
}
</style></head><body>
<div class="card" style="margin-top: 22px;">
  <!-- <h5 class="card-header">Table Basic</h5> -->
  <div class="table-responsive text-nowrap">
    <table class="table" id="expenses_listing_table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Paid date</th>
          <th>Main Category</th>
          <th>Category Name</th>
          <th>Project Name</th>
          <th>Reason</th>
          <th>Amount</th>

          <th>Payment Mode</th>
          <th>Description</th>
          <th>Paid</th>
          <th>Unpaid</th>
          <th>Advanced Amount</th>
          @role('Admin')
          <th>Added By</th>

          <th>Edited By</th>
          @endrole

          <th>Deleted date</th>

        </tr>
      </thead>
      <tbody class="table-border-bottom-0">

        @foreach($expenses as $expense)
       <tr>
        <td>{{ $loop->index+1}}</td>
        <td>{{\Carbon\Carbon::parse($expense->current_date)->format('d-m-Y h:i A')}}</td>
        <td>{{ $expense->main_category_name?? '--' }}</td>
        <td>{{$expense->category_name ? $expense->category_name : '--'}}</td>
        <td>{{$expense->project_name ? $expense->project_name : '--'}}</td>
        <td>{{$expense->reason}}</td>
        <td>{{$expense->amount}}</td>

        <td>{{$expense->payment_name}}</td>
        <td>{{$expense->description? $expense->description : '--'}}</td>
        <td><span style="color: green;">{{$expense->paid_amt}}</td>
        <td><a style="color:red">{{$expense->unpaid_amt}}</a>
        <td>{{$expense->extra_amt}}</td>
        @role('Admin')
        <td>{{$expense->first.''.$expense->last}}</td>
        <td>{{$expense->first_name.''.$expense->last_name}}</td>
        @endrole

        <td>{{$expense->deleted_at}}</td>

       </tr>
       @endforeach

      </tbody>
    </table>
  </div>
</div>
</body></html>
