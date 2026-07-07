@extends('layouts/contentNavbarLayout')

@section('title', 'Create | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
    <!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->
    <style>
        /* .error{
            position: relative;
        left: -170px;
        top: 30px;
        } */
    </style>
    <!-- Basic Layout & Basic with Icons -->
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Add Labour Role
    </h4>
    <div class="row" style="position:absolute; top:150px; right:50px;  ">
        <div class="col-md-12">
            <ul class="nav nav-pills flex-column flex-md-row mb-3">
                <li class="nav-item"><a class="nav-link active" href="{{ route('labourrole-index') }}"><i class="bx me-1"></i>
                        Back </a></li>

            </ul>
        </div>
    </div>
    <!-- Tabs navs -->
    {{-- <ul class="nav nav-tabs">
   <li class="active"><a href="#general-info" data-toggle="tab"  class="active">General Info</a></li>
   <li><a href="#job-info" data-toggle="tab">Job Info</a></li>
</ul> --}}
    <!-- Tabs navs -->

    <div class="row">
        <div class="tab-content">
            <div class="tab-pane active" id="general-info">
                <form name="createMember" action="{{ route('labourrole.store') }}" id="createMember" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="col-xl">
                        <div class="card mb-4" style="margin-top:30px;">
                           

                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-fullname">Name</label>
                                            <input type="text" id="first_name" name="name" class="form-control"
                                                id="basic-default-fullname"
                                                placeholder="Enter First name" />
                                            <label id="first-error" class="error" for="basic-default-first_name">Name is
                                                required</label>
                                        </div>
                                        <div class="mb-3">
                                          <label class="form-label" for="basic-default-phone">Salary</label>
                                            <input type="text" onkeypress="allowNumbersOnly(event)" value="" name="salary" id="salary" class="form-control phone-mask" placeholder="Enter salary" />
                                            <label id="salary-error" class="error" for="basic-default-job_title">Salary  is required</label>
                                          </div>
                                    </div>
                                    <div class="col-6">


                                          <div class="mb-3">
                                            <label class="form-label" for="basic-default-message">Salary Type</label><br>
                                            <input type="radio" class="salary_type" value="1" id="daily"
                                                name="salary_type">
                                            <label class="form-label" for="daily">Daily</label> &nbsp;
                                            <input type="radio" class="salary_type" value="2" id="weekly"
                                                name="salary_type">
                                            <label class="form-label" for="weekly">Weekly</label> &nbsp;
                                            <input type="radio" value="3" class="salary_type" id="monthly"
                                                name="salary_type">
                                            <label class="form-label" for="monthly">Monthly</label><br />
                                            <label id="salary-type-error" class="error" for="basic-default-gender">Salary type is
                                                required</label>
                                        </div>


                                       <button type="submit" class="btn btn-primary"
                                                style="margin-top: 20px;">Submit</button>
                                            <button type="reset" id="resetform" class="btn btn-danger"
                                                style="background-color: red; margin-top:20px;">Reset</button>

                                    </div>

                                </div>



                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.error').addClass('hide');
        });

        function allowNumbersOnly(e) {
            var code = (e.which) ? e.which : e.keyCode;
            if (code > 31 && (code < 48 || code > 57)) {
                e.preventDefault();
            }
        }
        $('#createMember').submit(function(e) {
            e.preventDefault();
            var first_name = $('#first_name').val();

            var salary = $('#salary').val();
            var salary_type = $('.salary_type:checked').length;
            var fname = false,
                salaryname = false,salarytypename = false;
            console.log('first', first_name);

            console.log('salary', salary);
            console.log('salarytype', salary_type);

            if (first_name == "") {
                $('#first-error').removeClass('hide');
            } else {
                $('#first-error').addClass('hide');
                fname = true;
            }


            if (salary_type < 1) {
                $('#salary-type-error').removeClass('hide');
            } else {

                $('#salary-type-error').addClass('hide');
                salarytypename = true;
            }
            if (salary == '') {
                $('#salary-error').removeClass('hide');
            } else {
                $('#salary-error').addClass('hide');
                salaryname = true;
            }
            if (fname == true && salarytypename == true && salaryname == true ) {
                document.getElementById("createMember").submit();
            }
        });


    </script>
@endsection
