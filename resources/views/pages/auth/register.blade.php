@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    <div class="overflow-hidden p-3 acc-vh">
        <!-- start row -->
        <div class="row vh-100 w-100 g-0">
            <div class="col-lg-6 vh-100 overflow-y-auto overflow-x-hidden">
                <!-- start row -->
                <div class="row">
                    <div class="col-md-10 mx-auto">
                        <form action="{{ route('register') }}" method="POST"
                            class="vh-100 d-flex justify-content-between flex-column p-4 pb-0">
                            @csrf
                            <div class="text-center mb-4 auth-logo">
                                <img src="{{ asset('assets/img/logo.svg') }}" class="img-fluid" alt="Logo">
                            </div>
                            <div>
                                <div class="mb-3">
                                    <h3 class="mb-2">Create Account</h3>
                                    <p class="mb-0">Join CRMS platform to manage your business efficiently.</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                                        value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                                        <span class="input-group-text toggle-password">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <div class="input-group input-group-flat pass-group">
                                        <input type="password"
                                            class="form-control pass-input @error('password_confirmation') is-invalid @enderror"
                                            name="password_confirmation" required>
                                        @error('password_confirmation')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="input-group-text toggle-password-confirm">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary w-100">Create Account</button>
                                </div>
                                <div class="mb-3">
                                    <p class="mb-0">Already have an account? <a href="{{ route('login') }}"
                                            class="link-indigo fw-bold link-hover">Sign In</a></p>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.querySelector('.toggle-password');
            const passInput = document.querySelector('.pass-input');
            if (togglePassword && passInput) {
                togglePassword.addEventListener('click', function () {
                    const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('ti-eye');
                    this.querySelector('i').classList.toggle('ti-eye-off');
                });
            }

            const togglePasswordConfirm = document.querySelector('.toggle-password-confirm');
            const passInputConfirm = document.querySelector('input[name="password_confirmation"]');
            if (togglePasswordConfirm && passInputConfirm) {
                togglePasswordConfirm.addEventListener('click', function () {
                    const type = passInputConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
                    passInputConfirm.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('ti-eye');
                    this.querySelector('i').classList.toggle('ti-eye-off');
                });
            }
        });
    </script>
@endpush