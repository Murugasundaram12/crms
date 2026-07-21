@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <div class="overflow-hidden p-3 acc-vh">
        <div class="row vh-100 w-100 g-0">
            <div class="col-lg-6 vh-100 overflow-y-auto overflow-x-hidden">
                <div class="row">
                    <div class="col-md-10 mx-auto">
                        <form action="{{ route('password.email') }}" method="POST"
                            class="vh-100 d-flex justify-content-between flex-column p-4 pb-0">
                            @csrf
                            <div class="text-center mb-4 auth-logo">
                                <img src="{{ asset('assets/img/logo.svg') }}" class="img-fluid" alt="Logo">
                            </div>
                            <div>
                                <div class="mb-3">
                                    <h3 class="mb-2">Reset Password</h3>
                                    <p class="mb-0">Enter your email address and we will send a password reset link.</p>
                                </div>
                                @if (session('status'))
                                    <div class="alert alert-success">{{ session('status') }}</div>
                                @endif
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group input-group-flat">
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            name="email" value="{{ old('email') }}" required autofocus>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="input-group-text">
                                            <i class="ti ti-mail"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                                </div>
                                <div class="mb-3">
                                    <p class="mb-0"><a href="{{ route('login') }}" class="link-indigo fw-bold link-hover">Back to sign in</a></p>
                                </div>
                            </div>
                            <div class="text-center pb-4">
                                <p class="text-dark mb-0">Copyright &copy; {{ date('Y') }} - CRMS</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 account-bg-01 d-none d-lg-block"></div>
        </div>
    </div>
@endsection
