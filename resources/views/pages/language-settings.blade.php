@extends('layouts.app')

@section('title', 'Settings - Language')

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
										<a href="{{ route('page', ['slug' => 'prefixes-settings']) }}" class="d-block p-2 fw-medium">Prefixes</a>
										<a href="{{ route('page', ['slug' => 'preference-settings']) }}" class="d-block p-2 fw-medium">Preference</a>
										<a href="{{ route('page', ['slug' => 'appearance-settings']) }}" class="d-block p-2 fw-medium">Appearance</a>
										<a href="{{ route('page', ['slug' => 'language-settings']) }}" class="d-block p-2 fw-medium active">Language</a>
									</div>
								</div>
							</div> <!-- end card body -->
						</div> <!-- end card -->

					</div> <!-- end col -->

					<div class="col-xl-9 col-lg-12">

					<!-- Custom Fields -->
					<div class="card">
						<div class="card-body">
							<div class="border-bottom mb-3 pb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
								<h4 class="fs-17 mb-0">Language</h4>
								<div class="d-flex align-items-center gap-2">
									<div class="dropdown">
										<a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown"><i class="ti ti-language me-2"></i>Language</a>
										<div class="dropdown-menu  dropdown-menu-end">
											<ul>
												<li>
													<a href="javascript:void(0);" class="dropdown-item d-flex align-items-center gap-2"><img src="{{ asset('assets/img/flags/us.svg') }}" alt="Img" height="16">English</a>
												</li>
												<li>
													<a href="javascript:void(0);" class="dropdown-item d-flex align-items-center gap-2"><img src="{{ asset('assets/img/flags/de.svg') }}" alt="Img" height="16">German</a>
												</li>
												<li>
													<a href="javascript:void(0);" class="dropdown-item d-flex align-items-center gap-2"><img src="{{ asset('assets/img/flags/ae.svg') }}" alt="Img" height="16">Arabic</a>
												</li>
												<li>
													<a href="javascript:void(0);" class="dropdown-item d-flex align-items-center gap-2"><img src="{{ asset('assets/img/flags/fr.svg') }}" alt="Img" height="16">French</a>
												</li>
											</ul>
										</div>
									</div>
									<a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_lang"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Language</a>
								</div>
							</div>

							<!-- Contact List -->
							<div class="table-responsive custom-table mb-4">
								<table class="table table-nowrap">
									<thead class="table-light">
									<tr>
										<th>Language</th>
										<th>Code</th>
										<th>RTL</th>
										<th>Total</th>
										<th>Done</th>
										<th>Progress</th>
										<th>Status</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<a href="javascript:void(0);" class="d-flex align-items-center gap-2"><img src="{{ asset('assets/img/flags/us.svg') }}" alt="Img" height="16">English</a>
										</td>
										<td>en</td>
										<td>
											<div class="form-check form-switch p-0">
												<label class="form-check-label d-flex align-items-center gap-2 w-100">
													<input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
												</label>
											</div>
										</td>
										<td>3481</td>
										<td>2861</td>
										<td>
											<div class="pipeline-progress d-flex align-items-center w-100">
												<div class="progress w-100 bg-light" style="height: 5px; border-radius: 10px;">
													<div class="progress-bar bg-warning" role="progressbar" style="width: 80%; border-radius: 10px;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
												</div>
												<span class="ms-2 text-body">80%</span>
											</div>
										</td>
										<td>
											<a href="javascript:void(0);" class="badge bg-success">Connected</a>
										</td>
										<td class="d-flex align-items-center gap-2">
											<a href="{{ route('page', ['slug' => 'language-web']) }}" class="badge bg-light text-dark me-2">Web</a>
											<a href="javascript:void(0);" class="badge bg-light text-dark me-2">App</a>
											<a href="javascript:void(0);" class="badge bg-light text-dark me-2">Admin</a>
											<div class="dropdown table-action">
												<a href="#" class="action-icon btn btn-xs shadow btn-icon btn-outline-light " data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical"></i>
												</a>
												<div class="dropdown-menu dropdown-menu-right">
													<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit_lang">
														<i class="ti ti-edit text-blue me-1"></i>Edit
													</a>
													<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_lang">
														<i class="ti ti-trash text-blue me-1"></i>Delete
													</a>
												</div>
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<a href="javascript:void(0);" class="d-flex align-items-center gap-2"><img src="{{ asset('assets/img/flags/de.svg') }}" alt="Img" height="16">German</a>
										</td>
										<td>de</td>
										<td>
											<div class="form-check form-switch p-0">
												<label class="form-check-label d-flex align-items-center gap-2 w-100">
													<input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
												</label>
											</div>
										</td>
										<td>4815</td>
										<td>4815</td>
										<td>
											<div class="pipeline-progress d-flex align-items-center w-100">
												<div class="progress w-100 bg-light" style="height: 5px; border-radius: 10px;">
													<div class="progress-bar bg-success" role="progressbar" style="width: 100%; border-radius: 10px;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
												</div>
												<span class="ms-2 text-body">100%</span>
											</div>
										</td>
										<td>
											<a href="javascript:void(0);" class="badge bg-success">Connected</a>
										</td>
										<td class="d-flex align-items-center gap-2">
											<a href="{{ route('page', ['slug' => 'language-web']) }}" class="badge bg-light text-dark me-2">Web</a>
											<a href="javascript:void(0);" class="badge bg-light text-dark me-2">App</a>
											<a href="javascript:void(0);" class="badge bg-light text-dark me-2">Admin</a>
											<div class="dropdown table-action">
												<a href="#" class="action-icon btn btn-xs shadow btn-icon btn-outline-light " data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical"></i>
												</a>
												<div class="dropdown-menu dropdown-menu-right">
													<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit_lang">
														<i class="ti ti-edit text-blue me-1"></i>Edit
													</a>
													<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_lang">
														<i class="ti ti-trash text-blue me-1"></i>Delete
													</a>
												</div>
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<a href="javascript:void(0);" class="d-flex align-items-center gap-2"><img src="{{ asset('assets/img/flags/ae.svg') }}" alt="Img" height="16">Arabic</a>
										</td>
										<td>ar</td>
										<td>
											<div class="form-check form-switch p-0">
												<label class="form-check-label d-flex align-items-center gap-2 w-100">
													<input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
												</label>
											</div>
										</td>
										<td>2590</td>
										<td>20</td>
										<td>
											<div class="pipeline-progress d-flex align-items-center w-100">
												<div class="progress w-100 bg-light" style="height: 5px; border-radius: 10px;">
													<div class="progress-bar bg-primary" role="progressbar" style="width: 50%; border-radius: 10px;" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
												</div>
												<span class="ms-2 text-body">50%</span>
											</div>
										</td>
										<td>
											<a href="javascript:void(0);" class="badge bg-success">Connected</a>
										</td>
										<td class="d-flex align-items-center gap-2">
											<a href="{{ route('page', ['slug' => 'language-web']) }}" class="badge bg-light text-dark me-2">Web</a>
											<a href="javascript:void(0);" class="badge bg-light text-dark me-2">App</a>
											<a href="javascript:void(0);" class="badge bg-light text-dark me-2">Admin</a>
											<div class="dropdown table-action">
												<a href="#" class="action-icon btn btn-xs shadow btn-icon btn-outline-light " data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical"></i>
												</a>
												<div class="dropdown-menu dropdown-menu-right">
													<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit_lang">
														<i class="ti ti-edit text-blue me-1"></i>Edit
													</a>
													<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_lang">
														<i class="ti ti-trash text-blue me-1"></i>Delete
													</a>
												</div>
											</div>
										</td>
									</tr>
									<tr>
										<td>
											<a href="javascript:void(0);" class="d-flex align-items-center gap-2"><img src="{{ asset('assets/img/flags/fr.svg') }}" alt="Img" height="16">English</a>
										</td>
										<td>fr</td>
										<td>
											<div class="form-check form-switch p-0">
												<label class="form-check-label d-flex align-items-center gap-2 w-100">
													<input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
												</label>
											</div>
										</td>
										<td>1892</td>
										<td>387</td>
										<td>
											<div class="pipeline-progress d-flex align-items-center w-100">
												<div class="progress w-100 bg-light" style="height: 5px; border-radius: 10px;">
													<div class="progress-bar bg-purple" role="progressbar" style="width: 40%; border-radius: 10px;" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
												</div>
												<span class="ms-2 text-body">40%</span>
											</div>
										</td>
										<td>
											<a href="javascript:void(0);" class="badge bg-success">Connected</a>
										</td>
										<td class="d-flex align-items-center gap-2">
											<a href="{{ route('page', ['slug' => 'language-web']) }}" class="badge bg-light text-dark me-2">Web</a>
											<a href="javascript:void(0);" class="badge bg-light text-dark me-2">App</a>
											<a href="javascript:void(0);" class="badge bg-light text-dark me-2">Admin</a>
											<div class="dropdown table-action">
												<a href="#" class="action-icon btn btn-xs shadow btn-icon btn-outline-light " data-bs-toggle="dropdown" aria-expanded="false">
													<i class="ti ti-dots-vertical"></i>
												</a>
												<div class="dropdown-menu dropdown-menu-right">
													<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#edit_lang">
														<i class="ti ti-edit text-blue me-1"></i>Edit
													</a>
													<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#delete_lang">
														<i class="ti ti-trash text-blue me-1"></i>Delete
													</a>
												</div>
											</div>
										</td>
									</tr>
								</tbody>
								</table>
							</div>
							<div class="row align-items-center">
								<div class="col-md-6">
									<div class="datatable-length"></div>
								</div>
								<div class="col-md-6">
									<div class="datatable-paginate"></div>
								</div>
							</div>
							<!-- /Contact List -->

						</div>
					</div>
					<!-- /Custom Fields -->

				</div>
			</div>

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/ResizeSensor.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/theia-sticky-sidebar.js') }}"></script>
@endpush

