@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <div class="overflow-hidden p-3 acc-vh">
        <div class="row vh-100 w-100 g-0">
            <div class="col-lg-6 vh-100 overflow-y-auto overflow-x-hidden">
                <div class="row">
                    <div class="col-md-10 mx-auto">
                        <form action="{{ route('password.update') }}" method="POST"
                            class="vh-100 d-flex justify-content-between flex-column p-4 pb-0">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <div class="text-center mb-4 auth-logo">
                                <img src="{{ asset('assets/img/logo.svg') }}" class="img-fluid" alt="Logo">
                            </div>
                            <div>
                                <div class="mb-3">
                                    <h3 class="mb-2">Create New Password</h3>
                                    <p class="mb-0">Set a new password for your CRMS account.</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <div class="input-group input-group-flat">
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            name="email" value="{{ old('email', $email) }}" required autofocus>
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
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <div class="input-group input-group-flat pass-group">
                                        <input type="password" class="form-control pass-input"
                                            name="password_confirmation" required>
                                        <button class="input-group-text toggle-password" type="button"
                                            aria-label="Show password">
                                            <i class="ti ti-eye-off"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
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
