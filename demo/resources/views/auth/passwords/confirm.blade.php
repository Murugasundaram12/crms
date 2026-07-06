@extends('layouts/blankLayout')

@section('title', 'Reset Password')

@section('page-style')
    <!-- Page -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endsection


@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">

                <!-- Register Card -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center">


                            <!-- /Logo -->
                            <h4 class="mb-2">Reset Password</h4>
                        </div>
                        <div class="display:flex;">
                            {{-- <p class="mb-4">Create an account as a new client.</p> --}}
                        </div>

                        <form id="formAuthentication" class="mb-3" action="{{ route('update-password',$id) }}" method="post"
                            autocomplete="off" enctype="multipart/form-data">
                            @csrf
                          {{ method_field('PUT') }}
                            <input type="hidden" name="id" value="{{ $id }}">
                            <!-- password -->
                            <div class="mb-3 form-password-toggle">
                                <label class="form-label" for="password">Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>


                                </div>
                                <label id="password-error" class="error" for="basic-default-password">Password is
                                    required</label>
                                <label id="password-invalid-error" class="error" for="basic-default-password">Password must
                                    be at least 8 characters long</label>
                            </div>
                            <!-- password -->

                            <!-- confirm password -->
                            <div class="mb-3 form-password-toggle">
                                <label class="form-label" for="confirmpassword">Confirm Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="confirmpassword" class="form-control" name="confirm_password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                                <label id="confirm-error" class="error" for="basic-default-confirm_password">Confirm
                                    Password is required</label>
                                <label id="confirm-invalid-error" class="error" for="basic-default-password">Confirm
                                    Password must be at least 8 characters long</label>
                                <label id="confirm-match-error" class="error" for="basic-default-password">Confirm password
                                    must match to Password</label>


                            </div>
                            <!-- confirm password -->
                            <button type="submit" class="btn btn-primary d-grid w-100">
                                Update
                            </button>
                        </form>

                    </div>
                </div>
            </div>
            <!-- Register Card -->
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
        $('#formAuthentication').submit(function(e) {
            e.preventDefault();

            var password = $('#password').val();
            var confirm_password = $('#confirmpassword').val();
            var passwordname = false,
                conname = false;

            if (password.length < 1) {
                $('#password-error').removeClass('hide');
            } else {
                if (password.length < 8) {
                    $('#password-error').addClass('hide');
                    $('#password-invalid-error').removeClass('hide');
                } else {
                    $('#password-error').addClass('hide');
                    $('#password-invalid-error').addClass('hide');
                    passwordname = true;
                }
            }
            if (confirm_password.length < 1) {
                $('#confirm-error').removeClass('hide');
            } else if (confirm_password.length < 8) {
                $('#confirm-error').addClass('hide');
                $('#confirm-invalid-error').removeClass('hide');
            } else {
                if (password != confirm_password) {
                    $('#confirm-error').addClass('hide');
                    $('#confirm-invalid-error').addClass('hide');
                    $('#confirm-match-error').removeClass('hide');
                } else {
                    $('#confirm-error').addClass('hide');
                    $('#confirm-invalid-error').addClass('hide');
                    $('#confirm-match-error').addClass('hide');
                    conname = true;
                }
            }
            if(conname == true && passwordname == true){
              console.log('hi');
              document.getElementById("formAuthentication").submit();
            }
        });
    </script>
@endsection
