@extends('layouts.app')

@section('title', 'Attendance Summary Report')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Attendance Summary <span class="badge badge-soft-primary ms-2">15</span></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item" aria-current="page">HRM Reports</li>
                                <li class="breadcrumb-item active" aria-current="page">Attendance Summary </li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh"><i class="ti ti-refresh"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-transition-top"></i></a>
                    </div>
                </div>                
				<!-- End Page Header -->
                
                <!-- start row -->
                <div class="row row-gap-3 mb-4">
                    <!-- Item 1 -->
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                        <div class="card overflow-hidden bg-info-gradient-6 mb-0 border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-start flex-wrap gap-2 mb-2">
                                    <div class="avatar rounded-lg bg-white text-indigo fs-20">
                                        <i class="ti ti-users"></i>
                                    </div>
                                    <div>
                                        <p class="text-dark mb-1 fs-13 fw-medium">Total Employees</p>
                                        <h2 class="fs-28 text-indigo mb-0">256</h2>
                                    </div>  
                                </div>
                                <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-indigo"><i class="ti ti-trending-up"></i></span>
                                        <p class="d-flex align-items-center fw-medium mb-0 gap-1 text-dark"> <span class="text-indigo">+12</span> vs Last Week </p>
                                    </div>
                                    <div id="attendance-summary-report-chart-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Item 2 -->
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12"> 
                        <div class="card overflow-hidden bg-success-gradient-6 mb-0 border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-start flex-wrap gap-2 mb-2">
                                    <div class="avatar rounded-lg bg-white text-success fs-20">
                                        <i class="ti ti-user-check"></i>
                                    </div>
                                    <div>
                                        <p class="text-dark mb-1 fs-13 fw-medium">Present Today</p>
                                        <h2 class="fs-28 text-success mb-0">156</h2>
                                    </div>  
                                </div>
                                <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-success"><i class="ti ti-trending-up"></i></span>
                                        <p class="d-flex align-items-center fw-medium mb-0 gap-1 text-dark"> <span class="text-success">+11</span> vs Last Week </p>
                                    </div>
                                    <div id="attendance-summary-report-chart-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Item 3 -->
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                        <div class="card overflow-hidden bg-purple-gradient-6 mb-0 border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-start flex-wrap gap-2 mb-2">
                                    <div class="avatar rounded-lg bg-white text-purple fs-20">
                                        <i class="ti ti-user-x"></i>
                                    </div>
                                    <div>
                                        <p class="text-dark mb-1 fs-13 fw-medium">Absent Today</p>
                                        <h2 class="fs-28 text-purple mb-0">20</h2>
                                    </div>  
                                </div>
                                <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-purple"><i class="ti ti-trending-up"></i></span>
                                        <p class="d-flex align-items-center fw-medium mb-0 gap-1 text-dark"> <span class="text-purple">+16</span> vs Last Week </p>
                                    </div>
                                    <div id="attendance-summary-report-chart-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Item 4 -->
                    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
                        <div class="card overflow-hidden bg-danger-gradient-6 mb-0 border-0">
                            <div class="card-body">
                                <div class="d-flex align-items-start flex-wrap gap-2 mb-2">
                                    <div class="avatar rounded-lg bg-white text-danger fs-20">
                                        <i class="ti ti-user-down"></i>
                                    </div>
                                    <div>
                                        <p class="text-dark mb-1 fs-13 fw-medium">Late Arrival</p>
                                        <h2 class="fs-28 text-danger mb-0">30</h2>
                                    </div>  
                                </div>
                                <div class="d-flex align-items-end justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-danger"><i class="ti ti-trending-down"></i></span>
                                        <p class="d-flex align-items-center fw-medium mb-0 gap-1 text-dark"> <span class="text-danger">+11</span> vs Last Week </p>
                                    </div>
                                    <div id="attendance-summary-report-chart-4"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- card start -->
                <div class="card">
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
                                                    <span>Total Working Days</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Present Days</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Absent Days</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Late Entries</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Average Work Hours</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Attendance Rate</span>   
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
                                <div id="reportrange" class="reportrange-picker d-flex align-items-center shadow">
                                    <i class="ti ti-calendar-due text-dark fs-14 me-1"></i><span class="reportrange-picker-field">01 Jan 2026 - 31 Dec 2026</span>
                                </div>
                                <!-- 1nd Drop down -->
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-rotate-clockwise-2 me-2"></i>Working Days<i class="ti ti-chevron-down ms-2"></i></a>
                                    <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg bg-light p-0">
                                        <div class="filter-set-view p-3">                                            
                                            <div class="accordion" id="accordionExample">
                                                <div class="filter-set-content mb-0">
                                                    <div class="filter-set-contents accordion-collapse collapse show" id="collapseThree" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list">
                                                            <div class="mb-2">
                                                                <div class="input-icon-start input-icon position-relative">
                                                                    <span class="input-icon-addon fs-12">
                                                                        <i class="ti ti-search"></i>
                                                                    </span>
                                                                    <input type="text" class="form-control form-control-md" placeholder="Search">
                                                                </div>
                                                            </div>
                                                            <ul class="mb-0">
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox" checked>
                                                                        20 Days
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        21 Days
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox" checked>
                                                                        22 Days
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        23 Days
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        24 Days
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        25 Days
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 d-flex">View More</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>                                            
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- 2nd Drop down -->
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-layout-grid me-2"></i>Attendance Rate<i class="ti ti-chevron-down ms-2"></i></a>
                                    <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg bg-light p-0">
                                        <div class="filter-set-view p-3">                                            
                                            <div class="accordion" id="accordionExample2">
                                                <div class="filter-set-content mb-0">
                                                    <div class="filter-set-contents accordion-collapse collapse show" id="collapseThree2" data-bs-parent="#accordionExample2">
                                                        <div class="filter-content-list">
                                                            <ul class="mb-0">
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        100%
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        85%
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        80%
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        40%
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        30%
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        20%
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>                                            
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" class="btn btn-primary"><i class="ti ti-player-play me-1"></i>Run Report</a>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle download-toggle btn btn-icon btn-outline-light shadow" data-bs-toggle="dropdown"><i class="ti ti-download"></i></a>
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
                            </div>
                        </div>
                        <!-- table header -->

                        <!-- Leads List -->
                        <div class="table-responsive table-nowrap custom-table">
                            <table class="table table-nowrap" id="attendance-summary-report">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th>Total Working Days</th>
                                        <th>Present Days</th>
                                        <th>Absent Days</th>
                                        <th>Late Entries</th>
                                        <th>Average Work Hours</th>
                                        <th>Attendance Rate</th>
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
                        <!-- /Leads List -->

                    </div>
                </div>
                <!-- card end -->

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap5.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/json/attendance-summary-report.js') }}"></script>
@endpush

