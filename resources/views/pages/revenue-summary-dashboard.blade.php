@extends('layouts.app')

@section('title', 'Revenue Summary Dashboard')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-0">Revenue Summary</h4>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
						<div class="dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown"><i class="ti ti-package-export me-2"></i>Export</a>
                            <div class="dropdown-menu  dropdown-menu-end">
                                <ul>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item"><i class="ti ti-file-type-pdf me-1"></i>Export as
                                            PDF</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item"><i class="ti ti-file-type-xls me-1"></i>Export as
                                            Excel </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh"><i class="ti ti-refresh"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-transition-top"></i></a>
                    </div>
                </div>
				<!-- End Page Header -->

				<!-- start row -->
				<div class="row">

					<div class="col-xxl-7 d-flex">

						<div class="row">
							<div class="col-md-12 d-flex">

								<div class="card flex-fill">
									<div class="card-body">
										<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
											<h5 class="mb-0 fs-18 fw-bold d-inline-flex items-center">Overview Statistics</h5>
											<a href="#" class="btn btn-sm btn-icon btn-outline-light"><i class="ti ti-arrow-right"></i></a>
										</div>
										<div class="border rounded">
											<div class="row g-4">

												<div class="col-md-4 d-flex pe-md-0">
													<div class="p-3 card-hover text-center mb-0 flex-fill border-end">
														<div class="avatar avatar-md bg-primary-gradient-100 fs-16 mb-2">
															<i class="ti ti-currency-dollar fs-22"></i>
														</div>
														<p class="mb-1">Total Revenue</p>
														<h5 class="mb-3">$2.45M</h5>
														<div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
															<span class="d-inline-flex align-items-center badge rounded-pill badge-soft-success border-0">+2.5%</span>
															<p class="text-dark mb-0">vs Last Period</p>
														</div>
													</div>
												</div> <!-- end col-->
												
												<div class="col-md-4 d-flex px-md-0">
													<div class="p-3 card-hover text-center mb-0 flex-fill border-end">
														<div class="avatar avatar-md bg-secondary fs-16 mb-2">
															<i class="ti ti-antenna-bars-5 fs-22"></i>
														</div>
														<p class="mb-1">Revenue Growth</p>
														<h5 class="mb-3">18.2%</h5>
														<div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
															<span class="d-inline-flex align-items-center badge rounded-pill badge-soft-success border-0">+3.4%</span>
															<p class="text-dark mb-0">QoQ Improved</p>
														</div>
													</div>
												</div> <!-- end col-->
												
												<div class="col-md-4 d-flex ps-md-0">
													<div class="p-3 card-hover text-center mb-0 flex-fill">
														<div class="avatar avatar-md bg-info fs-16 mb-2">
															<i class="ti ti-box fs-22"></i>
														</div>
														<p class="mb-1">Annual Recurring</p>
														<h5 class="mb-3">$28.4M</h5>
														<div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
															<span class="d-inline-flex align-items-center badge rounded-pill badge-soft-success border-0">+2.5%</span>
															<p class="text-dark mb-0">ARR Growth</p>
														</div>
													</div>
												</div> <!-- end col-->

											</div>

										</div>
									</div>
								</div> <!-- end card -->

							</div> <!-- end col -->

							

							<div class="col-md-12">

								<div class="row">
									<div class="col-md-5 d-flex">
										<div class="card flex-fill">
											<div class="card-body">
												<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
													<h5 class="mb-0">Deal Value</h5>
													<a href="#" class="btn btn-sm btn-icon btn-outline-light"><i class="ti ti-arrow-right"></i></a>
												</div>
												<div class="d-flex align-items-center justify-content-between mb-3">
													<div>
														<div class="mb-2">
															<p class="d-flex align-items-center mb-1"><i class="ti ti-square-filled fs-8 text-purple-gradient me-1"></i>Avg Deal Value</p>
															<h5 class="main-title mb-0">$43.2K</h5>
														</div>
														<div class="mb-0">
															<p class="d-flex align-items-center mb-1"><i class="ti ti-square-filled fs-8 text-danger-gradient me-1"></i>Previous</p>
															<h5 class="main-title mb-0">$39.8K</h5>
														</div>
													</div>
													<div id="deal-value-chart"></div>
												</div>
												<div class="d-flex align-items-center gap-2 flex-wrap">
													<span class="d-inline-flex align-items-center badge rounded-pill badge-soft-success border-0">+2.5%</span>
													<p class="text-dark mb-0">From Last Week</p>
												</div>
											</div>
										</div>
									</div> <!-- end col -->

									<div class="col-md-7 d-flex">
										<div class="card flex-fill">
											<div class="card-body">
												<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
													<div class="d-flex align-items-center">
														<div class="avatar avatar-lg bg-cyan me-2">
															<i class="ti ti-file-text fs-24"></i>
														</div>
														<div>
															<p class="mb-1">Forecasted Revenue</p>
															<h4 class="mb-0">$8.45M</h4>
														</div>
													</div>
													<span class="d-inline-flex align-items-center badge badge-soft-success border border-success">+5.1% Growth</span>
												</div>
												<div id="forecasted-revenue"></div>
												<div class="d-flex align-items-center justify-content-between border p-2 rounded mt-3">
													<p class="fs-13 fw-medium text-success d-inline-flex align-items-center mb-0"><i class="ti ti-trending-up me-1"></i>+15.2%</p>
													<p class="d-inline-flex align-items-center mb-0">Forecast Increase<i class="ti ti-info-circle ms-2"></i></p>
												</div>
											</div>
										</div>
									</div> <!-- end col -->

								</div>
							</div> <!-- end col -->

						</div>
					</div> <!-- end col -->

					<div class="col-xxl-5 d-flex flex-column">

						<div class="row flex-fill">
							<div class="col-md-12 d-flex">

								<div class="card flex-fill">
									<div class="card-body">
										<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
											<h5 class="mb-0">Revenue Breakdown</h5>
											<a href="#" class="btn btn-sm btn-icon btn-outline-light"><i class="ti ti-refresh"></i></a>
										</div>
										<div id="revenue-breakdown-chart"></div>
										<div class="border rounded">
											<div class="row">
												<div class="col-sm-6 pe-sm-0">
													<div class="p-3 border-end border-bottom bg-light">
														<p class="d-flex align-items-center mb-1"><i class="ti ti-circle-filled fs-8 text-purple-gradient me-1"></i>Enterprise Suite</p>
														<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
															<h5 class="main-title mb-0">40.9%</h5>
															<p class="fs-13 fw-medium text-success d-inline-flex align-items-center mb-0"><i class="ti ti-trending-up me-1"></i>+18.4%</p>
														</div>
													</div>
												</div> <!-- end col -->

												
												<div class="col-sm-6 ps-sm-0">
													<div class="p-3 border-bottom bg-light">
														<p class="d-flex align-items-center mb-1"><i class="ti ti-circle-filled fs-8 text-danger-gradient me-1"></i>Professional Plan</p>
														<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
															<h5 class="main-title mb-0">30.4%</h5>
															<p class="fs-13 fw-medium text-success d-inline-flex align-items-center mb-0"><i class="ti ti-trending-up me-1"></i>+12.7%</p>
														</div>
													</div>
												</div> <!-- end col -->

												
												<div class="col-sm-6 pe-sm-0">
													<div class="p-3 border-end bg-light">
														<p class="d-flex align-items-center mb-1"><i class="ti ti-circle-filled fs-8 text-warning-gradient me-1"></i>Starter Package</p>
														<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
															<h5 class="main-title mb-0">16.4%</h5>
															<p class="fs-13 fw-medium text-success d-inline-flex align-items-center mb-0"><i class="ti ti-trending-up me-1"></i>+8.9%</p>
														</div>
													</div>
												</div> <!-- end col -->

												
												<div class="col-sm-6 ps-sm-0">
													<div class="p-3 bg-light">
														<p class="d-flex align-items-center mb-1"><i class="ti ti-circle-filled fs-8 text-info-gradient me-1"></i>Add-ons & Services</p>
														<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
															<h5 class="main-title mb-0">12.2%</h5>
															<p class="fs-13 fw-medium text-success d-inline-flex align-items-center mb-0"><i class="ti ti-trending-up me-1"></i>+22.1%</p>
														</div>
													</div>
												</div> <!-- end col -->
											</div>
										</div>
									</div>
								</div> <!-- end card -->

							</div>
						</div>
					</div> <!-- end col -->

				</div>
                <!-- end row -->

				<!-- start row -->
				<div class="row">
                    <div class="col-12">		
						<div class="card">
							<div class="card-header border-0 d-flex align-items-center justify-content-between">
								<div class="mb-0 fs-18 fw-bold text-dark d-flex align-items-center gap-2">Revenue Performance Trend <a href="#" class="btn btn-sm btn-icon btn-outline-light"><i class="ti ti-refresh"></i></a></div>
								<div class="dropdown">
									<a class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown" href="javascript:void(0);">
										2026
									</a>
									<div class="dropdown-menu dropdown-menu-end">
										<a href="javascript:void(0);" class="dropdown-item">
											2025
										</a>
										<a href="javascript:void(0);" class="dropdown-item">
											2024
										</a>
										<a href="javascript:void(0);" class="dropdown-item">
											2023
										</a>
									</div>
								</div>
							</div>
							<div class="card-body pt-0">
								<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
									<span>Comparing actual revenue vs. forecast and prior year</span>
									<div class="d-flex align-items-center gap-2 flex-wrap">
										<span class="fw-medium border rounded text-gray-5 d-flex align-items-center px-2 gap-1"><i class="ti ti-circle-filled fs-8 text-info"></i> Actual Revenue</span>
										<span class="fw-medium border rounded text-gray-5 d-flex align-items-center px-2 gap-1"><i class="ti ti-circle-filled fs-8 text-success"></i> Forecasted</span>
										<span class="fw-medium border rounded text-gray-5 d-flex align-items-center px-2 gap-1"><i class="ti ti-circle-filled fs-8 text-danger"></i> Prior Year</span>
									</div>
								</div>
								<div id="revenue-performance-chart"></div>
								<div class="d-flex align-items-center justify-content-center gap-3 flex-wrap mt-2">
									<p class="mb-0 d-flex"><span class="me-2 rounded-3 bg-danger p-1 pb-0 pe-0"></span>Avg. Monthly Revenue<span class="fs-16 fw-semibold text-dark ms-2">$608K</span></p>
									<p class="mb-0 d-flex"><span class="me-2 rounded-3 bg-info p-1 pb-0 pe-0"></span>Forecast Accuracy<span class="fs-16 fw-semibold text-dark ms-2">96.3%</span></p>
                                    <p class="mb-0 d-flex"><span class="me-2 rounded-3 bg-success p-1 pb-0 pe-0"></span>YoY Growth<span class="fs-16 fw-semibold text-dark ms-2">+24.8%</span></p>
								</div>
							</div>
                        </div>
					</div>
                </div>
				<!-- end row -->

				<div class="row">
                    <div class="col-xl-6 d-flex">		
						<div class="card flex-fill">
							<div class="card-header border-0 d-flex align-items-center justify-content-between">
								<div class="mb-0 fs-18 fw-bold text-dark">Revenue VS Expense</div>
								<div class="dropdown">
									<a class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown" href="javascript:void(0);">
										2026
									</a>
									<div class="dropdown-menu dropdown-menu-end">
										<a href="javascript:void(0);" class="dropdown-item">
											2025
										</a>
										<a href="javascript:void(0);" class="dropdown-item">
											2024
										</a>
										<a href="javascript:void(0);" class="dropdown-item">
											2023
										</a>
									</div>
								</div>
							</div>
							<div class="card-body pt-0">
								<div id="revenue_expense"></div>
								<span>Detailed revenue analysis by product segment</span>
							</div>
                        </div>
					</div>
					<div class="col-xl-6 d-flex">		
						<div class="card flex-fill">
							<div class="card-header border-0 d-flex align-items-center justify-content-between">
								<div class="mb-0 fs-18 fw-bold text-dark">Comparison</div>
								<div class="dropdown">
									<a class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown" href="javascript:void(0);">
										2026
									</a>
									<div class="dropdown-menu dropdown-menu-end">
										<a href="javascript:void(0);" class="dropdown-item">
											2025
										</a>
										<a href="javascript:void(0);" class="dropdown-item">
											2024
										</a>
										<a href="javascript:void(0);" class="dropdown-item">
											2023
										</a>
									</div>
								</div>
							</div>
							<div class="card-body pt-0">

								<!-- Row 1 -->
								<div class="p-4 d-flex justify-content-between align-items-center deals-closed bg-purple-subtle">
									
									<div class="row w-100 g-1">
										<div class="col-6">
											<p class="mb-1 fs-14 text-dark">Deals Closed</p>
											<h2 class="fw-bold mb-0 fs-20">156</h2>
										</div>

										<div class="col-4">
											<p class="mb-1 fs-14 text-dark">Previous</p>
											<div class="fw-bold mb-0 fs-20 text-dark">152</div>
										</div>

										<div class="col-2 d-flex align-items-center justify-content-md-end mt-3 mt-md-0">
											<span class="badge rounded-pill bg-white text-dark py-2 fs-12">
												+3.3%
											</span>
										</div>
									</div>
								</div>

								<!-- Row 2 -->
								<div class="p-4 d-flex justify-content-between align-items-center win-rate bg-info-subtle">
									
									<div class="row w-100 g-1">
										<div class="col-6">
											<p class="mb-1 fs-14 text-dark">Win Rate</p>
											<h2 class="fw-bold mb-0 fs-20">28.4%</h2>
										</div>

										<div class="col-4">
											<p class="mb-1 fs-14 text-dark">Previous</p>
											<div class="fw-bold mb-0 fs-20 text-dark">26.9%</div>
										</div>

										<div class="col-2 d-flex align-items-center justify-content-md-end mt-3 mt-md-0">
											<span class="badge rounded-pill bg-white text-dark py-2 fs-12">
												+5.6%
											</span>
										</div>
									</div>
								</div>

								<!-- Row 3 -->
								<div class="p-4 d-flex justify-content-between align-items-center sales-cycle bg-warning-subtle">
									
									<div class="row w-100 g-1">
										<div class="col-6">
											<p class="mb-1 fs-14 text-dark">Sales Cycle (days)</p>
											<h2 class="fw-bold mb-0 fs-20">47</h2>
										</div>

										<div class="col-4">
											<p class="mb-1 fs-14 text-dark">Previous</p>
											<div class="fw-bold mb-0 fs-20 text-dark">42</div>
										</div>

										<div class="col-2 d-flex align-items-center justify-content-md-end mt-3 mt-md-0">
											<span class="badge rounded-pill bg-white text-dark py-2 fs-12">
												+10.6%
											</span>
										</div>
									</div>
								</div>

									
							</div>
                        </div>
					</div>
                </div>

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
<script src="{{ asset('assets/json/dashboard.js') }}"></script>
@endpush

