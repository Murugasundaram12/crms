<html>
    <head>
    <style>
table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
  font-size: 11px;
}
.dropdown-toggle{
  width:146px !important;
}
.bs-caret::after{
  color:#f7f7f7 !important;
  content: "";
  display:none !important;
}
</style>
</head>
<body>
<div class="card" style="margin-top: 40px;">
  <!-- <h5 class="card-header">Table Basic</h5> -->

  <div class="table-responsive text-nowrap">
    <table class="table" id="payment_expenses_listing_table">
      <thead>
        <tr>
            <th>ID</th>
          <th>Category Name</th>
          <th>Added By</th>
          <th>Amount</th>
          <th>Paid Amount</th>
          <th>Payment Mode</th>
          <th>Description</th>
          <th>Received Date</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">

        @foreach($project as $project)
       <tr>
       <td>{{ $loop->index+1}}</td>
        <td> {{$project->category_name}}</td>
        <td>{{$project->first_name}} {{$project->last_name}}</td>
        <td>{{$project->amount}}</td>
        <td>{{$project->paid_amt}}</td>
        <td>{{$project->payment_name}}</td>
        <td>{{$project->description}}</td>
        <td>{{$project->current_date}}</td>
       </tr>
       @endforeach

      </tbody>
    </table>
  </div>
</div>
</body>
    </html>
