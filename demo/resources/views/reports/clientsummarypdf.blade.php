<html><head>
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
</head><body>
<div class="card" style="margin-top: 40px;">
  <!-- <h5 class="card-header">Table Basic</h5> -->
  <div class="table-responsive text-nowrap">
    <table class="table" id="client_summary_listing_table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Client Name</th>
          <th>Project Name</th>
          <th>Received Amount</th>
          <th>Total Amount</th>
          <th>Payment Mode</th>
          <th>Stages</th>
          <th>Received Date</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">

        @foreach($clients as $client)
       <tr>
       <td>{{ $loop->index+1}}</td>
        <td>{{$client->first_name}} {{$client->last_name}}</td>
        <td>{{$client->name}}</td>
        <td>{{$client->amount}}</td>
        <td>{{$client->total_amt}}</td>
        <th>{{$client->payment}}
        <td>{{$client->stage_name}}</td>
        <td>{{$client->currentdate}}</td>
       </tr>
       @endforeach

      </tbody>
    </table>
  </div>
</div>
</body></html>
