@extends('layouts.app')

@section('title', 'Lead Conversion Time Reports')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Lead Conversion Time </h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item" aria-current="page">Reports </li>
                                <li class="breadcrumb-item active" aria-current="page">Lead Conversion Time </li>
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
                
                <!-- start row -->
                <div class="row row-gap-3 mb-4">
                    <!-- Item 1 -->
                    <div class="col-xxl-6 col-xl-12">
                        <!-- start row -->
                        <div class="row row-gap-4">
                            <!-- Item 1 -->
                            <div class="col-md-6">
                                <div class="card mb-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="avatar avatar-lg rounded-lg bg-orange fs-24 flex-shrink-0 p-2">
                                                <img src="{{ asset('assets/img/icons/lead-icon-6.svg') }}" alt="lead-icon" class="img-fluid">
                                            </div>
                                            <span class="badge bg-soft-success rounded-pill text-success">+2.5%</span>
                                        </div>
                                        <div class="d-flex align-items-start justify-content-between mt-3 gap-2">
                                            <div>
                                                <p class="mb-1">Total Converted leads</p>
                                                <h2 class="mb-0">460 <span class="fs-14 fw-normal text-body">Leads</span> </h2>
                                            </div>
                                            <div id="total-chart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Item 2 -->
                            <div class="col-md-6">
                                <div class="card mb-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="avatar avatar-lg rounded-lg bg-pink fs-24 flex-shrink-0 p-2">
                                                <img src="{{ asset('assets/img/icons/lead-icon-7.svg') }}" alt="lead-icon" class="img-fluid">
                                            </div>
                                            <span class="badge bg-soft-success rounded-pill text-success">+2.5%</span>
                                        </div>
                                        <div class="d-flex align-items-start justify-content-between gap-2 mt-3">
                                            <div>
                                                <p class="mb-1">Average Conversion </p>
                                                <h2 class="mb-0">15 <span class="fs-14 fw-normal text-body">Days</span> </h2>
                                            </div>
                                            <div id="average-chart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Item 3 -->
                            <div class="col-md-6">
                                <div class="card mb-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="avatar avatar-lg rounded-lg bg-teal fs-24 flex-shrink-0 p-2">
                                                <img src="{{ asset('assets/img/icons/lead-icon-8.svg') }}" alt="lead-icon" class="img-fluid">
                                            </div>
                                            <span class="badge bg-soft-success rounded-pill text-success">+2.5%</span>
                                        </div>
                                        <div class="d-flex align-items-start justify-content-between gap-2 mt-3">
                                            <div>
                                                <p class="mb-1">Within SLA</p>
                                                <h2 class="mb-0">60 %</h2>
                                            </div>
                                            <div id="within-chart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Item 4 -->
                            <div class="col-md-6">
                                <div class="card mb-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="avatar avatar-lg rounded-lg bg-danger fs-24 flex-shrink-0 p-2">
                                                <img src="{{ asset('assets/img/icons/lead-icon-9.svg') }}" alt="lead-icon" class="img-fluid">
                                            </div>
                                            <span class="badge bg-soft-success rounded-pill text-success">+2.5%</span>
                                        </div>
                                        <div class="d-flex align-items-start justify-content-between gap-2 mt-3">
                                            <div>
                                                <p class="mb-1">Breach Rate</p>
                                                <h2 class="mb-0">12 %</h2>
                                            </div>
                                            <div id="breach-chart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Item 2 -->
                    <div class="col-xxl-6 col-xl-12">
                        <div class="card mb-0">
                            <div class="card-header">
                                <div class="mb-0 fs-16 fw-bold text-dark">Lead Sources Vs SLA Status</div>
                            </div>
                            <div class="card-body py-0">
                                <div id="lead-sources-chart"></div>
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
                                                    <span>Lead ID</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Lead Name</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Source</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Created Date</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Converted Date</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Conversion Days</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Status</span>   
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
                                    <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-user me-2"></i>Lead Name<i class="ti ti-chevron-down ms-2"></i></a>
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
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/users/user-06.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Elizabeth Morgan
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/users/user-40.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Katherine Brooks
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/users/user-05.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Sophia Lopez
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/users/user-10.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>John Michael
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/users/user-15.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Natalie Brooks
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/users/user-01.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>William Turner
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/users/user-13.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Ava Martinez
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/users/user-12.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Nathan Reed
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/users/user-03.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Lily Anderson
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/users/user-18.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Ryan Coleman
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
                                    <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-artboard me-2"></i>Source<i class="ti ti-chevron-down ms-2"></i></a>
                                    <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg bg-light p-0">
                                        <div class="filter-set-view p-3">                                            
                                            <div class="accordion" id="accordionExample2">
                                                <div class="filter-set-content mb-0">
                                                    <div class="filter-set-contents accordion-collapse collapse show" id="collapsefour" data-bs-parent="#accordionExample2">
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
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Phone calls
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Social Media
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Referral Sites
                                                                    </label>
                                                                </li>
                                                                <li class="mb-0">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Campaigns
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
                                <!-- 3rd Drop down -->
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-dashboard me-2"></i>SLA Status<i class="ti ti-chevron-down ms-2"></i></a>
                                    <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg bg-light p-0">
                                        <div class="filter-set-view p-3">                                            
                                            <div class="accordion" id="accordionExample3">
                                                <div class="filter-set-content mb-0">
                                                    <div class="filter-set-contents accordion-collapse collapse show" id="collapsefive" data-bs-parent="#accordionExample3">
                                                        <div class="filter-content-list">
                                                            <ul class="mb-0">
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Paused
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        On Track
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        At Risk
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Breached
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
                            <table class="table table-nowrap" id="Leads-conversion-time-report">
                                <thead class="table-light">
                                    <tr>
                                        <th>Lead ID</th>
                                        <th>Lead Name</th>
                                        <th>Source</th>
                                        <th>Created Date</th>
                                        <th>Converted Date</th>
                                        <th>Conversion Days</th>
                                        <th>Status</th>
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
<script src="{{ asset('assets/json/lead-conversion-time-report.js') }}"></script>
@endpush

