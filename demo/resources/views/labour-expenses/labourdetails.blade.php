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
<div class="card" >
    <!-- <h5 class="card-header">Table Basic</h5> -->
    <div class="table-responsive text-nowrap">
        <table class="table" id="labour_datatable">
            <thead>
                <tr>
                    <th>Days</th>
                    <th>Name</th>
                    <th>Salary</th>
                    <th>Unpaid Amount</th>
                    <th>Advance Amount</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">

                @foreach ($labour as $labour)
                    <tr>
                        <td>{{ $labour->day_of_week }}</td>
                        <td>{{ $labour->labour_name }}</td>
                        <td>{{ $labour->amount }} </td>
                        <td>{{ $labour->unpaid_amt }}</td>
                        <td>{{ $labour->advance_amt }}</td>
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
            "lengthMenu": [15, 25, 50, 100],
            processing: false,
           search:false,
           ordering:false,

        });
    });
</script>
