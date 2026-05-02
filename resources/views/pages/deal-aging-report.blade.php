@extends('layouts.app')

@section('title', 'Deal Aging')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Deal Aging</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item">Reports</li>
                                <li class="breadcrumb-item active" aria-current="page">Deal Aging</li>
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
								<div class="mb-0 fs-16 fw-bold text-dark">Deal Aging Vs Conversion</div>
							</div>
							<div class="card-body">
								<div id="deal-aging"></div>
                                <div class="d-flex alig-items-center gap-2 justify-content-center mt-2">
                                    <span class="fw-medium border rounded text-gray-5 d-flex align-items-center px-2 gap-1"><i class="ti ti-circle-filled fs-8 text-success"></i> Won</span>
                                    <span class="fw-medium border rounded text-gray-5 d-flex align-items-center px-2 gap-1"><i class="ti ti-circle-filled fs-8 text-danger"></i> Lost</span>
                                </div>
							</div>
                        </div>
					</div>
                    <div class="col-md-12 col-xl-5 d-flex">		
						<div class="card flex-fill">
							<div class="card-header">
								<div class="mb-0 fs-16 fw-bold text-dark">Risk Level Split</div>
							</div>
							<div class="card-body">
								<div id="risk-level"></div>
                                <div class="row g-4 mt-2">

                                    <!-- Critical -->
                                    <div class="col-md-6 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                        <span class="me-2 rounded-3 bg-purple p-1 py-2 pe-0"></span>
                                        <span class="mb-0 text-dark">Critical</span>
                                        </div>
                                        <span class="badge badge-tag badge-soft-purple">
                                        16 Deals
                                        </span>
                                    </div>

                                    <!-- Medium -->
                                    <div class="col-md-6 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                        <span class="me-2 rounded-3 bg-warning p-1 py-2 pe-0"></span>
                                        <span class="mb-0 text-dark">Medium</span>
                                        </div>
                                        <span class="badge badge-tag badge-soft-warning text-warning">
                                        67 Deals
                                        </span>
                                    </div>

                                    <!-- High -->
                                    <div class="col-md-6 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                        <span class="me-2 rounded-3 bg-danger p-1 py-2 pe-0"></span>
                                        <span class="mb-0 text-dark">High</span>
                                        </div>
                                        <span class="badge badge-tag badge-soft-danger text-danger">
                                        24 Deals
                                        </span>
                                    </div>

                                    <!-- Low -->
                                    <div class="col-md-6 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                        <span class="me-2 rounded-3 bg-success p-1 py-2 pe-0"></span>
                                       <span class="mb-0 text-dark">Low</span>
                                        </div>
                                        <span class="badge badge-tag badge-soft-success text-success">
                                        34 Deals
                                        </span>
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
                                                    <span>Deal ID</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Deal Name</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Stage</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Days in Stage</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Stage SLA Days</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Aging Bucket</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Risk Level</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-0">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Closed Date</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
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
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown"><i class="ti ti-medal me-2"></i>Deal Name</a>
                                    <div class="dropdown-menu">
                                        <div class="mb-2">
                                            <div class="input-icon-start input-icon position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-md" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item">Annual Software Subscription</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> CRM Onboarding Package</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Enterprise Plan Upgrade</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> BrightWorks Campaign</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Sales Pipeline Optimization</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> CRM Migration Project</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Multi-Store License Renewal</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown"><i class="ti ti-dashboard me-2"></i>Risk Level</a>
                                    <div class="dropdown-menu">
                                        <ul>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item">High</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Medium</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Critical</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Low</a>
                                            </li>
                                        </ul>
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
                            <table class="table table-nowrap" id="deal-aging-list">
                                <thead class="table-light">
                                    <tr>
                                        <th>Deal ID</th>
                                        <th>Deal Name</th>
                                        <th>Stage</th>
                                        <th>Days in Stage</th>
                                        <th>Stage SLA Days</th>
                                        <th>Aging Bucket</th>
                                        <th>Risk Level</th>
                                        <th>Closed Date</th>
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
@endpush

@push('scripts')
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
<script src="{{ asset('assets/plugins/choices.js/public/assets/scripts/choices.min.js') }}"></script>
<script src="{{ asset('assets/plugins/intltelinput/js/intlTelInput.js') }}"></script>
<script src="{{ asset('assets/plugins/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/plugins/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('assets/json/deal-aging-list.js') }}"></script>
@endpush

