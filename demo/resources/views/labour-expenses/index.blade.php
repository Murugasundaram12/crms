<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Include DataTables CSS and JS from CDN -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>

<!-- Basic Bootstrap Table -->
<style>
    .preloader {
        width: 100%;
        height: 100vh;
        background-color: rgba(255, 255, 255, 0.2);
        position: fixed;
        top: 0;
        left: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(15px);
    }

    .loader {
        width: 15px;
        aspect-ratio: 1;
        border-radius: 50%;
        animation: l5 1s infinite linear alternate;
    }

    tr.collapse.show {
        display: table-row !important;
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

<div class="card">

  <select class="form-control " name="year" id="year" style="width: 80px ;">
    <option value="">Select Year</option>
    @for ($year=2020; $year<=date('Y'); $year++) {
      <option value="{{ $year }}" {{ $year == $current_year ? 'selected' : '' }}>{{ $year }} </option>
   }
   @endfor
</select><br>
<div class="table-responsive text-nowrap">
  <table class="table" id="user_listing_table">
      <thead>
          <tr>
              <th>Week</th>
              <th>Unpaid Amount</th>
              <th>Advance Amount</th>
          </tr>
      </thead>
      <tbody class="table-border-bottom-0">
          @if(count($start_labour_date) > 0 )
              @foreach ($start_labour_date as $week_data)
                  @foreach ($week_data['records'] as $record)
                      <tr>
                          <td>
                              <a style="text-decoration: none" href="#project-row{{ $record->week_start_date }}"
                                  role="button" data-bs-toggle="collapse" aria-expanded="false"
                                  aria-controls="project-row{{ $record->week_start_date }}">
                                  {{ $record->week_start_date }} - {{ $record->week_end_date }}</a>
                          </td>
                          <td>{{ $record->unpaid_amt }}</td>
                          <td>{{ $record->advance_amt }}</td>
                      </tr>
                      <tr id="project-row{{ $record->week_start_date }}" class="collapse">
                          <td colspan="3">
                              <table class="table">
                                  <thead>
                                      <tr>
                                          <th>Project</th>
                                          <th>Unpaid Amount</th>
                                          <th>Advance Amount</th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      @foreach ($week_data['labour'] as $labour)
                                          <tr>
                                              <td>
                                                  <a style="text-decoration: none" href="javascript:void(0)" class="projectunpaid" style="cursor:pointer"
                                                      data-start_week="{{ $record->week_start_date }}"
                                                      data-end_week="{{ $record->week_end_date }}"
                                                      data-project_id="{{ $labour->labour_id }}"> {{ $labour->labour_name }} </a>
                                              </td>
                                              <td>{{ $labour->unpaid_amt }}</td>
                                              <td>{{ $labour->advance_amt }}</td>
                                          </tr>
                                      @endforeach
                                  </tbody>
                              </table>
                          </td>
                      </tr>
                  @endforeach
              @endforeach
          @else
              <tr>
                  <td colspan="3" style="text-align: center;"> No record found. </td>
              </tr>
          @endif
      </tbody>
  </table>
</div>

</div>
<!-- DataTables initialization -->
<script>
    $(document).ready(function() {
        var table = $('#user_listing_table').DataTable({
            "lengthMenu": [15, 25, 50, 100],
            "processing": true,
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
        });

        table.on('processing.dt', function(e, settings, processing) {
            if (processing) {
                $('.preloader').fadeIn();
            } else {
                $('.preloader').fadeOut();
            }
        });
    });
    $('.projectunpaid').click(function() {
        var start_date = $(this).attr('data-start_week');
        var end_date = $(this).attr('data-end_week');
        var project_id = $(this).attr('data-project_id');
        $('.preloader').css('display', 'block');
        var url = '{{ route('labour-expenses-project') }}';
            window.location.href = url + '?labour_id=' + project_id + '&start_date=' + start_date + '&end_date=' +end_date;
    });
    $('#year').change(function(){
      var year = $(this).val();
      console.log('year',year);
      $.ajax({
            type: "get",
            url: "{{ route('labour-expenses-index') }}",
            data:{'year' : year},
            dataType: 'json',
            success: function(html) {
                console.log(html);

                $('.labour_loadingsalary').html(html);
                $('.preloader').css('display', 'none');
                $('#labour_total_popup').modal('show');
            }
        });
    });
</script>
