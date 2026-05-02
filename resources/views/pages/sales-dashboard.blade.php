@extends('layouts.app')

@section('title', 'Sales Dashboard')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-0">Sales Dashboard</h4>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
						<div class="daterangepick form-control w-auto d-flex align-items-center">
							<i class="ti ti-calendar text-dark me-2"></i>
							<span class="reportrange-picker-field text-dark">23 May 2025 - 30 May 2025</span>
						</div>	
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh"><i class="ti ti-refresh"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-transition-top"></i></a>
                    </div>
                </div>
				<!-- End Page Header -->

				<!-- start row -->
                <div class="row">

					<div class="col-xxl-8 col-xl-12 d-flex">		
						<div class="card flex-fill">
							<div class="card-body">
								<div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 mb-3">
									<div>
										<h5 class="sub-title mb-1">Total Revenue</h5>
										<p class="mb-0">26 Jan 2026 - 26 Jan 2027</p>
									</div>
									<div class="avatar-group avatar-group-sm">
										<a href="#" class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
											<img class="w-auto h-auto img-fluid" src="{{ asset('assets/img/company/company-09.svg') }}" alt="img">
										</a>
										<a href="#" class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
											<img class="w-auto h-auto img-fluid" src="{{ asset('assets/img/company/company-10.svg') }}" alt="img">
										</a>
										<a href="#" class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
											<img class="w-auto h-auto img-fluid" src="{{ asset('assets/img/company/company-01.svg') }}" alt="img">
										</a>
										<a href="#" class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
											<img class="w-auto h-auto img-fluid" src="{{ asset('assets/img/company/company-02.svg') }}" alt="img">
										</a>
										<a href="#" class="avatar avatar-rounded border bg-white p-1 d-inline-flex align-items-center justify-content-center">
											<img class="w-auto h-auto img-fluid" src="{{ asset('assets/img/company/company-11.svg') }}" alt="img">
										</a>
									</div>
								</div>
								<ul class="nav nav-tabs nav-border nav-solid-primary gap-2 justify-content-end mb-4" role="tablist">
                                    <li class="nav-item"><a class="nav-link active" href="#weekly" data-bs-toggle="tab">Weekly</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#monthly" data-bs-toggle="tab">Monthly</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#yearly" data-bs-toggle="tab">Yearly</a></li>
                                </ul>
								<div class="row g-4">
									<div class="col-md-6">
										<div class="bg-secondary rounded-4 rounded-end-5 d-flex">
											<div class="ps-3 d-flex align-items-center justify-content-center position-relative pe-2 z-1">
												<p class="fs-16 fw-medium text-white mb-0 z-2">MTD</p>
												<span class="arrow-icon d-block position-absolute"></span>
											</div>
											<div class="bg-light rounded-4 w-100 p-3">
												<p class="text-dark mb-2">Total MTD Revenue</p>
												<h3 class="mb-4">$18,50,800.00</h3>
												<div class="d-flex align-items-center justify-content-between gap-1 flex-wrap">
													<div class="d-flex align-items-center gap-1 flex-wrap">
														<span class="badge badge-pill rounded-pill border badge-soft-success border-0">+2.5%</span>
														<p class="mb-0">Month Till Date</p>
													</div>
													<div id="mtd-revenue"></div>
												</div>
											</div>
										</div>
									</div>
									<div class="col-md-6">
										<div class="bg-danger rounded-4 rounded-end-5 d-flex">
											<div class="ps-3 d-flex align-items-center justify-content-center position-relative pe-2 z-1">
												<p class="fs-16 fw-medium text-white mb-0">YTD</p>
												<span class="arrow-icon arrow-primary d-block position-absolute"></span>
											</div>
											<div class="bg-light rounded-4 w-100 p-3">
												<p class="text-dark mb-2">Total YTD Revenue</p>
												<h3 class="mb-4">$85,25,800.00</h3>
												<div class="d-flex align-items-center justify-content-between gap-1 flex-wrap">
													<div class="d-flex align-items-center gap-1 flex-wrap">
														<span class="badge badge-pill rounded-pill border badge-soft-danger border-0">-5.0%</span>
														<p class="mb-0">Year Till Date</p>
													</div>
													<div id="ytd-revenue"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div> <!-- end card body -->
                        </div> <!-- end card -->
					</div> <!-- end col --> 

					<div class="col-xxl-4 col-xl-12 col-md-12 d-flex">		
						<div class="card flex-fill">
							<div class="card-body">
								<div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 mb-3">
									<div>
										<h3 class="sub-title mb-1">Conversion Rate</h3>
										<p class="mb-0">26 Jan 2026 - 26 Jan 2027</p>
									</div>
								</div>
								<div>
								<canvas id="storage-request" class="mx-auto w-full"></canvas>

								</div>
								<div class="d-flex align-items-center gap-1 flex-wrap">
									<h3 class="sub-title mb-0">55.6%</h3>
									<span class="badge badge-pill rounded-pill border badge-soft-success border-0">+2.5%</span>
									<p class="mb-0">Last Week</p>
								</div>
							</div> <!-- end card body -->
                        </div> <!-- end card -->
					</div> <!-- end col --> 

				</div>
                <!-- end row -->

                <!-- start row -->
                <div class="row">

					<div class="col-xl-6 col-md-12 d-flex">		
						<div class="card flex-fill">
							<div class="card-body">
								<div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 mb-3">
									<div>
										<h2 class="sub-title mb-1">Deals Won Vs Lost</h2>
										<p class="mb-0">+15% vs last month</p>
									</div>
									<a href="#" class="btn btn-sm btn-icon btn-outline-light"><i class="ti ti-refresh"></i></a>
								</div>
								<div class="d-flex alig-items-center flex-wrap flex-xl-nowrap flex-xl-row gap-2">
									<div class="w-100">
										<div class="border rounded p-3 d-flex align-items-center mb-3">
											<div class="avatar avatar-lg bg-secondary-subtle border border-secondary text-dark rounded me-3 flex-shrink-0">
												<i class="ti ti-tag fs-20"></i>
											</div>
											<div>
												<p class="text-dark fw-medium mb-1">Deals Won</p>
												<div class="d-flex align-items-center gap-1 flex-wrap">
													<h3 class="custom-title mb-0 me-1">68</h3>
													<p class="fs-12 mb-0"><span class="text-success">+2.5%</span> Last Week</p>
												</div>
											</div>
										</div>
										<div class="border rounded p-3 d-flex align-items-center">
											<div class="avatar avatar-lg bg-primary-subtle border border-primary text-dark rounded me-3 flex-shrink-0">
												<i class="ti ti-tag-off fs-20"></i>
											</div>
											<div>
												<p class="text-dark fw-medium mb-1">Deals Lost</p>
												<div class="d-flex align-items-center gap-1 flex-wrap">
													<h3 class="custom-title text-danger mb-0 me-1">16</h3>
													<p class="fs-12 mb-0"><span class="text-danger">-5.8%</span> Last Week</p>
												</div>
											</div>
										</div>
									</div>
									<div>
										<div id="deals-won"></div>
									</div>
								</div>
							</div> <!-- end card body -->
                        </div> <!-- end card -->
					</div> <!-- end col --> 

					<div class="col-xl-6 col-md-12 d-flex">		
						<div class="card flex-fill">
							<div class="card-body">
								<div class="mb-3">
									<h2 class="sub-title mb-0">Sales Pipeline Overview</h2>
								</div>
								<div class="d-flex align-items-center gap-1 flex-wrap mb-3">
									<h3 class="custom-title mb-0">$2,56,054.50</h3>
									<p class="fs-12 mb-0"><span class="text-success">+2.5%</span> Last Week</p>
								</div>
								<div class="progress progress-bg progress-2xl mb-2" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-purple-subtle text-dark fw-medium text-start ps-4" style="width: 60%;">Probability - $50,000</div>
                                </div>
								<div class="progress progress-bg progress-2xl mb-2" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-success-subtle text-dark fw-medium text-start ps-4" style="width: 75%;">Proposal Sent - $56,054</div>
                                </div>
								<div class="progress progress-bg progress-2xl mb-2" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-warning-subtle text-dark fw-medium text-start ps-4" style="width: 40%;">Opportunity - $1,00,000</div>
                                </div>
								<div class="progress progress-bg progress-2xl mb-0" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-danger-subtle text-dark fw-medium text-start ps-4" style="width: 60%;">Total Deals - $1,00,000</div>
                                </div>
							</div> <!-- end card body -->
                        </div> <!-- end card -->
					</div> <!-- end col --> 

				</div>
                <!-- end row -->

                <!-- start row -->
                <div class="row">

					<div class="col-xl-6 col-md-12 d-flex">		
						<div class="card flex-fill">
							<div class="card-body pb-0">
								<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
									<h2 class="sub-title mb-0">Recently Created Deals</h2>
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
							</div>
							<div class="table-responsive custom-table">
								<table class="table border-start-0 dataTable table-nowrap" id="recent-deals"> 
									<thead class="table-light">
										<tr>
											<th>Deals</th>
											<th>Value</th>
											<th>Status</th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div> <!-- end card body -->
                        </div> <!-- end card -->
					</div> <!-- end col --> 

					<div class="col-xl-6 col-md-12 d-flex">		
						<div class="card flex-fill">
							<div class="card-body pb-0">
								<div class="mb-3">
									<h2 class="sub-title mb-0">Avg Deal Size</h2>
								</div>
								<div class="d-flex align-items-center gap-1 flex-wrap mb-3">
									<h3 class="custom-title mb-0">$1,56,054.50</h3>
									<p class="fs-12 mb-0"><span class="text-success">+2.5%</span> Last Week</p>
								</div>
								<div id="deal-size"></div>
							</div> <!-- end card body -->
                        </div> <!-- end card -->
					</div> <!-- end col --> 

				</div>
                <!-- end row -->

				<!-- start row -->
                <div class="row">

					<div class="col-md-12 d-flex">		
						<div class="card flex-fill">
							<div class="card-body pb-0">
								<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
									<h2 class="sub-title mb-0">Sales Growth</h2>
									<div class="dropdown">
										<a class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown" href="javascript:void(0);">
											Last Year
										</a>
										<div class="dropdown-menu dropdown-menu-end">
											<a href="javascript:void(0);" class="dropdown-item">
												Last 30 Days
											</a>
											<a href="javascript:void(0);" class="dropdown-item">
												Last 6 months
											</a>
											<a href="javascript:void(0);" class="dropdown-item">
												Last Year
											</a>
										</div>
									</div>
								</div>
								<div id="deal-chart"></div>
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
<script src="{{ asset('assets/plugins/chartjs/chart.min.js') }}"></script>
<script src="{{ asset('assets/plugins/chartjs/chart-data.js') }}"></script>
<script src="{{ asset('assets/json/dashboard.js') }}"></script>
@endpush

