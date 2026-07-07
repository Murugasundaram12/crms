@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <div class="overflow-hidden p-3 acc-vh">
        <!-- start row -->
        <div class="row vh-100 w-100 g-0">
            <div class="col-lg-6 vh-100 overflow-y-auto overflow-x-hidden">
                <!-- start row -->
                <div class="row">
                    <div class="col-md-10 mx-auto">
                        <form action="{{ route('login') }}" method="POST"
                            class="vh-100 d-flex justify-content-between flex-column p-4 pb-0">
                            @csrf
                            <div class="text-center mb-4 auth-logo">
                                <img src="{{ asset('assets/img/logo.svg') }}" class="img-fluid" alt="Logo">
                            </div>
                            <div>
                                <div class="mb-3">
                                    <h3 class="mb-2">Sign In</h3>
                                    <p class="mb-0">Access the CRMS panel using your email and passcode.</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group input-group-flat">
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            name="email" value="{{ old('email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="input-group-text">
                                            <i class="ti ti-mail"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group input-group-flat pass-group">
                                        <input type="password"
                                            class="form-control pass-input @error('password') is-invalid @enderror"
                                            name="password" required>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <button class="input-group-text toggle-password" type="button"
                                            aria-label="Show password">
                                            <i class="ti ti-eye-off"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="form-check form-check-md d-flex align-items-center">
                                        <input class="form-check-input mt-0" type="checkbox" name="remember" value="1"
                                            id="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label text-dark ms-1" for="remember">
                                            Remember Me
                                        </label>
                                    </div>
                                    <div class="text-end">
                                        <a href="#" class="link-danger fw-medium link-hover"
                                            onclick="alert('Password reset coming soon!')">Forgot Password?</a>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary w-100">Sign In</button>
                                </div>
                                <div class="mb-3">
                                    <p class="mb-0">New on our platform?<a href="{{ route('register') }}"
                                            class="link-indigo fw-bold link-hover"> Create an account</a></p>
                                </div>
                            </div>
                            <div class="text-center pb-4">
                                <p class="text-dark mb-0">Copyright &copy; {{ date('Y') }} - CRMS</p>
                            </div>
                        </form>
                    </div> <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <div class="col-lg-6 account-bg-01 d-none d-lg-block"></div> <!-- end col -->
        </div>
        <!-- end row -->
    </div>
@endsection
