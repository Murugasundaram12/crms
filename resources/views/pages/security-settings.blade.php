@extends('layouts.app')

@section('title', 'Security Settings')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Settings</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Settings</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh"><i class="ti ti-refresh"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-transition-top"></i></a>
                    </div>
                </div>                
				<!-- End Page Header -->

				<div class="card border-0">
					<div class="card-body pb-0 pt-0 px-2">
						<ul class="nav nav-tabs nav-bordered nav-bordered-primary">
							<li class="nav-item me-3">
								<a href="{{ route('page', ['slug' => 'profile-settings']) }}" class="nav-link p-2 active">
									<i class="ti ti-settings-cog me-2"></i>General Settings
								</a>
							</li>
							<li class="nav-item me-3">
								<a href="{{ route('page', ['slug' => 'company-settings']) }}" class="nav-link p-2">
									<i class="ti ti-world-cog me-2"></i>Website Settings
								</a>
							</li>
							<li class="nav-item me-3">
								<a href="{{ route('page', ['slug' => 'invoice-settings']) }}" class="nav-link p-2">
									<i class="ti ti-apps me-2"></i>App Settings
								</a>
							</li>
							<li class="nav-item me-3">
								<a href="{{ route('page', ['slug' => 'email-settings']) }}" class="nav-link p-2">
									<i class="ti ti-device-laptop me-2"></i>System Settings
								</a>
							</li>
							<li class="nav-item me-3">
								<a href="{{ route('page', ['slug' => 'payment-gateways']) }}" class="nav-link p-2">
									<i class="ti ti-moneybag me-2"></i>Financial Settings
								</a>
							</li>
							<li class="nav-item">
								<a href="{{ route('page', ['slug' => 'sitemap']) }}" class="nav-link p-2">
									<i class="ti ti-flag-cog me-2"></i>Other Settings
								</a>
							</li>
						</ul>
					</div> <!-- end card body -->
				</div> <!-- end card -->
				
				<!-- start row -->
				<div class="row">

					<div class="col-xl-3 col-lg-12 theiaStickySidebar">

						<div class="card mb-3 mb-xl-0">
							<div class="card-body">
								<div class="settings-sidebar">
									<h5 class="mb-3 fs-17">General Settings</h5>
									<div class="list-group list-group-flush settings-sidebar">
										<a href="{{ route('page', ['slug' => 'profile-settings']) }}" class="d-block p-2 fw-medium">Profile</a>
										<a href="{{ route('page', ['slug' => 'security-settings']) }}" class="d-block p-2 fw-medium active">Security</a>
										<a href="{{ route('page', ['slug' => 'notifications-settings']) }}" class="d-block p-2 fw-medium">Notifications</a>
										<a href="{{ route('page', ['slug' => 'connected-apps']) }}" class="d-block p-2 fw-medium">Connected Apps</a>
									</div>
								</div>
							</div> <!-- end card body -->
						</div> <!-- end card -->

					</div> <!-- end col -->

					<div class="col-xl-9 col-lg-12">

						<div class="card mb-0">
							<div class="card-body pb-0">
								<div class="border-bottom mb-3 pb-3">
									<h5 class="mb-0 fs-17">Security Settings</h5>
								</div>

								<!-- start row -->
								<div class="row">
									
									<div class="col-lg-4 col-md-6 d-flex">
										<div class="card border shadow-none flex-fill mb-3">
											<div class="card-body d-flex justify-content-between flex-column">
												<div class="mb-3">
													<div class="d-flex align-items-center justify-content-between mb-1">
														<h6 class="fs-14 fw-semibold mb-0">Password</h6>
													</div>
													<p class="fs-13 mb-0">Last Changed 03 Jan 2025, 09:00 AM</p>
												</div>
												<div>
													<a href="javascript:void(0)" class="btn btn-xs btn-light"
														data-bs-toggle="modal"
														data-bs-target="#change_password">
														Change Password
													</a>
												</div>
											</div>
										</div>
									</div> <!-- end col -->

									<div class="col-lg-4 col-md-6 d-flex">
										<div class="card border shadow-none flex-fill mb-3">
											<div class="card-body d-flex justify-content-between flex-column">
												<div class="mb-3">
													<div
														class="d-flex align-items-center justify-content-between mb-1">
														<h6 class="fs-14 fw-semibold mb-0">Two Factor</h6>
														<div class="form-check form-switch">
															<input class="form-check-input" type="checkbox"
																role="switch" checked>
														</div>
													</div>
													<p class="fs-13 mb-0">Receive codes via SMS or email every time you login</p>
												</div>
												<div>
													<span class="badge badge-soft-success">Enabled</span>
												</div>
											</div>
										</div>
									</div> <!-- end col -->

									<div class="col-lg-4 col-md-6 d-flex">
										<div class="card border shadow-none flex-fill mb-3">
											<div class="card-body d-flex justify-content-between flex-column">
												<div class="mb-3">
													<div
														class="d-flex align-items-center justify-content-between mb-1">
														<h6 class="fs-14 fw-semibold mb-0">Google Authenticator</h6>
														<div class="form-check form-switch">
															<input class="form-check-input" type="checkbox"
																role="switch" checked>
														</div>
													</div>
													<p class="fs-13 mb-0">Google Authenticator adds an extra layer of security</p>
												</div>
												<div>
													<span class="badge badge-soft-success">Connected</span>
												</div>
											</div>
										</div>
									</div> <!-- end col -->

									<div class="col-lg-4 col-md-6 d-flex">
										<div class="card border shadow-none flex-fill mb-3">
											<div class="card-body d-flex justify-content-between flex-column">
												<div class="mb-3">
													<div
														class="d-flex align-items-center justify-content-between mb-1">
														<h6 class="fs-14 fw-semibold mb-0">Phone Number Verification<i
																class="ti ti-discount-check-filled text-success ms-1"></i>
														</h6>
													</div>
													<p class="fs-13 mb-0">Verified Mobile Number : <span
															class="text-dark">+99264710583</span></p>
												</div>
												<div class="d-flex align-items-center">
													<a href="javascript:void(0)" class="btn btn-xs btn-light me-2"
														data-bs-toggle="modal"
														data-bs-target="#change_phone_number">Change</a>
													<a href="javascript:void(0)"
														class="link-primary fs-12 fw-medium">Remove</a>
												</div>
											</div>
										</div>
									</div> <!-- end col -->

									<div class="col-lg-4 col-md-6 d-flex">
										<div class="card border shadow-none flex-fill mb-3">
											<div class="card-body d-flex justify-content-between flex-column">
												<div class="mb-3">
													<div
														class="d-flex align-items-center justify-content-between mb-1">
														<h6 class="fs-14 fw-semibold mb-0">Email Verification<i
																class="ti ti-discount-check-filled text-success ms-1"></i>
														</h6>
													</div>
													<p class="fs-13 mb-0">Verified Email : <span class="text-dark"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="f49d9a929bb4918c9599849891da979b99">[email&#160;protected]</a></span></p>
												</div>
												<div class="d-flex align-items-center">
													<a href="javascript:void(0)" class="btn btn-xs btn-light me-2"
														data-bs-toggle="modal"
														data-bs-target="#change_email">Change</a>
													<a href="javascript:void(0)"
														class="link-primary fs-12 fw-medium">Remove</a>
												</div>
											</div>
										</div>
									</div> <!-- end col -->

									<div class="col-lg-4 col-md-6 d-flex">
										<div class="card border shadow-none flex-fill mb-3">
											<div class="card-body d-flex justify-content-between flex-column">
												<div class="mb-3">
													<div
														class="d-flex align-items-center justify-content-between mb-1">
														<h6 class="fs-14 fw-semibold mb-0">Device Management</h6>
													</div>
													<p class="fs-13 mb-0">Last Changed 15 Jan 2025, 12:00 AM</p>
												</div>
												<div class="d-flex align-items-center">
													<a href="javascript:void(0)"
														class="btn btn-xs btn-light" data-bs-toggle="modal"
														data-bs-target="#device_management">Manage</a>
												</div>
											</div>
										</div>
									</div> <!-- end col -->

									<div class="col-lg-4 col-md-6 d-flex">
										<div class="card border shadow-none flex-fill mb-3">
											<div class="card-body d-flex justify-content-between flex-column">
												<div class="mb-3">
													<div
														class="d-flex align-items-center justify-content-between mb-1">
														<h6 class="fs-14 fw-semibold mb-0">Account Activity</h6>
													</div>
													<p class="fs-13 mb-0">Last Changed 20 Jan 2025, 11:30 AM</p>
												</div>
												<div class="d-flex align-items-center">
													<a href="javascript:void(0)" class="btn btn-xs btn-light" data-bs-toggle="modal"
														data-bs-target="#account_activity">View</a>
												</div>
											</div>
										</div>
									</div> <!-- end col -->

									<div class="col-lg-4 col-md-6 d-flex">
										<div class="card border shadow-none flex-fill mb-3">
											<div class="card-body d-flex justify-content-between flex-column">
												<div class="mb-3">
													<div
														class="d-flex align-items-center justify-content-between mb-1">
														<h6 class="fs-14 fw-semibold mb-0">Deactive Account</h6>
													</div>
													<p class="fs-13 mb-0">Last Changed 04 Mar 2023, 08:40 AM</p>
												</div>
												<div class="d-flex align-items-center">
													<a href="javascript:void(0)" class="btn btn-xs btn-light"
														data-bs-toggle="modal"
														data-bs-target="#deactive_account">Deactive</a>
												</div>
											</div>
										</div>
									</div> <!-- end col -->

									<div class="col-lg-4 col-md-6 d-flex">
										<div class="card border shadow-none flex-fill mb-3">
											<div class="card-body d-flex justify-content-between flex-column">
												<div class="mb-3">
													<div
														class="d-flex align-items-center justify-content-between mb-1">
														<h6 class="fs-14 fw-semibold mb-0">Delete Account</h6>
													</div>
													<p class="fs-13 mb-0">Last Changed 13 Mar 2023, 02:40 PM</p>
												</div>
												<div class="d-flex align-items-center">
													<a href="javascript:void(0)" class="btn btn-xs btn-light"
														data-bs-toggle="modal"
														data-bs-target="#delete_account">Delete Account</a>
												</div>
											</div>
										</div> <!-- end col -->

									</div>
									<!-- end row -->

								</div>
							</div> <!-- end card body -->
						</div> <!-- end card -->

					</div> <!-- end col -->
				
				</div>
				<!-- end row -->

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/intltelinput/css/intlTelInput.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/intltelinput/js/intlTelInput.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/ResizeSensor.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/theia-sticky-sidebar.js') }}"></script>
@endpush

