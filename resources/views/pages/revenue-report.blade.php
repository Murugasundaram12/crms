@extends('layouts.app')

@section('title', 'Revenue Report')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Revenue Report</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item">Reports</li>
                                <li class="breadcrumb-item active" aria-current="page">Revenue Report</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <div id="reportrange" class="reportrange-picker d-flex align-items-center shadow">
                            <i class="ti ti-calendar-due text-dark fs-14 me-1"></i><span class="reportrange-picker-field">9 Jun 25 - 9 Jun 25</span>
                        </div>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh"><i class="ti ti-refresh"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-transition-top"></i></a>
                    </div>
                </div>          
				<!-- End Page Header -->

                <div class="row">
                    <div class="col-md-12 col-xl-7 d-flex">		
						<div class="card flex-fill">
							<div class="card-header">
								<div class="mb-0 fs-16 fw-bold text-dark">Revenue Split</div>
							</div>
							<div class="card-body p-0">
								<div id="revenue-split-report"></div>
							</div>
                        </div>
					</div>
                    <div class="col-md-12 col-xl-5 d-flex">		
						<div class="card flex-fill">
							<div class="card-header">
								<div class="mb-0 fs-16 fw-bold text-dark">Growth % Vs Churn Impact %</div>
							</div>
							<div class="card-body">
								<div class="row row-gap-3 alig-items-center">
                                    <div class="col-md-5">
                                        <div class="mb-4">
                                            <p class="fw-semibold mb-1 fs-13 d-flex alig-items-center"><span class="me-2 rounded-3 bg-info p-1 pb-0 pe-0"></span>Growth Impact %</p>
                                            <div class="mb-1 fs-20 fw-bold text-dark">+6.12</div>
                                            <span>Avg revenue $313,100</span>
                                        </div>
                                        <div>
                                            <p class="fw-semibold mb-1 fs-13 d-flex alig-items-center"><span class="me-2 rounded-3 bg-orange p-1 pb-0 pe-0"></span>Churn Impact %</p>
                                            <div class="mb-1 fs-20 fw-bold text-dark">-1.28</div>
                                            <span>Avg revenue $313,100</span>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div id="growth"></div>
                                    </div>
                                </div>
							</div>
                        </div>
					</div>
                </div>
                
                <!-- card start -->
                <div class="card border-0 rounded-0">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="input-icon input-icon-start position-relative">
                            <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search">
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort By</a>
                                <div class="dropdown-menu">
                                    <ul>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Newest</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item">Oldest</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="btn bg-soft-indigo border-0"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                        class="ti ti-columns-3 me-2"></i>Manage Columns</a>
                                <div class="dropdown-menu dropdown-menu-md dropdown-md p-3">
                                    <ul>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Period</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Total Revenue</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>New Revenue</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Expansion  Revenue</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>MRR</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>ARR</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Growth %</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-0">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Churn Impact %</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                </label>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>                        
                    </div>
                    <div class="card-body">

                        <!-- table header -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown"><i class="ti ti-currency-dollar me-2"></i>$50000 - $500000</a>
                                    <div class="dropdown-menu p-2">
                                        <div id="price-list" class="card-collapse collapse show price-range">
                                            <div>
                                                <div id="price" class="card-collapse collapse show">
                                                    <div>
                                                        <div class="filter-range">
                                                            <input type="text" id="range_03" aria-label="Price Range">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>                                
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">    
                                <a href="#" class="btn btn-primary"><i class="ti ti-player-play me-1"></i>Run Report</a>                            
                                <a href="#" class="btn btn-icon btn-outline-light shadow"><i class="ti ti-download"></i></a>
                            </div>
                        </div>
                        <!-- table header -->

                        <!-- Projects List -->
                        <div class="table-responsive custom-table table-nowrap">
                            <table class="table table-nowrap" id="revenue-report">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th>Total Revenue</th>
                                        <th>New Revenue</th>
                                        <th>Expansion  Revenue</th>
                                        <th>MRR</th>
                                        <th>ARR</th>
                                        <th>Growth %</th>
                                        <th>Churn Impact %</th>
                                    </tr>
                                </thead>
                                <tbody>
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
                        <!-- /Projects List -->
                         
                    </div>
                </div>
                <!-- card end -->

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/choices.js/public/assets/styles/choices.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/quill/quill.snow.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/intltelinput/css/intlTelInput.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/intltelinput/css/demo.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/ion-rangeslider/css/ion.rangeSlider.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
<script src="{{ asset('assets/plugins/ion-rangeslider/js/custom-rangeslider.js') }}"></script>
<script src="{{ asset('assets/plugins/ion-rangeslider/js/ion.rangeSlider.min.js') }}"></script>
<script src="{{ asset('assets/plugins/choices.js/public/assets/scripts/choices.min.js') }}"></script>
<script src="{{ asset('assets/plugins/intltelinput/js/intlTelInput.js') }}"></script>
<script src="{{ asset('assets/plugins/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/plugins/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('assets/json/revenue-report.js') }}"></script>
@endpush

