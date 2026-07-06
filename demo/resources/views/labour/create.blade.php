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
        <span class="text-muted fw-light">Add Labour
    </h4>
    <div class="row" style="position:absolute; top:150px; right:50px;  ">
        <div class="col-md-12">
            <ul class="nav nav-pills flex-column flex-md-row mb-3">
                <li class="nav-item"><a class="nav-link active" href="{{ route('labour-index') }}"><i class="bx me-1"></i>
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
                <form name="createMember" action="{{ route('labour.store') }}" id="createMember" method="post"
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
                                                id="basic-default-fullname" onkeydown="return /[a-z, ]/i.test(event.key)"
                                                onblur="if (this.value == '') {this.value = '';}"
                                                onfocus="if (this.value == '') {this.value = '';}"
                                                placeholder="Enter First name" />
                                            <label id="first-error" class="error" for="basic-default-first_name">Name is
                                                required</label>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-phone">Phone Number</label>
                                            <input type="text" maxlength="10" name="phone" id="phone"
                                                class="form-control phone-mask" onkeypress="allowNumbersOnly(event)"
                                                placeholder="Enter Phone number"/>
                                            <label id="phone-error" class="error" for="basic-default-phone">Phone number is
                                                required</label>

                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-message">Gender</label><br>
                                            <input type="radio" class="gender" value="1" id="male"
                                                name="gender">
                                            <label class="form-label" for="male">Male</label> &nbsp;
                                            <input type="radio" class="gender" value="2" id="female"
                                                name="gender">
                                            <label class="form-label" for="female">Female</label> &nbsp;
                                            <input type="radio" value="3" class="gender" id="other"
                                                name="gender">
                                            <label class="form-label" for="male">Other</label><br />
                                            <label id="gender-error" class="error" for="basic-default-gender">Gender is
                                                required</label>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-phone">Government Photo</label>
                                            <input type="file" name="image" id="image" class="form-control"
                                                placeholder="image" accept="image/*">

                                        </div>

                                    </div>
                                    <div class="col-6">

                                        <div class="mb-3">

                                            <label class="form-label" for="basic-default-phone">Job Title</label>
                                            <input type="text" value="" name="job_title" id="job_title"
                                                class="form-control phone-mask" placeholder="Enter Job Title" />
                                            <label id="job_title-error" class="error"
                                                for="basic-default-job_title">Job title is required</label>
                                        </div>
                                        <div class="mb-3">
                                          <label class="form-label" for="basic-default-phone">Labour Role</label>
                                          <select class="form-control" name="labour_role" onchange="salary_details()" id="roles">
                                          <option value="">Select roles </option>
                                          @foreach($role as $role)
                                          <option value="{{$role->id}}">{{$role->name}}</option>
                                          @endforeach
                                          </select>
                                          <label id="roles-error" class="error" for="basic-default-role">Role is required</label>
                                        </div>
                                        <div class="mb-3">
                                          <label class="form-label" for="basic-default-phone">Salary</label>
                                            <input type="text" onkeypress="allowNumbersOnly(event)" value="" name="salary" id="salary" class="form-control phone-mask" placeholder="Enter salary" readonly />
                                            <label id="salary-error" class="error" for="basic-default-job_title">Salary  is required</label>
                                          </div>
                                          {{-- <div class="mb-3">
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
                                        </div> --}}


                                        <center> <button type="submit" class="btn btn-primary"
                                                style="margin-top: 20px;">Submit</button>
                                            <button type="reset" id="resetform" class="btn btn-danger"
                                                style="background-color: red; margin-top:20px;">Reset</button>
                                        </center>
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
            var phone = $('#phone').val();
            var gender = $('.gender:checked').length;
            var job_title = $('#job_title').val();
            var salary = $('#salary').val();
            var roles = $('#roles').find(":selected").val();
            var fname = false,
                phonename = false,
                jobname = false,
                gendername = false,
                salaryname = false,rolename = false;
            console.log('first', first_name);
            console.log('phone', phone);
            console.log('job_title', job_title);
            console.log('gender', gender);
            console.log('salary', salary);
            console.log('rolename', rolename);

            if (first_name == "") {
                $('#first-error').removeClass('hide');
            } else {
                $('#first-error').addClass('hide');
                fname = true;
            }

            if (phone.length < 1) {
                $('#phone-error').removeClass('hide');
            } else {
                $('#phone-error').addClass('hide');
                phonename = true;
            }
            if (job_title == "") {
                $('#job_title-error').removeClass('hide');
            } else {
                $('#job_title-error').addClass('hide');
                jobname = true;
            }
            if (gender < 1) {
                $('#gender-error').removeClass('hide');
            } else {
                $('#gender-error').addClass('hide');
                gendername = true;
            }
            if (roles < 1) {
                $('#roles-error').removeClass('hide');
            } else {

                $('#roles-error').addClass('hide');
                rolename = true;
            }
            if (salary == '') {
                $('#salary-error').removeClass('hide');
            } else {
                $('#salary-error').addClass('hide');
                salaryname = true;
            }
            if (fname == true && phonename == true && jobname == true && gendername == true && rolename == true && salaryname == true ) {
                document.getElementById("createMember").submit();
            }
        });
function salary_details(){
  var id = $('#roles :selected').val();
  $.ajax({
    type:"get",
    url:"{{route('salary-get')}}",
    data:{id:id},
    dataType:'json',
    success:function(html){
      console.log(html);
      $('#salary').val(html.salary);
    }


  });
}

    </script>
@endsection
