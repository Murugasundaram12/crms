@extends('layouts.app')

@section('title', 'Holidays')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Holidays<span class="badge badge-soft-primary ms-2">15</span></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item" aria-current="page">HRM</li>
                                <li class="breadcrumb-item active" aria-current="page">Holidays</li>
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


                <!-- row start -->
                <div class="row">
                    <div class="col-xxl-3 col-xl-6 col-md-6 col-sm-6">
                        <div class="card shadow holiday-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 fs-13 fw-medium">Total Holidays</p>
                                        <div class="mb-0 fs-29 text-indigo fw-bold">474</div>
                                    </div>
                                    <span class="avatar avatar-lg rounded-lg bg-soft-indigo text-indigo inset-indigo fs-24 flex-shrink-0"><i class="ti ti-box fs-24"></i></span>
                                </div>
                                <div>
                                    <span class="fs-13 fw-medium mb-1 d-block text-soft-indigo">Active Holidays in 2026</span>
                                    <span class="bg-indigo border-line"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-6 col-md-6 col-sm-6">
                        <div class="card shadow holiday-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 fs-13 fw-medium">National Holidays </p>
                                        <div class="mb-0 fs-29 text-success fw-bold">7</div>
                                    </div>
                                    <span class="avatar avatar-lg rounded-lg bg-soft-success text-success inset-success fs-24 flex-shrink-0"><i class="ti ti-map-pin fs-24"></i></span>
                                </div>
                                <div>
                                    <span class="fs-13 fw-medium mb-1 d-block text-soft-success">Public Holidays </span>
                                    <span class="bg-success border-line"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-6 col-md-6 col-sm-6">
                        <div class="card shadow holiday-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 fs-13 fw-medium">Company Holidays</p>
                                        <div class="mb-0 fs-29 text-info fw-bold">474</div>
                                    </div>
                                    <span class="avatar avatar-lg rounded-lg bg-soft-info text-info inset-info fs-24 flex-shrink-0"><i class="ti ti-gift fs-24"></i></span>
                                </div>
                                <div>
                                    <span class="fs-13 fw-medium mb-1 d-block text-soft-info">Organization specific</span>
                                    <span class="bg-info border-line"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-6 col-md-6 col-sm-6">
                        <div class="card shadow holiday-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <p class="mb-1 fs-13 fw-medium">Optional Holidays</p>
                                        <div class="mb-0 fs-29 text-danger fw-bold">8</div>
                                    </div>
                                    <span class="avatar avatar-lg rounded-lg bg-soft-danger text-danger inset-danger fs-24 flex-shrink-0"><i class="ti ti-chart-line fs-24"></i></span>
                                </div>
                                <div>
                                    <span class="fs-13 fw-medium mb-1 d-block text-soft-danger">Employee Choice</span>
                                    <span class="bg-danger border-line"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- row end -->
                
                <!-- card start -->
                <div class="card border-0 rounded-0">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="input-icon input-icon-start position-relative">
                            <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search">
                        </div>
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_holiday"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Holiday</a>
                    </div>
                    <div class="card-body">

                        <!-- table header -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
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
                                <div id="reportrange" class="reportrange-picker d-flex align-items-center shadow">
                                    <i class="ti ti-calendar-due text-dark fs-14 me-1"></i><span class="reportrange-picker-field">9 Jun 25 - 9 Jun 25</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">                                
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-filter me-2"></i>Filter<i class="ti ti-chevron-down ms-2"></i></a>
                                    <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg p-0">
                                        <div class="filter-header d-flex align-items-center justify-content-between border-bottom">
                                            <h4 class="mb-0 fs-16"><i class="ti ti-filter me-1"></i>Filter</h4>
                                            <button type="button" class="btn-close close-filter-btn" data-bs-dismiss="dropdown-menu" aria-label="Close"></button>
                                        </div>
                                        <div class="filter-set-view p-3">                                            
                                            <div class="accordion" id="accordionExample">
                                                <div class="filter-set-content">
                                                    <div class="filter-set-content-head">
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">Holiday Name</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="collapseThree" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <div class="mb-1">
                                                                <div class="input-icon-start input-icon position-relative">
                                                                    <span class="input-icon-addon fs-12">
                                                                        <i class="ti ti-search"></i>
                                                                    </span>
                                                                    <input type="text" class="form-control form-control-md" placeholder="Search">
                                                                </div>
                                                            </div>
                                                            <ul>
                                                                <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Good Friday
                                                                    </label>
                                                                </li>
                                                                 <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Company Foundation Day
                                                                    </label>
                                                                </li>
                                                                 <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                       Diwali
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Christmas 
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        New Year Eve 
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 pt-0 d-flex">Load More</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="filter-set-content">
                                                    <div class="filter-set-content-head">
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#owner" aria-expanded="false" aria-controls="owner">Applicable Location</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="owner" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <div class="mb-1">
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
                                                                        <img src="{{ asset('assets/img/flags/us.svg') }}" alt="us" class="me-2 img-fluid avatar avatar-xs">  USA
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <img src="{{ asset('assets/img/flags/ca.png') }}" alt="us" class="me-2 img-fluid avatar avatar-xs">  Canada
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        <img src="{{ asset('assets/img/flags/spain.svg') }}" alt="spain" class="me-2 img-fluid avatar avatar-xs">  Spain
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                       <img src="{{ asset('assets/img/flags/india.svg') }}" alt="india" class="me-2 img-fluid avatar avatar-xs">  India
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                       <img src="{{ asset('assets/img/flags/brazil.svg') }}" alt="us" class="me-2 img-fluid avatar avatar-xs">  Brazil
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 pt-0 d-flex">Load More</a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>  
                                                <div class="filter-set-content">
                                                    <div class="filter-set-content-head">
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#Status" aria-expanded="false" aria-controls="Status">Status</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="Status" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <ul>
                                                                <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Active
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Inactive
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>                                             
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="javascript:void(0);" class="btn btn-outline-light w-100">Reset</a>
                                                <a href="javascript:void(0);" class="btn btn-primary w-100">Filter</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- table header -->

                        <!-- Campaign List -->
                        <div class="table-responsive table-nowrap custom-table">
                            <table class="table table-nowrap" id="holidays-list">
                                <thead class="table-light">
                                    <tr>
                                        <th class="no-sort">Holiday ID</th>
                                        <th class="no-sort">Holiday Name</th>
                                        <th class="no-sort">Date</th>
                                        <th class="no-sort">Day</th>
                                        <th class="no-sort">Applicable Location</th>
                                        <th class="no-sort">Status</th>
                                        <th class="text-end no-sort">Action</th>
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
                        <!-- /Campaign List -->
                         
                    </div>
                </div>
                <!-- card end -->

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/datatables/css/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('assets/json/holidays-list.js') }}"></script>
@endpush

