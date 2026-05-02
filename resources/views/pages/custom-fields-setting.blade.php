@extends('layouts.app')

@section('title', 'Settings - Custom Fields')

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
										<a href="{{ route('page', ['slug' => 'invoice-settings']) }}" class="d-block p-2 fw-medium">Invoice Settings</a>
										<a href="{{ route('page', ['slug' => 'printers-settings']) }}" class="d-block p-2 fw-medium">Printer</a>
										<a href="{{ route('page', ['slug' => 'custom-fields-setting']) }}" class="d-block p-2 fw-medium active">Custom Fields</a>
									</div>
								</div>
							</div> <!-- end card body -->
						</div> <!-- end card -->

					</div> <!-- end col -->

					<div class="col-xl-9 col-lg-12">
						<div class="card mb-0">
							<div class="card-body">
								<div class="border-bottom mb-3 pb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
									<h5 class="mb-0 fs-17">Custom Fields</h5>
									<a href="javascript:void(0)" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#add_fields"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add New Field</a>
								</div>
								<!-- start table -->
								<div class="table-responsive">
									<table class="table table-nowrap">
										<thead class="table-light">
											<tr>
												<th>Module</th>
												<th>Label</th>
												<th>Type</th>
												<th>Default Value</th>
												<th>Required</th>
												<th>status</th>
												<th>Action</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td>Contacts</td>
												<td>Preferred Language</td>
												<td>Select</td>
												<td>English</td>
												<td>
													<div class="form-check form-switch p-0">
														<label class="form-check-label d-flex align-items-center justify-content-center">
															<input class="form-check-input switchCheckDefault" type="checkbox" role="switch" checked>     
														</label>
													</div>
												</td>
												<td>
													<span class="badge badge-tag badge-soft-success">Connected</span>
												</td>
												
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow d-inline-flex btn-outline-light" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#edit_fields"><i class="ti ti-edit me-1"></i> Edit</a>
															<a class="dropdown-item d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#delete_fields"><i class="ti ti-trash me-1"></i> Delete</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>Projects</td>
												<td>Project Type</td>
												<td>Select</td>
												<td>Internal</td>
												<td>
													<div class="form-check form-switch p-0">
														<label class="form-check-label d-flex align-items-center justify-content-center">
															<input class="form-check-input switchCheckDefault" type="checkbox" role="switch" checked>     
														</label>
													</div>
												</td>
												<td>
													<span class="badge badge-tag badge-soft-success">Connected</span>
												</td>
												
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow d-inline-flex btn-outline-light" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#edit_fields"><i class="ti ti-edit me-1"></i> Edit</a>
															<a class="dropdown-item d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#delete_fields"><i class="ti ti-trash me-1"></i> Delete</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>Tasks</td>
												<td>Task Type</td>
												<td>Select</td>
												<td>Design</td>
												<td>
													<div class="form-check form-switch p-0">
														<label class="form-check-label d-flex align-items-center justify-content-center">
															<input class="form-check-input switchCheckDefault" type="checkbox" role="switch" checked>     
														</label>
													</div>
												</td>
												<td>
													<span class="badge badge-tag badge-soft-success">Connected</span>
												</td>
												
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow d-inline-flex btn-outline-light" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#edit_fields"><i class="ti ti-edit me-1"></i> Edit</a>
															<a class="dropdown-item d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#delete_fields"><i class="ti ti-trash me-1"></i> Delete</a>
														</div>
													</div>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
								<!-- end table -->
							</div>
						</div>
					</div> <!-- end col -->
				</div> <!-- end row -->
            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/profile-upload.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/ResizeSensor.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/theia-sticky-sidebar.js') }}"></script>
@endpush

