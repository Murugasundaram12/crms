@extends('layouts.app')

@section('title', 'Growth Dashboard')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Growth Dashboard</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Growth Dashboard</li>
                            </ol>
                        </nav>
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
				<div class="row flex-fill">

					<div class="col-xl-9 d-flex">
                       <div class="row g-3 flex-fill">
                          <div class="col-lg-3 col-md-6 d-flex">
                             <div class="card growth-card flex-fill bg-soft-success border-0 shadow-none">
                                   <div class="card-header text-center border-0 py-3">
                                      <div class="avatar rounded avatar-md bg-success">
                                           <img src="{{ asset('assets/img/icons/carbon_growth.svg') }}" alt="icon" class="img-fluid p-2">
                                      </div>
                                   </div>
                                   <div class="card-body bg-white rounded border mb-1 p-3">
                                      <p class="mb-2 fs-13 fw-medium text-dark">Total Revenue Growth</p>
                                      <div class="fs-28 text-dark fw-bold mb-3">$400k</div>
                                      <div class="fs-13 fw-medium"><span class="text-success"><i class="ti ti-clock"></i> +12%</span> vs Last Month</div>
                                   </div>
                             </div>
                          </div>
                          <div class="col-lg-3 col-md-6 d-flex">
                             <div class="card growth-card flex-fill bg-soft-danger border-0 shadow-none">
                                   <div class="card-header text-center border-0 py-3">
                                      <div class="avatar rounded avatar-md bg-danger">
                                           <img src="{{ asset('assets/img/icons/hand-icon.svg') }}" alt="icon" class="img-fluid p-2">
                                      </div>
                                   </div>
                                   <div class="card-body bg-white rounded border mb-1 p-3">
                                      <p class="mb-2 fs-13 fw-medium text-dark">Conversion Rate</p>
                                      <div class="fs-28 text-dark fw-bold mb-3">12.2%</div>
                                      <div class="fs-13 fw-medium"><span class="text-danger"><i class="ti ti-clock"></i> +90%</span> vs Last Month</div>
                                   </div>
                             </div>
                          </div>
                          <div class="col-lg-3 col-md-6 d-flex">
                             <div class="card growth-card flex-fill bg-purple-subtle border-0 shadow-none">
                                   <div class="card-header text-center border-0 py-3">
                                      <div class="avatar rounded avatar-md bg-purple">
                                           <img src="{{ asset('assets/img/icons/users.svg') }}" alt="icon" class="img-fluid p-2">
                                      </div>
                                   </div>
                                   <div class="card-body bg-white rounded border mb-1 p-3">
                                      <p class="mb-2 fs-13 fw-medium text-dark">New Customers</p>
                                      <div class="fs-28 text-dark fw-bold mb-3">560</div>
                                      <div class="fs-13 fw-medium"><span class="text-purple"><i class="ti ti-clock"></i> +10%</span> vs Last Month</div>
                                   </div>
                             </div>
                          </div>
                          <div class="col-lg-3 col-md-6 d-flex">
                             <div class="card growth-card flex-fill bg-soft-warning border-0 shadow-none">
                                   <div class="card-header text-center border-0 py-3">
                                      <div class="avatar rounded avatar-md bg-warning">
                                           <img src="{{ asset('assets/img/icons/fluent_arrow.svg') }}" alt="icon" class="img-fluid p-2">
                                      </div>
                                   </div>
                                   <div class="card-body bg-white rounded border mb-1 p-3">
                                      <p class="mb-2 fs-13 fw-medium text-dark">Monthly Grow</p>
                                      <div class="fs-28 text-dark fw-bold mb-3">8.9%</div>
                                      <div class="fs-13 fw-medium"><span class="text-warning"><i class="ti ti-clock"></i> +24%</span> vs Last Month</div>
                                   </div>
                             </div>
                          </div>
                       </div>
                    </div>

                    <div class="col-xl-3 d-flex">
                        <div class="card flex-fill">
                            <div class="card-header d-flex align-items-center justify-content-between border-0">
                                <span class="fs-18 fw-bold text-dark">Retention</span>
                                <a href="#" class="btn btn-sm btn-icon btn-outline-light"><i class="ti ti-refresh"></i></a>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
                                    <div id="retained-chart"></div>
                                    <div class="text-end">
                                        <div class="fs-24 text-dark fw-semibold mb-1 d-flex align-items-center gap-1">82% <i class="ti ti-arrow-big-up-filled text-success fs-16"></i></div>
                                        <p class="mb-0 fs-13 fw-medium">Retained </p>                                        
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div id="churned-chart"></div>
                                    <div class="text-end">
                                        <div class="fs-24 text-dark fw-semibold mb-1 d-flex align-items-center gap-1">18% <i class="ti ti-arrow-big-down-filled text-danger fs-16"></i></div>
                                        <p class="mb-0 fs-13 fw-medium">Churned</p>                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


				</div>
                <!-- end row -->

				 <!-- start row -->
				<div class="row">
                    <div class="col-md-12 col-xl-6 d-flex">		
						<div class="card flex-fill">							
							<div class="card-body pb-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div class="mb-0 fs-18 fw-bold text-dark">Revenue</div>
                                    <div class="dropdown">
                                        <a class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown" href="javascript:void(0);">
                                            Last 6 Months
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="javascript:void(0);" class="dropdown-item">
                                                Last 30 Days
                                            </a>
                                            <a href="javascript:void(0);" class="dropdown-item">
                                                Last 6 months
                                            </a>
                                            <a href="javascript:void(0);" class="dropdown-item">
                                                Last 12 months
                                            </a>
                                        </div>
                                    </div>
                                </div>
								<div id="revenue-chart2"></div>
							</div>
                        </div>
					</div>
                    <div class="col-md-12 col-xl-6 d-flex">		
						<div class="card flex-fill">
							<div class="card-body pb-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div class="mb-0 fs-18 fw-bold text-dark">Region-wise Growth</div>
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
								<div id="region-wise-growth"></div>
							</div>
                        </div>
					</div>
                </div>
                <!-- end row -->

                <!-- start row -->
				<div class="row">

					<div class="col-md-12">		
						<div class="card">
							<div class="card-body">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="mb-0 fs-18 fw-bold text-dark">Growth Trend</div>
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
								<div id="growth-trend"></div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <span class="position-relative p-1 d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-25 rounded-circle me-1 z-1">
                                   
                                    <span class="p-1 bg-danger pb-0 position-absolute top-50 start-50 translate-middle w-100 z-n1"></span>
                                     <span class="bg-danger rounded-circle p-1 border border-2 border-white"></span>
                                    </span>
                                    <span class="mb-0 fw-medium text-dark fs-12">Revenue</span>
                                </div>
							</div>
                        </div>
					</div>

				</div>
                <!-- end row -->

                <!-- start row -->
                <div class="row">

					<div class="col-md-12 d-flex">		
						<div class="card flex-fill">
							<div class="card-body">
								<div class="mb-4 fs-18 fw-bold text-dark">Growth Overview</div>

								<!-- Growth Overview List -->
                                <div class="table-responsive custom-table table-nowrap">
                                    <table class="table table-nowrap" id="growth-overview-list">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Period</th>
                                                <th>Customers</th>
                                                <th>Conversion Rate</th>
                                                <th>Revenue</th>
                                                <th>Retention Rate</th>
                                                <th>Growth</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Growth Overview List -->

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
<script src="{{ asset('assets/json/growth-overview-list.js') }}"></script>
@endpush

