@extends('layouts.app')

@section('title', 'Settings - Connected Apps')

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
										<a href="{{ route('page', ['slug' => 'security-settings']) }}" class="d-block p-2 fw-medium">Security</a>
										<a href="{{ route('page', ['slug' => 'notifications-settings']) }}" class="d-block p-2 fw-medium">Notifications</a>
										<a href="{{ route('page', ['slug' => 'connected-apps']) }}" class="d-block p-2 fw-medium active">Connected Apps</a>
									</div>
								</div>
							</div> <!-- end card body -->
						</div> <!-- end card -->

					</div> <!-- end col -->

					<div class="col-xl-9 col-lg-12">

						<div class="card mb-0">
							<div class="card-body pb-0">
								<div class="border-bottom mb-3 pb-3">
									<h5 class="mb-0 fs-17">Connected Apps</h5>
								</div>

								<!-- start row -->
								<div class="row">

									<div class="col-md-4 col-sm-6">
										<div class="card border mb-3">
											<div class="card-body">
												<div
													class="d-flex align-items-center justify-content-between mb-3">
													<span class="avatar rounded bg-light p-2">
														<img src="{{ asset('assets/img/icons/integration-01.svg') }}" alt="Icon">
													</span>
													<div class="connect-btn">
														<a href="javascript:void(0);" class="badge badge-soft-success">Connected</a>
													</div>
												</div>
												<div class="d-flex align-items-center justify-content-between">
													<p class="fw-medium text-dark  mb-0">Google Calendar</p>
													<div class="form-check form-switch">
														<input class="form-check-input ms-0" type="checkbox" role="switch" checked>
													</div>
												</div>
											</div>
										</div>
									</div> <!-- end col -->		

									<div class="col-md-4 col-sm-6">
										<div class="card border mb-3">
											<div class="card-body">
												<div
													class="d-flex align-items-center justify-content-between mb-3">
													<span class="avatar rounded bg-light p-2">
														<img src="{{ asset('assets/img/icons/integration-03.svg') }}" alt="Icon">
													</span>
													<div class="connect-btn">
														<a href="javascript:void(0);" class="badge badge-soft-success">Connected</a>
													</div>
												</div>
												<div class="d-flex align-items-center justify-content-between">
													<p class="fw-medium text-dark  mb-0">Dropbox</p>
													<div class="form-check form-switch">
														<input class="form-check-input ms-0" type="checkbox" role="switch" checked>
													</div>
												</div>
											</div>
										</div>
									</div> <!-- end col -->		

									<div class="col-md-4 col-sm-6">
										<div class="card border mb-3">
											<div class="card-body">
												<div
													class="d-flex align-items-center justify-content-between mb-3">
													<span class="avatar rounded bg-light p-2">
														<img src="{{ asset('assets/img/icons/integration-04.svg') }}" alt="Icon">
													</span>
													<div class="connect-btn">
														<a href="javascript:void(0);"
															class="badge border badge-soft-success">Connected</a>
													</div>
												</div>
												<div class="d-flex align-items-center justify-content-between">
													<p class="fw-medium text-dark  mb-0">Slack</p>
													<div class="form-check form-switch">
														<input class="form-check-input ms-0" type="checkbox" role="switch" checked>
													</div>
												</div>
											</div>
										</div>
									</div> <!-- end col -->		
									
									<div class="col-md-4 col-sm-6">
										<div class="card border mb-3">
											<div class="card-body">
												<div
													class="d-flex align-items-center justify-content-between mb-3">
													<span class="avatar rounded bg-light p-2">
														<img src="{{ asset('assets/img/icons/integration-05.svg') }}" alt="Icon">
													</span>
													<div class="connect-btn">
														<a href="javascript:void(0);" class="badge badge-soft-success">Connected</a>
													</div>
												</div>
												<div class="d-flex align-items-center justify-content-between">
													<p class="fw-medium text-dark  mb-0">Gmail</p>
													<div class="form-check form-switch">
														<input class="form-check-input ms-0" type="checkbox" role="switch" checked>
													</div>
												</div>
											</div>
										</div>
									</div> <!-- end col -->		
									
									<div class="col-md-4 col-sm-6">
										<div class="card border mb-3">
											<div class="card-body">
												<div
													class="d-flex align-items-center justify-content-between mb-3">
													<span class="avatar rounded bg-light p-2">
														<img src="{{ asset('assets/img/icons/integration-06.svg') }}" alt="Icon">
													</span>
													<div class="connect-btn">
														<a href="javascript:void(0);"
															class="badge badge-soft-success">Connect</a>
													</div>
												</div>
												<div class="d-flex align-items-center justify-content-between">
													<p class="fw-medium text-dark  mb-0">Github</p>
													<div class="form-check form-switch">
														<input class="form-check-input ms-0" type="checkbox" role="switch" checked>
													</div>
												</div>
											</div>
										</div>
									</div> <!-- end col -->							

								</div>
							</div> <!-- end card body -->
						</div> <!-- end card -->

					</div> <!-- end col -->
				
				</div>
				<!-- end row -->

            </div>
@endsection

@push('scripts')
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/ResizeSensor.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/theia-sticky-sidebar.js') }}"></script>
@endpush

