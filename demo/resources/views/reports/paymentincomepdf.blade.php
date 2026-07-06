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
    <div class="table-responsive text-nowrap">
    <table class="table" id="payment_income_listing_table">
      <thead>
        <tr>
            <th>ID</th>
          <th>Client Name</th>
          <th>Received Amount</th>
          <th>Payment Mode</th>
          <th>Description</td>
          <th>Stage</th>
          <th>Received Date</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">

        @foreach($project as $project)
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

      </tbody>
    </table>
  </div>

        </body></html>
