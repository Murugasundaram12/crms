@extends('layouts.app')

@section('title', 'Settings - Currencies')

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
								<a href="{{ route('page', ['slug' => 'payment-gateways']) }}" class="nav-link p-2 active">
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
				<!-- /Settings Menu -->

				<!-- start row -->
				<div class="row">
					<div class="col-xl-3 col-lg-12 theiaStickySidebar">

						<!-- Settings Sidebar -->
						<div class="card">
							<div class="card-body">
								<div class="settings-sidebar">
									<h4 class="fs-17 mb-3">Financial Settings</h4>
									<div class="list-group list-group-flush settings-sidebar">
										<a href="{{ route('page', ['slug' => 'payment-gateways']) }}" class="d-block p-2 fw-medium">Payment Gateways</a>
										<a href="{{ route('page', ['slug' => 'bank-accounts']) }}" class="d-block p-2 fw-medium">Bank Accounts</a>
										<a href="{{ route('page', ['slug' => 'tax-rates']) }}" class="d-block p-2 fw-medium">Tax Rates</a>
										<a href="{{ route('page', ['slug' => 'currencies']) }}" class="d-block p-2 fw-medium active">Currencies</a>
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
									<h4 class="fs-17 mb-0">Currencies</h4>
									<a href="javascript:void(0)" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#add_currency"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add New Currency</a>
								</div>

								<!-- Start Table -->
								<div class="table-responsive custom-table">
									<table class="table table-nowrap">
										<thead class="table-light">
											<tr>
												<th>Currency</th>
												<th>Code</th>
												<th>Symbol</th>
												<th>Exchange Rate</th>
												<th>Status</th>
												<th>Action</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td>Dollar<a href="javascript:void(0);" class="badge badge-tag badge-soft-info ms-2" data-bs-toggle="modal" data-bs-target="#default">Default</a></td>
												<td>USD</td>
												<td>$</td>
												<td>01</td>
												<td><span class="badge bg-success">Active</span></td>
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow btn-icon btn-outline-light " data-bs-toggle="dropdown" aria-expanded="false">
															<i class="ti ti-dots-vertical"></i>
														</a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit_currency">
																<i class="ti ti-edit text-blue me-1"></i>Edit
															</a>
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_currency">
																<i class="ti ti-trash text-blue me-1"></i>Delete
															</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>Rupee</td>
												<td>INR</td>
												<td>Ã¢â€šÂ¹</td>
												<td>86.62</td>
												<td><span class="badge bg-success">Active</span></td>
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow btn-icon btn-outline-light " data-bs-toggle="dropdown" aria-expanded="false">
															<i class="ti ti-dots-vertical"></i>
														</a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit_currency">
																<i class="ti ti-edit text-blue me-1"></i>Edit
															</a>
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_currency">
																<i class="ti ti-trash text-blue me-1"></i>Delete
															</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>Pound</td>
												<td>GBP</td>
												<td>Ã‚Â£</td>
												<td>0.81</td>
												<td><span class="badge bg-success">Active</span></td>
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow btn-icon btn-outline-light " data-bs-toggle="dropdown" aria-expanded="false">
															<i class="ti ti-dots-vertical"></i>
														</a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit_currency">
																<i class="ti ti-edit text-blue me-1"></i>Edit
															</a>
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_currency">
																<i class="ti ti-trash text-blue me-1"></i>Delete
															</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>Euro</td>
												<td>EUR</td>
												<td>Ã¢â€šÂ¬</td>
												<td>0.96</td>
												<td><span class="badge bg-success">Active</span></td>
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow btn-icon btn-outline-light " data-bs-toggle="dropdown" aria-expanded="false">
															<i class="ti ti-dots-vertical"></i>
														</a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit_currency">
																<i class="ti ti-edit text-blue me-1"></i>Edit
															</a>
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_currency">
																<i class="ti ti-trash text-blue me-1"></i>Delete
															</a>
														</div>
													</div>
												</td>
											</tr>
											<tr>
												<td>Dhirams</td>
												<td>AED</td>
												<td>Ã˜Â¯.Ã˜Â¥</td>
												<td>3.67</td>
												<td><span class="badge bg-success">Active</span></td>
												<td>
													<div class="dropdown table-action">
														<a href="#" class="action-icon btn btn-xs shadow btn-icon btn-outline-light " data-bs-toggle="dropdown" aria-expanded="false">
															<i class="ti ti-dots-vertical"></i>
														</a>
														<div class="dropdown-menu dropdown-menu-right">
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit_currency">
																<i class="ti ti-edit text-blue me-1"></i>Edit
															</a>
															<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_currency">
																<i class="ti ti-trash text-blue me-1"></i>Delete
															</a>
														</div>
													</div>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
								<!-- End Table -->
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

