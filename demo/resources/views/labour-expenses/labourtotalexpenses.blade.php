<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include DataTables CSS and JS from CDN -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>

<style>
    @media only screen and (max-width:320px) {
        .aa {
            display: inline !important;
        }
    }

    .dataTables_wrapper {
        font-family: tahoma;
        font-size: 13px;
        clear: both;

    }

    .dataTables_length select {
        width: 120px;
    }

    .table-responsive {
        margin-top: 5px;
        margin-left: 5px;


    }
</style>
<!-- Basic Bootstrap Table -->
<div class="">
    <h5 class=""><b>Labour Name: </b> {{ $labour_name->name }}</h5>
    <div class="table-responsive ">
        <table class="table" id="labour_datatable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Project Name</th>
                    <th>Salary</th>
                    <th>Paid Amount</th>
                    <th>Unpaid Amount</th>
                    <th>Advance Amount</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">

                @foreach ($labours as $labour)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($labour->current_date)->format('d-m-Y') }} <br />
                          {{ \Carbon\Carbon::parse($labour->current_date)->format('h:i A') }}</td>
                        <td>{{ $labour->project_name }}</td>
                        <td>{{ $labour->amount }} </td>
                        <td>{{ $labour->paid_amt }}</td>
                        <td>{{ $labour->unpaid_amt }}</td>
                        <td>{{ $labour->extra_amt }}</td>
                        <td>{{ $labour->description }}</td>
                    </tr>
                @endforeach

            </tbody>
        </table>
    </div>
</div>
<!--/ Basic Bootstrap Table -->





<!-- modal popup for salary details -->
<script>
    $(document).ready(function() {
        var data = new DataTable('#labour_datatable', {
            "lengthMenu": [5,10,15, 25, 50, 100],
            processing: false,
           search:false,
           ordering:false,

        });
    });
</script>
