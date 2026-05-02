@extends('layouts.app')

@section('title', 'Product Details')
@section('content_class', 'pb-0')

@section('content')
<div class="row">
					<div class="col-md-12">

						<div class="mb-3">
							<a href="{{ route('page', ['slug' => 'products']) }}"><i class="ti ti-arrow-narrow-left me-1"></i>Back to Products</a>
						</div>

					</div>

					<!-- Contact Sidebar -->
					<div class="col-xl-4">
						<div class="card">
							<div class="card-body p-3">
								<h6 class="mb-3 fw-semibold fs-14">Product Information</h6>
								<div class="d-flex align-items-center gap-1 border-bottom pb-3 mb-3">
                                    <span class="avatar avatar-lg bg-light p-0 flex-shrink-0 rounded-circle text-dark me-2"><i class="ti ti-box fs-24"></i></span>
                                    <div>
                                        <h6 class="mb-1 d-flex align-items-center gap-2">Barcode Scanner <span class="badge bg-success">Active</span></h6>
                                        <span>Hardware</span>
                                    </div>
                                </div>
								<h6 class="mb-3 fw-semibold fs-14">Other Information</h6>
								<ul class="mb-3">
									<li class="row mb-2"><span class="col-6">Product ID</span ><span class="col-6 text-dark">PRD114</span></li>
									<li class="row mb-2"><span class="col-6">SKU</span><span class="col-6 text-dark">BARHARD</span></li>
									<li class="row mb-2"><span class="col-6">Cost Price ($)</span><span class="col-6 text-dark">8965</span></li>
                                    <li class="row mb-2"><span class="col-6">Selling Price ($)</span><span class="col-6 text-dark">7500</span></li>
                                    <li class="row mb-2"><span class="col-6">Tax (%)</span><span class="col-6 text-dark">18</span></li>
									<li class="row"><span class="col-6">Created On</span><span class="col-6 text-dark">15 Feb 2025, 02:02 PM</span></li>
								</ul>
                                <a href="#" class="btn btn-primary w-100" data-bs-target="#add_notes" data-bs-toggle="modal">Add Notes</a>
							</div>
						</div>
					</div>
					<!-- /Contact Sidebar -->

					<!-- Contact Details -->
					<div class="col-xl-8">
						<div class="card mb-3">
							<div class="card-body pb-0 pt-2">
								<ul class="nav nav-tabs nav-bordered" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a href="#tab_1" data-bs-toggle="tab" class="nav-link active border-3" aria-controls="tab_1" aria-selected="true" role="tab">
                                            <span class="d-md-inline-block"><i class="ti ti-alarm-minus me-1"></i>Activities</span>
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tab_2" data-bs-toggle="tab" aria-controls="tab_2" class="nav-link border-3" aria-selected="false" role="tab" tabindex="-1">
                                            <span class="d-md-inline-block"><i class="ti ti-notes me-1"></i>Notes</span>
                                        </a>
                                    </li>
                                </ul>
							</div>
						</div>

						<!-- Tab Content -->
						<div class="tab-content pt-0">

							<!-- Activities -->
							<div class="tab-pane active show" id="tab_1" role="tabpanel" aria-labelledby="tab_1">
								<div class="card">									
									<div class="card-body">
                                        <div class="border-bottom mb-3 pb-3">
                                            <h5 class="fw-bold mb-0">Activities</h5>										
                                        </div>
										<div class="card border mb-3">
											<div class="card-body">
												<div class="d-flex flex-wrap row-gap-2">
													<span class="avatar avatar-lg bg-light p-0 flex-shrink-0 rounded-circle text-dark me-2"><i class="ti ti-refresh-dot fs-24"></i></span>
													<div>
														<div class="text-dark fw-medium mb-1 d-flex align-items-center">Updated Price : $10500 <i class="ti ti-arrow-right fs-16 mx-1"></i> $10200</div>
														<span>05 Jun 2026, 1:45 PM, By Admin</span>
													</div>
												</div>
											</div>
										</div>
                                        <div class="card border mb-3">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap row-gap-2">
                                                    <span class="avatar avatar-lg bg-light p-0 flex-shrink-0 rounded-circle text-dark me-2">
                                                        <i class="ti ti-refresh-dot fs-24"></i>
                                                    </span>
                                                    <div>
                                                        <div class="text-dark fw-medium mb-1 d-flex align-items-center">
                                                            Updated Price : $9400
                                                            <i class="ti ti-arrow-right fs-16 mx-1"></i>
                                                            $9200
                                                        </div>
                                                        <span class="text-muted">30 May 2026, 2:00 PM, By User</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card border mb-3">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap row-gap-2">
                                                    <span class="avatar avatar-lg bg-light p-0 flex-shrink-0 rounded-circle text-dark me-2">
                                                        <i class="ti ti-refresh-dot fs-24"></i>
                                                    </span>
                                                    <div>
                                                        <div class="text-dark fw-medium mb-1 d-flex align-items-center">
                                                            Updated Price : $11000
                                                            <i class="ti ti-arrow-right fs-16 mx-1"></i>
                                                            $10800
                                                        </div>
                                                        <span class="text-muted">25 Apr 2026, 10:15 AM, By Admin</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card border mb-3">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap row-gap-2">
                                                    <span class="avatar avatar-lg bg-light p-0 flex-shrink-0 rounded-circle text-dark me-2">
                                                        <i class="ti ti-refresh-dot fs-24"></i>
                                                    </span>
                                                    <div>
                                                        <div class="text-dark fw-medium mb-1 d-flex align-items-center">
                                                            Updated Price : $8700
                                                            <i class="ti ti-arrow-right fs-16 mx-1"></i>
                                                            $8500
                                                        </div>
                                                        <span class="text-muted">20 Mar 2026, 11:00 AM, By User</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card border mb-3">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap row-gap-2">
                                                    <span class="avatar avatar-lg bg-light p-0 flex-shrink-0 rounded-circle text-dark me-2">
                                                        <i class="ti ti-refresh-dot fs-24"></i>
                                                    </span>
                                                    <div>
                                                        <div class="text-dark fw-medium mb-1 d-flex align-items-center">
                                                            Updated Price : $10250
                                                            <i class="ti ti-arrow-right fs-16 mx-1"></i>
                                                            $9800
                                                        </div>
                                                        <span class="text-muted">15 Feb 2026, 3:30 PM, By Admin</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card border mb-3">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap row-gap-2">
                                                    <span class="avatar avatar-lg bg-light p-0 flex-shrink-0 rounded-circle text-dark me-2">
                                                        <i class="ti ti-refresh-dot fs-24"></i>
                                                    </span>
                                                    <div>
                                                        <div class="text-dark fw-medium mb-1 d-flex align-items-center">
                                                            Updated Price : $8956
                                                            <i class="ti ti-arrow-right fs-16 mx-1"></i>
                                                            $7500
                                                        </div>
                                                        <span class="text-muted">17 Jan 2026, 6:32 AM, By Admin</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card border mb-0">
                                            <div class="card-body">
                                                <div class="d-flex flex-wrap row-gap-2">
                                                    <span class="avatar avatar-lg bg-light p-0 flex-shrink-0 rounded-circle text-dark me-2">
                                                        <i class="ti ti-refresh-dot fs-24"></i>
                                                    </span>
                                                    <div>
                                                        <div class="text-dark fw-medium mb-1 d-flex align-items-center">
                                                            Updated Category : Server
                                                            <i class="ti ti-arrow-right fs-16 mx-1"></i>
                                                            Hardware
                                                        </div>
                                                        <span class="text-muted">12 Jan 2026, 4:45 PM, By Admin</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>                                        
									</div>
								</div>
							</div>
							<!-- /Activities -->

							<!-- Notes -->
							<div class="tab-pane fade" id="tab_2" role="tabpanel" aria-labelledby="tab_2">
								<div class="card">									
									<div class="card-body">
                                        <div class="border-bottom mb-3 pb-3">
                                            <h5 class="fw-bold mb-0">Notes</h5>										
                                        </div>
										<div class="card border mb-3">
											<div class="card-body d-flex align-items-center justify-content-between">
												<div class="d-flex flex-wrap row-gap-2">
													<span class="avatar avatar-lg bg-light p-0 flex-shrink-0 rounded-circle text-dark me-2"><i class="ti ti-file-settings fs-24"></i></span>
													<div>
														<div class="text-dark mb-1 d-flex align-items-center">Includes USB cable & 1-year warranty</div>
														<span>15 Feb 2026, 3:30 PM, By Admin</span>
													</div>
												</div>
                                                <div class="dropdown">
                                                    <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_notes"><i class="ti ti-edit me-1"></i>Edit</a>
                                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_note"><i class="ti ti-trash me-1"></i>Delete</a>
                                                    </div>
                                                </div>
											</div>
										</div>  
                                        <div class="card border mb-0">
											<div class="card-body d-flex align-items-center justify-content-between">
												<div class="d-flex flex-wrap row-gap-2">
													<span class="avatar avatar-lg bg-light p-0 flex-shrink-0 rounded-circle text-dark me-2"><i class="ti ti-file-settings fs-24"></i></span>
													<div>
														<div class="text-dark mb-1 d-flex align-items-center">Includes firewall & malware protection</div>
														<span>15 Feb 2026, 3:30 PM, By Admin</span>
													</div>
												</div>
                                                <div class="dropdown">
                                                    <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></a>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit_notes"><i class="ti ti-edit me-1"></i>Edit</a>
                                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete_note"><i class="ti ti-trash me-1"></i>Delete</a>
                                                    </div>
                                                </div>
											</div>
										</div>                                      
									</div>
								</div>
							</div>
							<!-- /Notes -->

						</div>
						<!-- /Tab Content -->

					</div>
					<!-- /Contact Details -->

				</div>
                <!-- Start Footer -->
            
            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/choices.js/public/assets/styles/choices.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/quill/quill.snow.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/intltelinput/css/intlTelInput.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/intltelinput/css/demo.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/choices.js/public/assets/scripts/choices.min.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/ResizeSensor.js') }}"></script>
<script src="{{ asset('assets/plugins/theia-sticky-sidebar/theia-sticky-sidebar.js') }}"></script>
<script src="{{ asset('assets/plugins/intltelinput/js/intlTelInput.js') }}"></script>
<script src="{{ asset('assets/plugins/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/plugins/flatpickr/flatpickr.min.js') }}"></script>
@endpush

