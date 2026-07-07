@extends('layouts/contentNavbarLayout')

@section('title', 'Edit | HOUSE FIX - A DOCTOR FOR YOUR HOUSE')

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
        <span class="text-muted fw-light">Edit Vendor
    </h4>
    <div class="row" style="position:absolute; top:150px; right:50px;  ">
        <div class="col-md-12">
            <ul class="nav nav-pills flex-column flex-md-row mb-3">
                <li class="nav-item"><a class="nav-link active" href="{{ route('vendor-index') }}"><i class="bx me-1"></i>
                        Back </a></li>

            </ul>
        </div>
    </div>
    <div class="row">
        <div class="tab-content">
            <div class="tab-pane active" id="general-info">
                <form name="createMember" action="{{ route('vendor.update',$user->id) }}" id="createMember" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    {{ method_field('PUT') }}
                    <div class="col-xl">
                        <div class="card mb-4" style="margin-top:30px;">

                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-fullname">Name</label>
                                            <input type="text" id="first_name" name="name" class="form-control" value="{{ $user->name }}"
                                                id="basic-default-fullname" onkeydown="return /[a-z, ]/i.test(event.key)"
                                                onblur="if (this.value == '') {this.value = '';}"
                                                onfocus="if (this.value == '') {this.value = '';}"
                                                placeholder="Enter First name" />
                                            <label id="first-error" class="error" for="basic-default-first_name">Name is
                                                required</label>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-phone">Phone Number</label>
                                            <input type="text" maxlength="10" name="phone" id="phone" value="{{ $user->phone }}"
                                                class="form-control phone-mask" onkeypress="allowNumbersOnly(event)"
                                                placeholder="Enter Phone number" oninput="phoneunique(this.value)" />
                                            <label id="phone-error" class="error" for="basic-default-phone">Phone number is
                                                required</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-message">Address</label>
                                            <textarea id="basic-default-message" class="form-control" name="address" placeholder="Enter Address">{{ $user->address }}</textarea>
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
            var phone = $('#phone').val()

            var fname = false,
                phonename = false;
            console.log('first', first_name);
            console.log('phone', phone);

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

            if (fname == true && phonename == true ) {
                document.getElementById("createMember").submit();
            }
        });



    </script>
@endsection
