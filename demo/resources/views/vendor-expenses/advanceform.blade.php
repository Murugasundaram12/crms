@extends('layouts/contentNavbarLayout')

@section('title', 'Form | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
    <!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->

    <!-- Basic Layout & Basic with Icons -->
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Advance Amount Deduction
    </h4>
    <div class="row" style="position:absolute; top:150px; right:50px ">
        <div class="col-md-12">
            <ul class="nav nav-pills flex-column flex-md-row mb-3">
                <li class="nav-item"><a class="nav-link active" href="{{ route('vendor-expenses-advance-history') }}"><i
                            class="bx me-1"></i> Back</a></li>

            </ul>
        </div>
    </div>
    <div class="row">
        <!-- Basic Layout -->
        <div class="col-xxl">
            <div class="card mb-4" style="top:30px">


                <div class="card-body">
                    <form name="UnpaidSubmit" action="{{ route('vendor-advance.store') }}" id="UnpaidSubmit" method="post">
                        @csrf
                        <div class="col-xl">
                            <div class="card mb-4" style="margin-top:30px;">
                                <div class="card-body">
                                    <div class="row">
                                        <input type="hidden" id="labour_id" value="{{ $labour->id }}" name="labour_id">
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-email">Paid amount</label>

                                            <input type="text" onkeypress="allowNumbersOnly(event)" id="amount"
                                                name="extra_amt" class="form-control" placeholder="Enter amount"
                                                value="" />

                                                <input type="hidden" name="advance_amt" id="advance_amt" value="{{ $labour->advance_amt }}">
                                            <p class="advance_amt" style="color:blue">Labour Total Advance Amount is
                                                :{{ $labour->advance_amt }} </p>
                                                <label id="amount-error" class="error" for="basic-default-email">Amount is
                                                  required</label>
                                        </div>
                                        <div class="mb-3">
                                          <label class="form-label" for="basic-default-message">Amount
                                              Deduction</label><br>
                                          <input type="radio" class="gender" value="1" id="male"
                                              name="gender">
                                          <label class="form-label" for="male">Advance</label> &nbsp;
                                          <input type="radio" class="gender" value="2" id="female"
                                              name="gender">
                                          <label class="form-label" for="female">Unpaid</label> <br />
                                          <label id="gender-error" class="error" for="basic-default-email">Amount
                                              deduction is
                                              required</label>
                                      </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="datetimepicker1">Project</label><br>
                                            <select class="form-control selectpicker" name="project_id"
                                                data-live-search="true" id="project_id">
                                                <option value="">Select project </option>
                                                @foreach ($project as $project)
                                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="project_advance_amt" id="project_advance_amt"
                                            value="">
                                        <input type="hidden" name="project_unpaid_amt" id="project_unpaid_amt"
                                            value="">
                                        <p class="project_advance_amt" style="color:blue"> </p>
                                        <p class="project_unpaid_amt" style="color:blue"> </p>
                                        <label id="project-error" class="error" for="basic-default-email">Project is
                                            required</label>
                                        <label id="advance-error" class="error" for="basic-default-email">Amount is
                                            insufficient</label>
                                            <label id="advance1-error" class="error" for="basic-default-email">Amount is
                                              insufficient</label>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="datetimepicker1">Date</label><br>
                                            <input type="date" class="form-control" id="datetimepicker1"
                                                name="current_date" value="{{ Carbon\Carbon::now()->format('Y-m-d') }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="appt">Time:</label><br>
                                            <input type="time" id="appt" class="form-control" name="time"
                                                value="{{ Carbon\Carbon::now()->format('h:i:s') }}">
                                        </div>
                                        <center> <button type="submit" class="btn btn-primary"
                                                style="margin-top: 20px;">Submit</button>
                                            <button type="reset" id="resetform" class="btn btn-danger"
                                                style="background-color: red; margin-top:20px;">Reset</button>
                                        </center>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Basic with Icons -->
    </div>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>

    <script>
        $(document).ready(function() {
            $('.error').addClass('hide');

        });

        function allowNumbersOnly(e) {
            var key = e.key;
            if (isNaN(key) || key === ' ' || key === null) {
                e.preventDefault();
           }
        }
        $('#UnpaidSubmit').submit(function(e) {
            e.preventDefault();

            var amount = $('#amount').val();
            var project_id = $('#project_id :selected').val();
            var advance_amt = $('#advance_amt').val();
            var project_advance_amt = $('#project_advance_amt').val();
            var project_unpaid_amt = $('#project_unpaid_amt').val();
            var gender = $('.gender:checked').length;
            var type = $('.gender:checked').val();

            console.log('amount', amount);
            console.log('project id', project_id);
            console.log('advance_amt', advance_amt);
            console.log('project_advance_amt', project_advance_amt);
            console.log('gender',gender);

            var amountname = false,
                projectname = false,
                advancename = false, gendername= false,
                unpaidname = false;

            if (amount == "") {
                $('#amount-error').removeClass('hide');
            } else {
                $('#amount-error').addClass('hide');
                amountname = true;
            }
            if (project_id == "") {
                $('#project-error').removeClass('hide');
            } else {
                $('#project-error').addClass('hide');
                projectname = true;
            }
            if (gender == 0) {
                $('#gender-error').removeClass('hide');
            } else {
                $('#gender-error').addClass('hide');
                gendername = true;
            }
            if (type == 1  && parseInt(project_advance_amt) == 0) {
                $('#advance-error').removeClass('hide');
                console.log('hi');
            }else if(type == 1 && (parseInt(advance_amt) < parseInt(amount)) && parseInt(project_advance_amt) < parseInt(amount)){
              $('#advance-error').removeClass('hide');
                console.log('else if hi');

            } else {
                $('#advance-error').addClass('hide');
                advancename = true;

                console.log('else');
            }
            if (type == 2 && parseInt(project_unpaid_amt) == 0) {
                $('#advance1-error').removeClass('hide');
            }else if(type == 2  && parseInt(project_unpaid_amt) < parseInt(amount)){
              $('#advance1-error').removeClass('hide');
                console.log('else if hi');

            } else {
                $('#advance1-error').addClass('hide');

                unpaidname = true;
                console.log('else');
            }
             if (amountname == true && projectname == true &&  advancename == true && gendername == true) {
                 document.getElementById("UnpaidSubmit").submit();
             }
        });
        $('#project_id').change(function(){
          var project_id = $(this).val();
          var labour_id = $('#labour_id').val();
          $.ajax({
                    url: "{{ route('vendor_project_amount') }}",
                    data: {
                        'labour_id': labour_id,
                        'project_id' : project_id
                    },
                    type: 'GET',
                    dataType: 'json',
                    success: function(result) {
                      console.log("result", result);
                    $('#project_advance_amt').val(result.advance);
                    $('#project_unpaid_amt').val(result.unpaid_amt);
                    $('.project_advance_amt').text('Labour Project Advance Amount is :' + result
                        .advance);
                    $('.project_unpaid_amt').text('Labour Project Unpaid Amount is :' + result
                        .unpaid_amt);
                    }
                });
        });
    </script>
@endsection

