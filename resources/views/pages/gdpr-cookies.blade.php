@extends('layouts.app')

@section('title', 'Settings - GDPR')

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
								<a href="{{ route('page', ['slug' => 'profile-settings']) }}" class="nav-link p-2">
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
								<a href="{{ route('page', ['slug' => 'email-settings']) }}" class="nav-link p-2 active">
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
									<h5 class="mb-3 fs-17">System Settings</h5>
									<div class="list-group list-group-flush settings-sidebar">
										<a href="{{ route('page', ['slug' => 'email-settings']) }}" class="d-block p-2 fw-medium">Email Settings</a>
										<a href="{{ route('page', ['slug' => 'sms-gateways']) }}" class="d-block p-2 fw-medium">SMS Gateways</a>
										<a href="{{ route('page', ['slug' => 'gdpr-cookies']) }}" class="d-block p-2 fw-medium active">GDPR Cookies</a>
									</div>
								</div>
							</div> <!-- end card body -->
						</div> <!-- end card -->

					</div> <!-- end col -->

					<div class="col-xl-9 col-lg-12">

						<div class="card mb-0">
							<div class="card-body">
								<div class="border-bottom mb-3 pb-3">
									<h5 class="mb-0 fs-17">GDPR Cookies</h5>
										</div>
										<form action="https://crms.dreamstechnologies.com/html/template/gdpr-cookies.html">
											<div class="border-bottom mb-3">
												<div class="row align-items-center">
													<div class="col-md-6">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Cookies Content Text</h6>
															<p class="fs-13">You can configure the text here</p>
														</div>
													</div>
													<div class="col-md-6">
														<div class="mb-3">
															<div class="snow-editor"></div>
														</div>
													</div>
												</div>
												<div class="row align-items-center">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Cookies Position</h6>
															<p class="fs-13">You can configure the type</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>Right</option>
																<option>Left</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row align-items-center">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Agree Button Text</h6>
															<p class="fs-13">You can configure the text here</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<input type="text" class="form-control" value="Agree">
														</div>
													</div>
												</div>
												<div class="row align-items-center">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Decline Button Text</h6>
															<p class="fs-13">You can configure the text here</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<input type="text" class="form-control" value="Decline">
														</div>
													</div>
												</div>
												<div class="row align-items-center">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Show Decline Button</h6>
															<p class="fs-13">To display decline button</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<div class="form-check form-switch ms-0 ps-0">
																<input class="form-check-input ms-0 mt-0" type="checkbox" role="switch" checked>
															</div>
														</div>
													</div>
												</div>
												<div class="row align-items-center">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Link for Cookies Page</h6>
															<p class="fs-13">You can configure the link here</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<input type="text" class="form-control">
														</div>
													</div>
												</div>
											</div>
											<div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
												<a href="#" class="btn btn-sm btn-light me-2">Cancel</a>
												<button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
											</div>
										</form>
									</div>
								</div>
								<!-- /GDPR Cookies -->

							</div>
						</div>

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/quill/quill.snow.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/profile-upload.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/ResizeSensor.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/theia-sticky-sidebar.js') }}"></script>
<script src="{{ asset('assets/plugins/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/js/form-quilljs.js') }}"></script>
@endpush

