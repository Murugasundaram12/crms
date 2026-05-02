@extends('layouts.app')

@section('title', 'Settings - Invoice')

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
								<a href="{{ route('page', ['slug' => 'invoice-settings']) }}" class="nav-link p-2 active">
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
									<h5 class="mb-3 fs-17">App Settings</h5>
									<div class="list-group list-group-flush settings-sidebar">
										<a href="{{ route('page', ['slug' => 'invoice-settings']) }}" class="d-block p-2 fw-medium active">Invoice Settings</a>
										<a href="{{ route('page', ['slug' => 'printers-settings']) }}" class="d-block p-2 fw-medium">Printer</a>
										<a href="{{ route('page', ['slug' => 'custom-fields-setting']) }}" class="d-block p-2 fw-medium">Custom Fields</a>
									</div>
								</div>
							</div> <!-- end card body -->
						</div> <!-- end card -->

					</div> <!-- end col -->

					<div class="col-xl-9 col-lg-12">

						<div class="card mb-0">
							<div class="card-body">
								<div class="border-bottom mb-3 pb-3">
									<h5 class="mb-0 fs-17">Invoice Settings</h5>
								</div>
										<form action="https://crms.dreamstechnologies.com/html/template/invoice-settings.html">
											<div class="border-bottom mb-3">
											<div class="row">
												<div class="col-md-6">
													<div class="mb-3">
														<h6 class="fs-14 fw-semibold mb-1">Invoice Logo</h6>
														<p class="fs-13 mb-0">Upload logo of your company to display in invoice</p>
													</div>
												</div>
												<div class="col-md-6">
													<div class="mb-3">
														<div class="profile-upload d-flex align-items-center">
															<div class="profile-upload-img avatar avatar-xxl border border-dashed rounded position-relative flex-shrink-0">
																<span><i class="ti ti-photo"></i></span>
																<img id="ImgPreview" src="{{ asset('assets/img/profiles/avatar-02.jpg') }}" alt="img" class="preview1">
																<a href="javascript:void(0);" id="removeImage1" class="profile-remove">
																	<i class="ti ti-x"></i>
																</a>
															</div>
															<div class="profile-upload-content ms-3">
																<label class="d-inline-flex align-items-center position-relative btn btn-primary btn-sm mb-2">
																	<i class="ti ti-file-broken me-1"></i>Upload File
																	<input type="file" id="imag" class="input-img position-absolute w-100 h-100 opacity-0 top-0 end-0">
																</label>
																<p class="mb-0">Upload Logo of your company to display in website. Recommended size is 250 px*100 px</p>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-md-8">
													<div class="mb-3">
														<h6 class="fs-14 fw-semibold mb-1">Invoice Prefix</h6>
														<p class="fs-13 mb-0">Add prefix to your invoice</p>
													</div>
												</div>
												<div class="col-md-4">
													<div class="mb-3">
														<input type="text" class="form-control" value="INV-">
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-md-8">
													<div class="mb-3">
														<h6 class="fs-14 fw-semibold mb-1">Invoice Due</h6>
														<p class="fs-13 mb-0">Select due date to display in invoice</p>
													</div>
												</div>
												<div class="col-md-4">
													<div class="mb-3">
														<div class="d-flex align-items-center inv-days">
															<div class="me-2">
																<select class="select">
																	<option selected>5</option>
																	<option>7</option>
																</select>
															</div>
															<p class="fs-13 mb-0">Days</p>
														</div>
													</div>
												</div>
											</div>
											<div class="row align-items-center">
												<div class="col-md-8">
													<div class="mb-3">
														<h6 class="fs-14 fw-semibold mb-1">Invoice Round Off</h6>
														<p class="fs-13 mb-0">Value roundoff in invoice</p>
													</div>
												</div>
												<div class="col-md-4">
													<div class="mb-3">
														<div class="d-flex align-items-center">
															<div class="form-check form-switch me-2">
																<input class="form-check-input" type="checkbox"
																	role="switch" checked>
															</div>
															<div class="w-100">
																<select class="select">
																	<option selected>Roundoff Up</option>
																	<option>Roundoff Down</option>
																</select>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="row align-items-center">
												<div class="col-md-8">
													<div class="mb-3">
														<h6 class="fs-14 fw-semibold mb-1">Show Company Details</h6>
														<p class="fs-13 mb-0">Show/hide company details in invoice</p>
													</div>
												</div>
												<div class="col-md-4">
													<div class="mb-3">
														<div class="form-check form-switch">
															<input class="form-check-input" type="checkbox"
																role="switch" checked>
														</div>
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-md-6">
													<div class="mb-3">
														<h6 class="fs-14 fw-semibold mb-1">Invoice Footer Terms</h6>
														<p class="fs-13 mb-0">Enter terms that will appear on All Proposals by default.</p>
													</div>
												</div>
												<div class="col-md-6">
													<div class="mb-3">
														<div class="snow-editor"></div>
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
								<!-- /Invoice Settings -->

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

