@extends('layouts.app')

@section('title', 'Sales Rep Comparison Report')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Sales Rep Comparison Report<span class="badge badge-soft-primary ms-2">15</span></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item" aria-current="page">User & Team Reports </li>
                                <li class="breadcrumb-item active" aria-current="page">Sales Rep Comparison Report </li>
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
                <div class="row row-gap-3">
                    <!-- Item 1 -->
                    <div class="col-xxl-8 col-xl-7">
                        <!-- start card -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-1 fs-16">Monthly Estimation Trend</h5>
                                <p class="mb-0">Deals & Revenue metrics</p>
                            </div>
                            <div class="card-body">
                                <div id="sales-rep-chart"></div>
                                <div class="d-flex align-items-center justify-content-center">
                                    <p class="fs-14 fw-medium mb-0 text-dark d-flex align-items-center gap-2">Deals Closed  <img src="{{ asset('assets/img/icons/arrow-icon.svg') }}" alt="arrow"> <span class="text-success">Revenue</span> </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Item 2 -->
                    <div class="col-xxl-4 col-xl-5">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0 fs-16">Top Performer</h5>
                            </div>
                            <div class="card-body">
                                <!-- Item 1 -->
                                <div class="card border-0 mb-4 bg-light-gradient">
                                    <div class="card-body">
                                        <h6 class="fs-28 mb-3">$285K</h6>
                                        <div class="d-flex align-items-center fs-14 fw-medium mb-2">
                                            <a href="javascript:void(0);" class="d-flex align-items-center">
                                                <span class="avatar me-2">
                                                <img class="img-fluid rounded-circle" src="{{ asset('assets/img/profiles/avatar-01.jpg') }}" alt="User Image">
                                                </span>Robert Sheldon
                                            </a>
                                        </div>
                                        <p class="d-flex align-items-center gap-1 mb-0">45 deals closed <span class="d-flex align-items-center fs-6"><i class="ti ti-point-filled"></i></span> 86.5% conversion</p>
                                    </div>
                                </div>
                                <!-- Item 2 -->
                                <div class="card border-0 mb-0 bg-light-gradient">
                                    <div class="card-body">
                                        <h6 class="fs-28 mb-3">$194K</h6>
                                        <div class="d-flex align-items-center fs-14 fw-medium mb-2">
                                            <a href="javascript:void(0);" class="d-flex align-items-center">
                                                <span class="avatar me-2">
                                                <img class="img-fluid rounded-circle" src="{{ asset('assets/img/profiles/avatar-02.jpg') }}" alt="User Image">
                                                </span>John Smith
                                            </a>
                                        </div>
                                        <p class="d-flex align-items-center gap-1 mb-0">32 deals closed <span class="d-flex align-items-center fs-6"><i class="ti ti-point-filled"></i></span> 78.2% conversion</p>
                                    </div>
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
                                                    <span>Sales Rep ID</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Sales Rep Name</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Team</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Total Deals</span>   
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
                                <!-- 1nd Drop down -->
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-user me-2"></i>Sales Rep Name<i class="ti ti-chevron-down ms-2"></i></a>
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
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/profiles/avatar-01.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Robert Sheldon
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/profiles/avatar-02.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Emma Sandoval
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/profiles/avatar-03.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Mike Matheson
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/profiles/avatar-04.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Natalie Ensley
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/profiles/avatar-05.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Antonio Warthen
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <span class="avatar avatar-xs rounded-circle me-2"><img src="{{ asset('assets/img/profiles/avatar-06.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></span>Carol Robillard
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
                                        <th>Sales Rep ID</th>
                                        <th>Sales Rep Name</th>
                                        <th>Team</th>
                                        <th>Total Deals</th>
                                        <th>Total Revenue</th>
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
<script src="{{ asset('assets/json/sales-rep-comparision-report.js') }}"></script>
@endpush

