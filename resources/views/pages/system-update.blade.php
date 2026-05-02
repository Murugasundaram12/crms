@extends('layouts.app')

@section('title', 'Settings - System Update')

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
                                        <a href="{{ route('page', ['slug' => 'ban-ip-address']) }}" class="d-block p-2 fw-medium">Ban IP Address</a>
                                        <a href="{{ route('page', ['slug' => 'system-backup']) }}" class="d-block p-2 fw-medium">System Backup</a>
                                        <a href="{{ route('page', ['slug' => 'database-backup']) }}" class="d-block p-2 fw-medium">Database Backup</a>
                                        <a href="{{ route('page', ['slug' => 'system-update']) }}" class="d-block p-2 fw-medium active">System Update</a>
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
                                    <h4 class="fs-17 mb-0">System Update</h4>
                                </div>
                                <!-- Item -->
                                <div class="mb-4">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <div class="position-relative z-1">
                                            <span class="avatar avatar-lg badge-soft-success badge-sm border-0 text-success rounded-circle"><i class="ti ti-circle-check-filled fs-24"></i></span>
                                        </div>
                                        <div>
                                            <h6 class="fs-14 mb-1 fw-semibold"> You are up to date <span class="badge badge-tag badge-soft-info ms-2">Default</span> </h6>
                                            <p class="fs-13 mb-0">Last Checked : Today 10:30AM</p>
                                        </div>
                                    </div>
                                </div>

								<!-- Item -->
                                <div class="mb-3">
                                    <div class="w-100">
                                        <label class="form-label">Purchase Key<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                                <!-- Item -->
                                <div class="bg-light border rounded p-2 d-flex align-items-center">
                                    <p class="mb-0"> <i class="ti ti-info-circle me-2 text-info"></i> Before updating, it's best to back up your files and database and review the changelog.</p>
                                </div>
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

