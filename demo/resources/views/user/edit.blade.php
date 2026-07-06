@extends('layouts/contentNavbarLayout')

@section('title', 'Edit | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

@section('content')
    <style>
        .tab-container {
            width: 100%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .tab-buttons {
            display: flex;
            background: #eee;
            border-bottom: 1px solid #ccc;
        }

        .tab-button {
            flex: 1;
            padding: 14px;
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            color: #555;
            transition: background 0.3s, color 0.3s;
        }

        .tab-button:hover {
            background-color: #171f29;
            color: #fff;
        }

        .tab-button.active {
            background-color: #171f29;
            color: #fff !important;
        }

        .tab-content {
            display: none;
            padding: 20px;
        }

        .tab-content.active {
            display: block;
        }

        svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
        }

        .tab-pane {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .tab-pane.active,
        .tab-pane.show {
            display: block;
            opacity: 1;
        }
    </style>
    <!-- <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Forms/</span></h4> -->
    <!-- Basic Layout & Basic with Icons -->
    <div class="justify-content-between" style="justify-content: space-between">
        <h4 class="fw-bold">
            <span class="text-muted fw-light">Edit Member
        </h4>

<div style="float:right;">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item"><a class="nav-link active" href="{{ route('user-index') }}"><i class="bx me-1"></i> Back
                </a></li>

        </ul>
</div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    {{-- <ul class="nav nav-tabs">
   <li @if ($tab == 'general-info') class="active" @endif><a href="#general-info" data-toggle="tab">General Info</a></li>
   <li @if ($tab == 'job-info') class="active" @endif><a href="#job-info" data-toggle="tab">Job Info</a></li>
</ul> --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <div class="tab-container" style="margin-top: 5px;">
        <div class="tab-buttons">
            <!-- General Info -->
            <a href="#general-info" data-toggle="tab"
                class="tab-button {{ request('tab') == 'general-info' || $tab == 'general-info' ? 'active' : '' }}">
                <!-- Icon: User -->
                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    viewBox="0 0 24 24" height="20" width="20">
                    <path d="M20 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M4 21v-2a4 4 0 0 1 3-3.87"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                General Info
            </a>

            <!-- Job Info -->
            <a href="#job-info" data-toggle="tab"
                class="tab-button {{ request('tab') == 'job-info' || $tab == 'job-info' ? 'active' : '' }}">
                <!-- Icon: Briefcase -->
                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    viewBox="0 0 24 24" height="20" width="20">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 3h-8a2 2 0 0 0-2 2v2h12V5a2 2 0 0 0-2-2z"></path>
                </svg>
                Job Info
            </a>

        </div>
        <div class="tab-content @if ($tab == 'general-info') active @endif" id="general-info">
            <div class="tab-pane @if ($tab == 'general-info') active @endif" >
                <div class="row">
                    <form name="EditMember" action="{{ route('user.update', $user->id) }}" id="EditMember" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        {{ method_field('PUT') }}
                        <div class="col-xl">
                            <div class="card mb-4" style="margin-top:30px;">

                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="basic-default-fullname">First Name</label>
                                                <input type="hidden" id="formname" name="edit_member" value="edit_member">
                                                <input type="hidden" id="user_id" value="{{ $user->id }}">
                                                <input type="text" id="first_name" name="first_name" class="form-control"
                                                    value="{{ $user->first_name }}" id="basic-default-fullname"
                                                    onkeydown="return /[a-z, ]/i.test(event.key)"
                                                    onblur="if (this.value == '') {this.value = '';}"
                                                    onfocus="if (this.value == '') {this.value = '';}"
                                                    placeholder="Enter First name" />
                                                <label id="first-error" class="error" for="basic-default-first_name">First
                                                    Name
                                                    is required</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="basic-default-company">Last Name</label>
                                                <input type="text" name="last_name" id="last_name"
                                                    value="{{ $user->last_name }}" class="form-control"
                                                    id="basic-default-company" onkeydown="return /[a-z, ]/i.test(event.key)"
                                                    onblur="if (this.value == '') {this.value = '';}"
                                                    onfocus="if (this.value == '') {this.value = '';}"
                                                    placeholder="Enter Last name" />
                                                <label id="last-error" class="error" for="basic-default-last_name">last
                                                    Name is
                                                    required</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="basic-default-email">Email</label>
                                                <input type="email" id="email" name="email" class="form-control"
                                                    value="{{ $user->email }}" placeholder="Enter Email"
                                                    aria-label="john.doe" aria-describedby="basic-default-email2" />
                                                <label id="email-error" class="error" for="basic-default-email">Email is
                                                    required</label>
                                                <label id="email-invalid-error" class="error"
                                                    for="basic-default-email">Email is invalid</label>
                                            </div>
                                            <!--  <div class="mb-3">
                    <label class="form-label" for="basic-default-email">Password</label>

                      <input  type="password"  value="{{ $user->password }}" readonly id="password" name="password" class="form-control" placeholder="Enter Password" aria-label="john.doe" aria-describedby="basic-default-email2" />
                      <label id="password-error" class="error" for="basic-default-password">Password is required</label>
                      <label id="password-invalid-error" class="error" for="basic-default-password">Password must be at least 8 characters long</label>

                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="basic-default-email">Confirm Password</label>

                      <input  type="password" id="confirm_password"  value="{{ $user->confirm_password }}" readonly name="confirm_password" class="form-control" placeholder="Enter Confirm Password" aria-label="john.doe" aria-describedby="basic-default-email2" />
                      <label id="confirm-error" class="error" for="basic-default-confirm_password">Confirm Password is required</label>
                      <label id="confirm-invalid-error" class="error" for="basic-default-password">Confirm Password must be at least 8 characters long</label>
                      <label id="confirm-match-error" class="error" for="basic-default-password">Confirm password must match to Password</label>
                  </div>-->
                                            <div class="mb-3">
                                                <label class="form-label" for="basic-default-phone">Phone Number</label>
                                                <input type="text" id="phone" value="{{ $user->phone }}"
                                                    maxlength="10" name="phone" id="basic-default-phone"
                                                    class="form-control phone-mask" onkeypress="phoneno()"
                                                    oninput="phoneunique(this.value)" placeholder="Enter Phone number" />
                                                <label id="phone-error" class="error" for="basic-default-phone">Phone
                                                    number
                                                    is required</label>
                                                <input type="hidden" class="phone-unique-error" value="">
                                                <label id="phone-unique-error" class="error"
                                                    for="basic-default-phone">Phone
                                                    number already exists</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="basic-default-message">Gender</label><br>
                                                <input type="radio" class="gender" value="1"
                                                    @if ($user->gender == 1) checked @endif id="male"
                                                    name="gender">
                                                <label class="form-label" for="male">Male</label> &nbsp;
                                                <input type="radio" class="gender" value="2"
                                                    @if ($user->gender == 2) checked @endif id="female"
                                                    name="gender">
                                                <label class="form-label" for="female">Female</label> &nbsp;
                                                <input type="radio" class="gender" value="3"
                                                    @if ($user->gender == 3) checked @endif id="other"
                                                    name="gender">
                                                <label class="form-label" for="male">Other</label><br />
                                                <label id="gender-error" class="error" for="basic-default-gender">Gender
                                                    is
                                                    required</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="basic-default-phone">Profile Photo</label>
                                                <input type="file" name="image" id="image" class="form-control"
                                                    accept="image/*" placeholder="image">

                                                @if ($user->image != '' || $user->image != null)
                                                    <input type="hidden" id="image_status" name="image_status"
                                                        value="{{ $user->image }}">
                                                    <img src="{{ url('images/' . $user->image) }}" width="50px"
                                                        height="50px">
                                                @endif
                                                <label id="job_title-error" class="error"
                                                    for="basic-default-job_title">Profile photo is required</label>
                                            </div>
                                            <div class="mb-3">
                                                <?php //dd($modeluser);
                                                ?>
                                                <label class="form-label" for="basic-default-phone">Roles</label>
                                                <select class="form-control" name="roles" id="roles">
                                                    <option value="">Select roles </option>
                                                    @foreach ($role as $role)
                                                        <option value="{{ $role->id }}"
                                                            {{ $role->id == $modeluser->role_id ? 'selected' : '' }}>
                                                            {{ $role->name }}</option>
                                                    @endforeach
                                                </select>
                                                <label id="roles-error" class="error" for="basic-default-role">Role is
                                                    required</label>
                                            </div>
                                            <center> <button type="submit" class="btn btn-primary"
                                                    style="margin-top: 20px;">Submit</button>
                                                <!-- <button type="submit" class="btn btn-danger" style="background-color: red; margin-top:20px;">Reset</button> -->
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
        <div class="tab-content @if ($tab == 'job-info') active @endif" id="job-info">
            <div class="tab-pane  active " >
                <div class="row">
                    <form name="EditJobInfo" action="{{ route('user.jobupdate', $user->id) }}" id="EditJobInfo"
                        method="post" enctype="multipart/form-data">
                        @csrf
                        {{ method_field('PUT') }}
                        <div class="col-xl">
                            <div class="card mb-4" style="margin-top:30px;">


                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <input type="hidden" id="formname" name="edit_jobinfo"
                                                    value="edit_jobinfo">
                                                <label class="form-label" for="basic-default-phone">Job Title</label>
                                                <input type="text" value="{{ $user->job_title }}" name="job_title"
                                                    id="job_title" class="form-control phone-mask"
                                                    placeholder="Enter Job Title" />
                                                <label id="form2-job_title-error" class="error"
                                                    for="basic-default-job_title">Job title is required</label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="basic-default-phone">Salary</label>
                                                <input type="text" onkeypress="allowNumbersOnly(event)"
                                                    value="{{ $user->salary }}" name="salary" id="salary"
                                                    class="form-control phone-mask" placeholder="Enter salary" />
                                                <label id="form2-salary-error" class="error"
                                                    for="basic-default-job_title">Job title is required</label>
                                            </div>


                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="datetimepicker1">Date of
                                                    joining</label><br>
                                                <input type="date" class="form-control" id="datetimepicker1"
                                                    name="date_of_joining"
                                                    value="{{ $user->date_of_hiring != '0000-00-00' && $user->date_of_hiring != null ? explode(' ', $user->date_of_hiring) : Carbon\Carbon::now()->format('Y-m-d') }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="basic-default-phone">Upload Government
                                                    Document</label>
                                                <input type="file" name="government_image" id="government_image"
                                                    class="form-control" accept="image/*" placeholder="image">
                                                @if ($user->government_image != '' || $user->government_image != null)
                                                    <input type="hidden" name="government_image_status"
                                                        value="{{ $user->government_image }}">
                                                    <img src="{{ url('public/images/' . $user->government_image) }}"
                                                        width="30px">
                                                @endif
                                                <label id="government_image-error" class="error"
                                                    for="basic-default-job_title">Upload Government document is </label>
                                            </div>


                                            <center> <button type="submit" class="btn btn-primary"
                                                    style="margin-top: 20px;">Submit</button>
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
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.error').addClass('hide');
        });

        function phoneno() {
            $('#phone').keypress(function(e) {
                var a = [];
                var k = e.which;

                for (i = 48; i < 58; i++)
                    a.push(i);

                if (!(a.indexOf(k) >= 0))
                    e.preventDefault();
            });
        }
        $('#EditMember').submit(function(e) {
            e.preventDefault();
            var first_name = $('#first_name').val();
            var last_name = $('#last_name').val();
            var email = $('#email').val();
            var password = $('#password').val();
            var confirm_password = $('#confirm_password').val();
            var phone = $('#phone').val();
            var job_title = $('#image').val();
            var image_status = $('#image_status').val();
            var gender = $('.gender:checked').length;
            var roles = $('#roles').find(":selected").val();
            var test = $('.phone-unique-error').val();
            var fname = false,
                lname = false,
                emailname = false,
                passwordname = false,
                conname = false,
                phonename = false,
                jobname = false,
                gendername = false,
                rolesname = false;
            console.log('first', first_name);
            console.log('last', last_name);
            console.log('email', email);
            console.log('password', password);
            console.log('confirm', confirm_password);
            console.log('phone', phone);
            console.log('job_title', job_title);
            console.log('gender', gender);
            console.log('roles', roles);
            console.log('test', test);
            console.log('image_status', image_status);

            if (first_name == "") {
                $('#first-error').removeClass('hide');
            } else {
                $('#first-error').addClass('hide');
                fname = true;
            }
            if (last_name == "") {
                $('#last-error').removeClass('hide');
            } else {
                $('#last-error').addClass('hide');
                lname = true;
            }
            if (email == "") {
                console.log('email length', email);
                $('#email-error').removeClass('hide');
            } else {
                var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                if (!regex.test(email)) {
                    $('#email-error').addClass('hide');
                    $('#email-invalid-error').removeClass('hide');
                } else {
                    $('#email-error').addClass('hide');
                    $('#email-invalid-error').addClass('hide');
                    emailname = true;
                }
            }

            if (phone.length < 1) {
                $('#phone-error').removeClass('hide');
            } else {
                $('#phone-error').addClass('hide');
                phonename = true;
            }
            if (job_title.length < 1 && image_status == '') {
                $('#job_title-error').removeClass('hide');
            } else {
                $('#job_title-error').addClass('hide');
                jobname = true;
            }
            if (gender.length < 1) {
                $('#gender-error').removeClass('hide');
            } else {
                $('#gender-error').addClass('hide');
                gendername = true;
            }
            if (roles.length < 1) {
                $('#roles-error').removeClass('hide');
            } else {
                $('#roles-error').addClass('hide');
                rolesname = true;
            }
            if (fname == true && lname == true && emailname == true && phonename == true && jobname == true &&
                gendername == true && rolesname == true && (test == false || test == "false")) {
                document.getElementById("EditMember").submit();
            }
        });

        function phoneunique(phone) {
            var user_id = $('#user_id').val();
            console.log(user_id, "user id");
            console.log(phone, "phone unique");
            if (phone.length == 10) {
                $.ajax({
                    url: "{{ route('phoneunique') }}",
                    data: {
                        'phone': phone
                    },
                    type: 'GET',
                    dataType: 'json',
                    success: function(result) {
                        console.log("result", result);
                        $('.phone-unique-error').val(result);
                        if (result == true)
                            $('#phone-unique-error').removeClass('hide');
                        else
                            $('#phone-unique-error').addClass('hide');
                    }
                });
            }
        }
        $('#EditJobInfo').submit(function(e) {
            e.preventDefault();
            var job_title = $('#job_title').val();
            var image = $('#government_image').val();
            var salary = $('#salary').val();
            var image_status = $('#government_image_status').val();
            var job_titlename = false,
                imagename = false,
                salaryname = false;

            console.log('job_title', job_title);
            console.log('image', image);
            console.log('salary', salary);

            if (job_title == "") {
                $('#form2-job_title-error').removeClass('hide');
            } else {
                $('#form2-job_title-error').addClass('hide');
                job_titlename = true;
            }
            if (image == "" && image_status == "") {
                $('#government_image-error').removeClass('hide');
            } else {
                $('#government_image-error').addClass('hide');
                imagename = true;
            }
            if (salary == "") {
                $('#form2-salary-error').removeClass('hide');
            } else {

                $('#form2-salary-error').addClass('hide');
                salaryname = true;
            }

            if (salaryname == true && imagename == true && job_titlename == true) {
                document.getElementById("EditJobInfo").submit();
            }
        });
       
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();

            // Remove 'active' from all tab contents and tab buttons
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });

            // Add 'active' to clicked tab and corresponding content
            const targetId = this.getAttribute('href');
            document.querySelector(targetId).classList.add('active');
            this.classList.add('active');
        });
    });


    </script>
@endsection
