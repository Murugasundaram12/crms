@extends('layouts.app')

@section('title', 'Settings - Ban IP Address')

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

				<!-- Settings Menu -->
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
								<a href="{{ route('page', ['slug' => 'sitemap']) }}" class="nav-link p-2 active">
									<i class="ti ti-flag-cog me-2"></i>Other Settings
								</a>
							</li>
						</ul>
					</div> <!-- end card body -->
				</div> <!-- end card -->
				<!-- /Settings Menu -->

				<!-- start row -->
				<div class="row row-gap-3">
					<div class="col-xl-3 col-lg-12 theiaStickySidebar">

						<!-- Settings Sidebar -->
						<div class="card mb-0">
							<div class="card-body">
								<div class="settings-sidebar">
									<h4 class="fs-17 mb-3">Other Settings</h4>
									<div class="list-group list-group-flush settings-sidebar">
										<a href="{{ route('page', ['slug' => 'sitemap']) }}" class="d-block p-2 fw-medium">Sitemap</a>
										<a href="{{ route('page', ['slug' => 'clear-cache']) }}" class="d-block p-2 fw-medium">Clear Cache </a>
										<a href="{{ route('page', ['slug' => 'storage']) }}" class="d-block p-2 fw-medium">Storage</a>
										<a href="{{ route('page', ['slug' => 'cronjob']) }}" class="d-block p-2 fw-medium">Cronjob</a>
										<a href="{{ route('page', ['slug' => 'ban-ip-address']) }}" class="d-block p-2 fw-medium active">Ban IP Address</a>
										<a href="{{ route('page', ['slug' => 'system-backup']) }}" class="d-block p-2 fw-medium">System Backup</a>
										<a href="{{ route('page', ['slug' => 'database-backup']) }}" class="d-block p-2 fw-medium">Database Backup</a>
										<a href="{{ route('page', ['slug' => 'system-update']) }}" class="d-block p-2 fw-medium">System Update</a>
									</div>
								</div>
							</div>
						</div>
						<!-- /Settings Sidebar -->

					</div>

					<div class="col-xl-9 col-lg-12">

						<!-- Settings Info -->
						<div class="card mb-0">
							<div class="card-body">
								<div class="border-bottom mb-3 pb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
									<h4 class="fs-17 mb-0">Ban IP Address</h4>
									<a href="javascript:void(0)" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#add_ip"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add New Ban IP </a>
								</div>

								<!-- start table -->
								<div class="table-responsive">
									<table class="table table-nowrap">
										<thead class="table-light">
											<tr>
												<th>Ban Ip Address</th>
												<th>Reason</th>
												<th>Created On</th>
												<th>Action</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td>211.11.0.25</td>
												<td>Suspicious Activity</td>
												<td>22 Feb 2025</td>
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow d-inline-flex btn-outline-light" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_ip"><i class="ti ti-edit text-blue"></i> Edit</a>
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_ip"><i class="ti ti-trash"></i> Delete</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>211.03.0.11</td>
												<td>Spam or Abuse</td>
												<td>10 Feb 2025</td>
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow d-inline-flex btn-outline-light" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_ip"><i class="ti ti-edit text-blue"></i> Edit</a>
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_ip"><i class="ti ti-trash"></i> Delete</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>211.24.0.17</td>
												<td>Unauthorized Access</td>
												<td>17 Jan 2025</td>
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow d-inline-flex btn-outline-light" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_ip"><i class="ti ti-edit text-blue"></i> Edit</a>
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_ip"><i class="ti ti-trash"></i> Delete</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>211.12.0.34</td>
												<td>Violation of Terms</td>
												<td>07 Jan 2025</td>
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow d-inline-flex btn-outline-light" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_ip"><i class="ti ti-edit text-blue"></i> Edit</a>
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_ip"><i class="ti ti-trash"></i> Delete</a>
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
						<!-- /Settings Info -->

					</div>
				</div>
				<!-- end row -->

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/ResizeSensor.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/theia-sticky-sidebar.js') }}"></script>
@endpush

