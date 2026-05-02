@extends('layouts.app')

@section('title', 'Settings - Localization')

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
								<a href="{{ route('page', ['slug' => 'company-settings']) }}" class="nav-link p-2 active">
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
									<h5 class="mb-3 fs-17">Website Settings</h5>
									<div class="list-group list-group-flush settings-sidebar">
										<a href="{{ route('page', ['slug' => 'company-settings']) }}" class="d-block p-2 fw-medium">Company Settings</a>
										<a href="{{ route('page', ['slug' => 'localization-settings']) }}" class="d-block p-2 fw-medium active">Localization</a>
										<a href="{{ route('page', ['slug' => 'prefixes-settings']) }}" class="d-block p-2 fw-medium">Prefixes</a>
										<a href="{{ route('page', ['slug' => 'preference-settings']) }}" class="d-block p-2 fw-medium">Preference</a>
										<a href="{{ route('page', ['slug' => 'appearance-settings']) }}" class="d-block p-2 fw-medium">Appearance</a>
										<a href="{{ route('page', ['slug' => 'language-settings']) }}" class="d-block p-2 fw-medium">Language</a>
									</div>
								</div>
							</div> <!-- end card body -->
						</div> <!-- end card -->

					</div> <!-- end col -->

					<div class="col-xl-9 col-lg-12">

								<!-- Prefixes -->
								<div class="card mb-0">
									<div class="card-body">
										<div class="border-bottom mb-3 pb-3">
											<h5 class="mb-0 fs-17">Localization</h5>
										</div>
										<form action="https://crms.dreamstechnologies.com/html/template/localization-settings.html">
											<div class="mb-3">
												<h6 class="mb-1">Basic Information</h6>
												<p class="mb-0">Provide the basic information below</p>
											</div>
											<div class="border-bottom mb-3">
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Language</h6>
															<p class="fs-13 mb-0">Select Language of the website</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>English</option>
																<option>French</option>
																<option>German</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Language Switcher</h6>
															<p class="fs-13 mb-0">To display in all the pages</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<div class="form-check form-switch ms-0 ps-0">
																<input class="form-check-input ms-0" type="checkbox" role="switch" checked>
															</div>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Timezone</h6>
															<p class="fs-13 mb-0">Select date format to display in website</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>UTC 4:30</option>
																<option>(UTC+11:00) INR</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Date Format</h6>
															<p class="fs-13 mb-0">Select Language of the website</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>01 Jan 2025</option>
																<option>01-Jan-2025</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Time Format</h6>
															<p class="fs-13 mb-0">Select time format to display in website</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>12 Hours</option>
																<option>24 Hours</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Financial Year</h6>
															<p class="fs-13 mb-0">Select year for finance</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>2025</option>
																<option>2024</option>
																<option>2022</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Starting Month</h6>
															<p class="fs-13 mb-0">Select starting month to display</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>January</option>
																<option>February</option>
																<option>March</option>
															</select>
														</div>
													</div>
												</div>
											</div>
											<div class="mb-3">
												<h6 class="mb-1">Currency Settings</h6>
												<p>Provide the currency information below</p>
											</div>
											<div class="border-bottom mb-3">
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Currency</h6>
															<p class="fs-13 mb-0">Select currency</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>USD</option>
																<option>Dollar</option>
																<option>Euro</option>
																<option>Pound</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Currency Symbol</h6>
															<p class="fs-13 mb-0">Select currency symbol</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>$</option>
																<option>Ã¢â€šÂ¬</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Currency Position</h6>
															<p class="fs-13 mb-0">Select currency position</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>$100</option>
																<option>100$</option>
																<option>$ 100</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Decimal Seperator</h6>
															<p class="fs-13 mb-0">Select decimal seperator</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>.</option>
																<option>,</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Thousand Seperator</h6>
															<p class="fs-13 mb-0">Select thousand seperator</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>,</option>
																<option>.</option>
															</select>
														</div>
													</div>
												</div>
											</div>
											<div class="mb-3">
												<h6 class="mb-1">Country Settings</h6>
												<p class="mb-0">Provide the country information below</p>
											</div>
											<div class="border-bottom mb-3">
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Countries Restriction</h6>
															<p class="mb-0">Select restricted countries</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<select class="select">
																<option selected>Allow All Countries</option>
																<option>Deny All Countries</option>
															</select>
														</div>
													</div>
												</div>
											</div>
											<div class="mb-3">
												<h6 class="mb-1">File Settings</h6>
												<p class="mb-0">Provide the files information below</p>
											</div>
											<div class="border-bottom mb-3 border-0">
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Allowed Files</h6>
															<p class="fs-13 mb-0">Select allowed files</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<input class="form-control" id="choices-text-remove-button" data-choices data-choices-removeItem type="text" value="JPG, PNG, GIF">
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-8">
														<div class="mb-3">
															<h6 class="fs-14 fw-semibold mb-1">Max File Size</h6>
															<p class="fs-13 mb-0">Select size of the files</p>
														</div>
													</div>
													<div class="col-md-4">
														<div class="mb-3">
															<input type="text" class="form-control" value="5000MB">
														</div>
													</div>
												</div>
											</div>
											<div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
												<a href="#" class="btn btn-sm btn-light">Cancel</a>
												<button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
											</div>
										</form>
									</div>
								</div>
								<!-- /Prefixes -->

							</div>
						</div>

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/choices.js/public/assets/styles/choices.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/ResizeSensor.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/theia-sticky-sidebar.js') }}"></script>
<script src="{{ asset('assets/plugins/choices.js/public/assets/scripts/choices.min.js') }}"></script>
@endpush

