@extends('layouts.app')

@section('title', 'Settings - Prefixes')

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

						<div class="card">
							<div class="card-body">
								<div class="settings-sidebar">
									<h5 class="mb-3 fs-17">Website Settings</h5>
									<div class="list-group list-group-flush settings-sidebar">
										<a href="{{ route('page', ['slug' => 'company-settings']) }}" class="d-block p-2 fw-medium">Company Settings</a>
										<a href="{{ route('page', ['slug' => 'localization-settings']) }}" class="d-block p-2 fw-medium">Localization</a>
										<a href="{{ route('page', ['slug' => 'prefixes-settings']) }}" class="d-block p-2 fw-medium active">Prefixes</a>
										<a href="{{ route('page', ['slug' => 'preference-settings']) }}" class="d-block p-2 fw-medium">Preference</a>
										<a href="{{ route('page', ['slug' => 'appearance-settings']) }}" class="d-block p-2 fw-medium">Appearance</a>
										<a href="{{ route('page', ['slug' => 'language-settings']) }}" class="d-block p-2 fw-medium">Language</a>
									</div>
								</div>
							</div> <!-- end card body -->
						</div> <!-- end card -->

					</div> <!-- end col -->

					<div class="col-xl-9 col-lg-12">

						<div class="card">
							<div class="card-body">
								<div class="border-bottom mb-3 pb-3">
									<h5 class="mb-0 fs-17">Prefixes</h5>
								</div>
								<form action="https://crms.dreamstechnologies.com/html/template/prefixes-settings.html">
									<div class="border-bottom mb-3">

										<!-- start row -->
										<div class="row">
											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Products</label>
													<input type="text" class="form-control" value="SKU - ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Supplier</label>
													<input type="text" class="form-control" value="SUP - ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Purchase</label>
													<input type="text" class="form-control" value="PU - ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Purchase Return</label>
													<input type="text" class="form-control" value="PR - ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Sales</label>
													<input type="text" class="form-control" value="SA - ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Sales Return</label>
													<input type="text" class="form-control" value="SR -  ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Customer</label>
													<input type="text" class="form-control" value="CT - ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Expense</label>
													<input type="text" class="form-control" value="EX - ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Stock Transfer</label>
													<input type="text" class="form-control" value="ST -  ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Stock Adjustment</label>
													<input type="text" class="form-control" value="SA -  ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Sales Order</label>
													<input type="text" class="form-control" value="SO - ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Invoice</label>
													<input type="text" class="form-control" value="INV -  ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Estimation</label>
													<input type="text" class="form-control" value="EST - ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Transaction</label>
													<input type="text" class="form-control" value="TRN - ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Employee</label>
													<input type="text" class="form-control" value="EMP -  ">
												</div>
											</div> <!-- end col -->

											<div class="col-md-3 col-sm-6">
												<div class="mb-3">
													<label class="form-label">Purchase Return</label>
													<input type="text" class="form-control" value="PR -  ">
												</div>
											</div> <!-- end col -->

										</div>
										<!-- end row -->

									</div>
									<div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
										<a href="#" class="btn btn-sm btn-light">Cancel</a>
										<button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
									</div>
								</form>
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

