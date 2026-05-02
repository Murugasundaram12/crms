@extends('layouts.app')

@section('title', 'Executive Dashboard')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-0">Executive Dashboard</h4>
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

					<div class="col-xxl-8 d-flex">
						<div class="card flex-fill">
							<div class="card-body">
								<div class="row g-4">
									<div class="col-md-6 d-flex">
										<div class="card mb-0 flex-fill">
											<div class="card-body">
												<div class="d-flex align-items-center gap-2 mb-3">
													<div class="avatar bg-success-subtle text-success border border-success fs-24">
														<i class="ti ti-trending-up-3"></i>
													</div>
													<p class="mb-0 fs-13 fw-medium text-dark">Sales Revenue</p>
												</div>
												<div class="border rounded p-3 d-flex align-items-sm-center gap-2 justify-content-between flex-sm-row flex-column">
													<div>
														<h2 class="mb-2 text-success">$400k</h2>
														<p class="fs-13 fw-medium mb-0"><span class="text-success">+12%</span> vs Last Year</p>
													</div>
													<i class="ti ti-arrow-big-up-filled text-success"></i>
													<div class="border-bottom px-1 pb-2 border-bottom-dashed border-top-0 border-start-0 border-end-0">
														<div id="sales-revenue"></div>
													</div>
												</div>
											</div>
										</div>
									</div> <!-- end col-->

									<div class="col-md-6 d-flex">
										<div class="card mb-0 flex-fill">
											<div class="card-body">
												<div class="d-flex align-items-center gap-2 mb-3">
													<div class="avatar bg-purple-subtle text-purple border border-purple fs-24">
														<i class="ti ti-user-dollar"></i>
													</div>
													<p class="mb-0 fs-13 fw-medium text-dark">New Customers</p>
												</div>
												<div class="border rounded p-3 d-flex align-items-sm-center gap-2 justify-content-between flex-sm-row flex-column">
													<div>
														<h2 class="mb-2 text-purple">450</h2>
														<p class="fs-13 fw-medium mb-0"><span class="text-purple">+8.2%</span> vs Last Year</p>
													</div>
													<i class="ti ti-arrow-big-up-filled text-purple"></i>
													<div class="border-bottom px-1 pb-2 border-bottom-dashed border-top-0 border-start-0 border-end-0">
														<div id="customer-revenue"></div>
													</div>
												</div>
											</div>
										</div>
									</div> <!-- end col-->

									<div class="col-md-6 d-flex">
										<div class="card mb-0 flex-fill">
											<div class="card-body">
												<div class="d-flex align-items-center gap-2 mb-3">
													<div class="avatar bg-secondary-subtle text-secondary border border-secondary fs-24">
														<i class="ti ti-target-arrow"></i>
													</div>
													<p class="mb-0 fs-13 fw-medium text-dark">Target Achievement</p>
												</div>
												<div class="border rounded p-3 d-flex align-items-sm-center gap-2 justify-content-between flex-sm-row flex-column">
													<div>
														<h2 class="mb-2 text-secondary">68%</h2>
														<p class="fs-13 fw-medium mb-0"><span class="text-secondary">-1.2%</span> vs Last Year</p>
													</div>
													<i class="ti ti-arrow-big-down-filled text-secondary"></i>
													<div class="border-bottom px-1 pb-2 border-bottom-dashed border-top-0 border-start-0 border-end-0">
														<div id="target-revenue"></div>
													</div>
												</div>
											</div>
										</div>
									</div> <!-- end col-->

									<div class="col-md-6 d-flex">
										<div class="card mb-0 flex-fill">
											<div class="card-body">
												<div class="d-flex align-items-center gap-2 mb-3">
													<div class="avatar bg-info-subtle text-info border border-info fs-18">
														<img src="{{ asset('assets/img/icons/profit.svg') }}" alt="icon" class="img-fluid p-2">
													</div>
													<p class="mb-0 fs-13 fw-medium text-dark">Profit</p>
												</div>
												<div class="border rounded p-3 d-flex align-items-sm-center gap-2 justify-content-between flex-sm-row flex-column">
													<div>
														<h2 class="mb-2 text-info">40%</h2>
														<p class="fs-13 fw-medium mb-0"><span class="text-info">+1.2%</span> vs Last Year</p>
													</div>
													<i class="ti ti-arrow-big-up-filled text-info"></i>
													<div class="border-bottom px-1 pb-2 border-bottom-dashed border-top-0 border-start-0 border-end-0">
														<div id="profit-revenue"></div>
													</div>
												</div>
											</div>
										</div>
									</div> <!-- end col-->

								</div>
							</div>
						</div> <!-- end card -->
					</div> <!-- end col -->

					<div class="col-xxl-4 d-flex flex-column">

						<div class="row flex-fill">
							<div class="col-md-12 d-flex">

								<div class="card flex-fill">
									<div class="card-body">
										<h2 class="card-subtitle mb-3">Activity Count</h2>
										<div class="d-flex align-items-center justify-content-between gap-3 border-bottom pb-2">
											<div class="d-flex align-items-center gap-2">
												<div class="circular-progress" data-progress="70" data-color="#27AE60">
													<span class="avatar avatar-rounded avatar-xss text-success"><i class="ti ti-phone-call fs-18"></i></span>
												</div>
												<p class="fs-13 fw-medium text-dark mb-0">Calls</p>
											</div>
											<div class="text-end">
												<p class="fs-16 fw-semibold text-dark mb-1">342</p>
												<p class="fs-12 text-success mb-0">+12%</p>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between gap-3 border-bottom py-2">
											<div class="d-flex align-items-center gap-2">
												<div class="circular-progress" data-progress="85" data-color="#800080">
													<span class="avatar avatar-rounded avatar-xss text-purple"><i class="ti ti-mail fs-18"></i></span>
												</div>
												<p class="fs-13 fw-medium text-dark mb-0">Emails</p>
											</div>
											<div class="text-end">
												<p class="fs-16 fw-semibold text-dark mb-1">567</p>
												<p class="fs-12 text-purple mb-0">+22%</p>
											</div>
										</div>
										<div class="d-flex align-items-center justify-content-between gap-3 py-2 pb-0">
											<div class="d-flex align-items-center gap-2">
												<div class="circular-progress" data-progress="50" data-color="#2F80ED">
													<span class="avatar avatar-rounded avatar-xss text-info"><i class="ti ti-users fs-18"></i></span>
												</div>
												<p class="fs-13 fw-medium text-dark mb-0">Meetings</p>
											</div>
											<div class="text-end">
												<p class="fs-16 fw-semibold text-dark mb-1">42</p>
												<p class="fs-12 text-info mb-0">+15%</p>
											</div>
										</div>
									</div>
								</div> <!-- end card -->

							</div>
							<div class="col-md-12 d-flex">

								<div class="card flex-fill">
									<div class="card-body">
										<h2 class="card-subtitle mb-3">Conversion Split</h2>
										<div class="row align-items-center justify-content-between g-4">
											<div class="col-sm-6">
												<div id="conversion-chart"></div>
											</div>
											<div class="col-sm-6">
												<div class="bg-light p-2 rounded mb-2">
													<p class="mb-0 text-dark fs-12 fw-medium"><i class="ti ti-circle-filled text-success-gradient me-2"></i>Converted</p>
												</div>
												<div class="bg-light p-2 rounded">
													<p class="mb-0 text-dark fs-12 fw-medium"><i class="ti ti-circle-filled text-purple-gradient me-2"></i>On Progress</p>
												</div>
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

					<div class="col-xl-6 d-flex ">
						<div class="card flex-fill">							
							<div class="card-body pb-0">
								<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
									<h2 class="card-subtitle mb-0">Top Revenue per Salesperson</h2>
									<div class="dropdown">
										<a class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown" href="javascript:void(0);">
											Last 6 Months
										</a>
										<div class="dropdown-menu dropdown-menu-end">
											<a href="javascript:void(0);" class="dropdown-item">
												Last Month
											</a>
											<a href="javascript:void(0);" class="dropdown-item">
												Last 6 Months
											</a>
											<a href="javascript:void(0);" class="dropdown-item">
												Last 3 Months
											</a>
										</div>
									</div>
								</div>
								<div id="salesperson-chart"></div>
							</div>
						</div> <!-- end card -->
					</div> <!-- end col -->
					
					<div class="col-xl-6 d-flex ">
						<div class="card flex-fill">							
							<div class="card-body pb-0">
								<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
									<h2 class="card-subtitle mb-0">Top Deals Closed per User</h2>
									<div class="dropdown">
										<a class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown" href="javascript:void(0);">
											2025
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
								<div id="top-deals"></div>
							</div>
						</div> <!-- end card -->
					</div> <!-- end col -->

				</div>
                <!-- end row -->

				<!-- start row -->
				<div class="row">

					<div class="col-xl-4 d-flex ">
						<div class="card flex-fill">
							<div class="card-body">
								<div class="mb-3">
									<h2 class="card-subtitle mb-4">Pipeline</h2>
								</div>
								<div class="d-flex align-items-center gap-2 mb-4">
									<p class="fs-12 mb-0 mw-74">Prospecting</p>
									<div class="d-flex align-items-center w-100 gap-2">
										<div class="progress w-100 progress-animate bg-white progress-xxl" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
											<div class="progress-bar bg-purple-gradient-100 rounded-pill" style="width: 100%"></div>
										</div>
										<p class="fs-12 fw-medium text-dark mb-0 flex-shrink-0">15 Deals</p>
									</div>
								</div>
								<div class="d-flex align-items-center gap-2 mb-4">
									<p class="fs-12 mb-0 mw-74">Qualification</p>
									<div class="d-flex align-items-center w-100 gap-2">
										<div class="progress w-100 progress-animate bg-white progress-xxl" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
											<div class="progress-bar bg-purple-gradient-100 rounded-pill" style="width: 80%"></div>
										</div>
										<p class="fs-12 fw-medium text-dark mb-0 flex-shrink-0">10 Deals</p>
									</div>
								</div>
								<div class="d-flex align-items-center gap-2 mb-4">
									<p class="fs-12 mb-0 mw-74">Proporsal</p>
									<div class="d-flex align-items-center w-100 gap-2">
										<div class="progress w-100 progress-animate bg-white progress-xxl" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
											<div class="progress-bar bg-purple-gradient-100 rounded-pill" style="width: 60%"></div>
										</div>
										<p class="fs-12 fw-medium text-dark mb-0 flex-shrink-0">8 Deals</p>
									</div>
								</div>
								<div class="d-flex align-items-center gap-2 mb-4">
									<p class="fs-12 mb-0 mw-74">Negotiation</p>
									<div class="d-flex align-items-center w-100 gap-2">
										<div class="progress w-100 progress-animate bg-white progress-xxl" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
											<div class="progress-bar bg-purple-gradient-100 rounded-pill" style="width: 40%"></div>
										</div>
										<p class="fs-12 fw-medium text-dark mb-0 flex-shrink-0">5 Deals</p>
									</div>
								</div>
								<div class="d-flex align-items-center gap-2 mb-4">
									<p class="fs-12 mb-0 mw-74">Closing</p>
									<div class="d-flex align-items-center w-100 gap-2">
										<div class="progress w-100 progress-animate bg-white progress-xxl" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
											<div class="progress-bar bg-purple-gradient-100 rounded-pill" style="width: 30%"></div>
										</div>
										<p class="fs-12 fw-medium text-dark mb-0 flex-shrink-0">2 Deals</p>
									</div>
								</div>
								<p class="fs-12 d-flex align-items-center gap-2 mb-0"><span class="fs-12 fw-semibold text-purple">30%</span>The performance is 30% better compare to last week</p>
							</div>
						</div> <!-- end card -->
					</div> <!-- end col -->
					
					<div class="col-xl-8 d-flex ">
						<div class="card flex-fill">							
							<div class="card-body pb-0">
								<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
									<h2 class="card-subtitle mb-0">Forecast Overview</h2>
									<div class="dropdown">
										<a class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown" href="javascript:void(0);">
											2025
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
								<div id="forecast-chart"></div>
							</div>
						</div> <!-- end card -->
					</div> <!-- end col -->

				</div>
                <!-- end row -->

                <!-- start row -->
                <div class="row">

					<div class="col-md-12 d-flex">		
						<div class="card flex-fill">
							<div class="card-body">
								<div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 mb-3">
									<h2 class="card-subtitle mb-0">Executive Performance Overview</h2>
									<div class="dropdown">
										<a class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown" href="javascript:void(0);">
											Weekly
										</a>
										<div class="dropdown-menu dropdown-menu-end">
											<a href="javascript:void(0);" class="dropdown-item">
												Yearly
											</a>
											<a href="javascript:void(0);" class="dropdown-item">
												Weekly
											</a>
											<a href="javascript:void(0);" class="dropdown-item">
												Monthly
											</a>
										</div>
									</div>
								</div>
								<div class="table-responsive custom-table">
									<table class="table dataTable table-nowrap" id="executive-project"> 
										<thead class="table-light">
											<tr>
												<th>Executive Name</th>
												<th>Deal Closed</th>
												<th>Revenue Generated</th>
												<th>Conversion %</th>
												<th>Status</th>
											</tr>
										</thead>
										<tbody>
										</tbody>
									</table>
								</div>
							</div> <!-- end card body -->
                        </div> <!-- end card -->
					</div> <!-- end col --> 

				</div>
                <!-- end row -->

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

