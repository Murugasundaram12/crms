@extends('layouts.app')

@section('title', 'Tenant Support Tickets')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-3 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Tenant Support Tickets<span class="badge badge-soft-primary ms-2">125</span></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Tenant Support Tickets</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <div id="reportrange" class="reportrange-picker d-flex align-items-center shadow">
                            <i class="ti ti-calendar-due text-dark fs-14 me-1"></i><span class="reportrange-picker-field">9 Jun 25 - 9 Jun 25</span>
                        </div>
                        <div class="btn-group support-btn-group" role="group" aria-label="Basic radio toggle button group">
                            <input type="radio" class="btn-check" name="btnradio" id="btnradio1" checked>
                            <label class="btn btn-outline-primary border fw-normal" for="btnradio1">Day</label>

                            <input type="radio" class="btn-check" name="btnradio" id="btnradio2">
                            <label class="btn btn-outline-primary border fw-normal" for="btnradio2">Week</label>

                            <input type="radio" class="btn-check" name="btnradio" id="btnradio3">
                            <label class="btn btn-outline-primary border fw-normal" for="btnradio3">Month</label>

                            <input type="radio" class="btn-check" name="btnradio" id="btnradio4">
                            <label class="btn btn-outline-primary border fw-normal" for="btnradio4">Date Range</label>
                        </div>
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
                
                <!-- Start Add Ticket -->
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-4">
                    <div class="d-inline-flex align-items-center gap-3">
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort By</a>
                            <div class="dropdown-menu">
                                <ul>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item">Recently Viewed</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item">Recently Added</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item">Ascending</a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item">Descending</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="input-icon input-icon-start position-relative">
                            <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search">
                        </div>
                    </div>
                    <div class="d-inline-flex align-items-center gap-3 flex-wrap">
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
                                                <a href="#" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">Assigned</a>
                                            </div>
                                            <div class="filter-set-contents accordion-collapse collapse show" id="collapseTwo" data-bs-parent="#accordionExample">
                                                <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
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
                                                            <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 d-flex">Load More</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="filter-set-content">
                                            <div class="filter-set-content-head">
                                                <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">Tenant Name</a>
                                            </div>
                                            <div class="filter-set-contents accordion-collapse collapse" id="collapseThree" data-bs-parent="#accordionExample">
                                                <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                    <ul>
                                                        <li>
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Sunburst Tech
                                                            </label>
                                                        </li>
                                                            <li>
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Veridian Systems
                                                            </label>
                                                        </li>
                                                        <li>
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Apex Solution
                                                            </label>
                                                        </li>
                                                        <li>
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Zenith Holdings
                                                            </label>
                                                        </li>
                                                        <li>
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Onyx Enterprises
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 d-flex">Load More</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="filter-set-content">
                                            <div class="filter-set-content-head">
                                                <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#owner" aria-expanded="false" aria-controls="owner">Priority</a>
                                            </div>
                                            <div class="filter-set-contents accordion-collapse collapse" id="owner" data-bs-parent="#accordionExample">
                                                <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                    <ul class="mb-0">
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                High
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Medium
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Low
                                                            </label>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>   
                                        <div class="filter-set-content">
                                            <div class="filter-set-content-head">
                                                <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">Status</a>
                                            </div>
                                            <div class="filter-set-contents accordion-collapse collapse" id="collapseFive" data-bs-parent="#accordionExample">
                                                <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                    <ul class="mb-0">
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Resolved
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Open
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Closed
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Pending
                                                            </label>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>                                              
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="javascript:void(0);" class="btn btn-outline-light w-100">Reset</a>
                                        <a href="{{ route('page', ['slug' => 'tenant-support-tickets-grid']) }}" class="btn btn-primary w-100">Filter</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-inline-flex align-items-center shadow p-1 rounded border view-icons bg-white">
                            <a href="{{ route('page', ['slug' => 'tenant-support-tickets']) }}" class="btn p-2 border-0 fs-14"><i class="ti ti-list-tree"></i></a>
                            <a href="{{ route('page', ['slug' => 'tenant-support-tickets-grid']) }}" class="flex-shrink-0 btn p-2 border-0 ms-1 fs-14 active"><i class="ti ti-grid-dots"></i></a>
                        </div>
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_ticktet"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Ticket</a>
                    </div>
                </div>

                <!-- Start row -->
                <div class="row row-gap-4">
                    <div class="col-lg-8">

                        <!-- Card 1 -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <div class="d-flex align-items-center flex-wrap text-dark">
                                        Ticket ID : <span class="badge badge-outline-info ms-2">#TKT0020</span>  
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="badge bg-success">Resloved</span>  
                                        <div>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                            <ul class="dropdown-menu p-2">                                    
                                                <li>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_ticket"><i class="ti ti-edit me-2"></i>Edit Ticket</a>
                                                </li>
                                                <li>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('page', ['slug' => 'tenant-ticket-details']) }}" class="dropdown-item"><i class="ti ti-eye me-2"></i>View Details</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <h5 class="fs-16 mb-2 d-flex align-items-center flex-wrap gap-2">Login Access Error <span class="badge badge-soft-teal">Authentication</span>  </h5>
                                    <p class="mb-0">Our team is unable to log in to the account and requires assistance to regain access.</p>
                                </div>
                                <div class="bg-light border border-color p-2 rounded d-flex align-items-center">
                                    <!-- start row -->
                                    <div class="row w-100 align-items-center row-gap-3 g-0">
                                        <div class="col-xl-4 col-lg-6 col-md-4 col-sm-6">
                                            <p class="mb-0">Tenant : <span class="fw-medium text-dark">Sunburst Tech</span> </p>
                                        </div>
                                        <div class="col-xl-3 col-lg-6 col-md-3 col-sm-6">
                                            <p class="mb-0 text-dark d-flex align-items-center justify-content-start justify-content-xl-center gap-1">Priority : <span class="badge badge-soft-danger border-0"> <i class="ti ti-point-filled"></i>  High</span> </p>
                                        </div>
                                        <div class="col-xl-5 col-lg-12 col-md-5 col-sm-12">
                                            <div class="d-flex align-items-center justify-content-xl-end justify-content-start flex-wrap gap-2">
                                                <p class="mb-0 text-dark">Assignee :</p>
                                                <img src="{{ asset('assets/img/users/avatar-5.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                                <p class="fw-medium d-flex align-items-center justify-content-between gap-2 text-nowrap mb-0 text-dark">Robert Johnson </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end row -->
                                </div>
                            </div>
                        </div>

                        <!-- Card 2 -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <div class="d-flex align-items-center flex-wrap text-dark">
                                        Ticket ID : <span class="badge badge-outline-info ms-2">#TKT0019</span>  
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="badge bg-cyan">Open</span>  
                                        <div>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                            <ul class="dropdown-menu p-2">                                    
                                                <li>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_ticket"><i class="ti ti-edit me-2"></i>Edit Ticket</a>
                                                </li>
                                                <li>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('page', ['slug' => 'tenant-ticket-details']) }}" class="dropdown-item"><i class="ti ti-eye me-2"></i>View Details</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <h5 class="fs-16 mb-2 d-flex align-items-center flex-wrap gap-2">Invoice Mismatch <span class="badge badge-soft-cyan">Billing</span>  </h5>
                                    <p class="mb-0">Our account shows an invoice amount that does not match the actual usage and needs verification.</p>
                                </div>
                                <div class="bg-light border border-color p-2 rounded d-flex align-items-center">
                                    <!-- start row -->
                                    <div class="row w-100 align-items-center row-gap-3 g-0">
                                        <div class="col-xl-4 col-lg-6 col-md-4 col-sm-6">
                                            <p class="mb-0">Tenant : <span class="fw-medium text-dark">Veridian Systems</span> </p>
                                        </div>
                                        <div class="col-xl-3 col-lg-6 col-md-3 col-sm-6">
                                            <p class="mb-0 text-dark d-flex align-items-center justify-content-start justify-content-xl-center gap-1">Priority : <span class="badge badge-soft-danger border-0"> <i class="ti ti-point-filled"></i>  High</span> </p>
                                        </div>
                                        <div class="col-xl-5 col-lg-12 col-md-5 col-sm-12">
                                            <div class="d-flex align-items-center justify-content-xl-end justify-content-start flex-wrap gap-2">
                                                <p class="mb-0 text-dark">Assignee :</p>
                                                <img src="{{ asset('assets/img/users/avatar-6.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                                <p class="fw-medium d-flex align-items-center justify-content-between gap-2 text-nowrap mb-0 text-dark">John Smith </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end row -->
                                </div>
                            </div>
                        </div>

                        <!-- Card 3 -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <div class="d-flex align-items-center flex-wrap text-dark">
                                        Ticket ID : <span class="badge badge-outline-info ms-2">#TKT0018</span>  
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="badge bg-success">Pending</span>  
                                        <div>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                            <ul class="dropdown-menu p-2">                                    
                                                <li>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_ticket"><i class="ti ti-edit me-2"></i>Edit Ticket</a>
                                                </li>
                                                <li>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('page', ['slug' => 'tenant-ticket-details']) }}" class="dropdown-item"><i class="ti ti-eye me-2"></i>View Details</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <h5 class="fs-16 mb-2 d-flex align-items-center flex-wrap gap-2">Invoice Mismatch <span class="badge badge-soft-pink">Performance</span>  </h5>
                                    <p class="mb-0">We are experiencing issues with the dashboard not loading and need support.</p>
                                </div>
                                <div class="bg-light border border-color p-2 rounded d-flex align-items-center">
                                    <!-- start row -->
                                    <div class="row w-100 align-items-center row-gap-3 g-0">
                                        <div class="col-xl-4 col-lg-6 col-md-4 col-sm-6">
                                            <p class="mb-0">Tenant : <span class="fw-medium text-dark">Zenith Holdings</span> </p>
                                        </div>
                                        <div class="col-xl-3 col-lg-6 col-md-3 col-sm-6">
                                            <p class="mb-0 text-dark d-flex align-items-center justify-content-start justify-content-xl-center gap-1">Priority : <span class="badge badge-soft-warning border-0"> <i class="ti ti-point-filled"></i>  Medium</span> </p>
                                        </div>
                                        <div class="col-xl-5 col-lg-12 col-md-5 col-sm-12">
                                            <div class="d-flex align-items-center justify-content-xl-end justify-content-start flex-wrap gap-2">
                                                <p class="mb-0 text-dark">Assignee :</p>
                                                <img src="{{ asset('assets/img/users/avatar-7.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                                <p class="fw-medium d-flex align-items-center justify-content-between gap-2 text-nowrap mb-0 text-dark">Isabella Cooper </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end row -->
                                </div>
                            </div>
                        </div>

                        <!-- Card 4 -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <div class="d-flex align-items-center flex-wrap text-dark">
                                        Ticket ID : <span class="badge badge-outline-info ms-2">#TKT0017</span>  
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="badge bg-danger">Closed</span>  
                                        <div>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                            <ul class="dropdown-menu p-2">                                    
                                                <li>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_ticket"><i class="ti ti-edit me-2"></i>Edit Ticket</a>
                                                </li>
                                                <li>
                                                    <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('page', ['slug' => 'tenant-ticket-details']) }}" class="dropdown-item"><i class="ti ti-eye me-2"></i>View Details</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <h5 class="fs-16 mb-2 d-flex align-items-center flex-wrap gap-2">Export Format Issue  <span class="badge badge-soft-indigo">Reports</span>  </h5>
                                    <p class="mb-0">The exported data is not in the expected format and requires support.</p>
                                </div>
                                <div class="bg-light border border-color p-2 rounded d-flex align-items-center">
                                    <!-- start row -->
                                    <div class="row w-100 align-items-center row-gap-3 g-0">
                                        <div class="col-xl-4 col-lg-6 col-md-4 col-sm-6">
                                            <p class="mb-0">Tenant : <span class="fw-medium text-dark">Everest Group</span> </p>
                                        </div>
                                        <div class="col-xl-3 col-lg-6 col-md-3 col-sm-6">
                                            <p class="mb-0 text-dark d-flex align-items-center justify-content-start justify-content-xl-center gap-1">Priority : <span class="badge badge-soft-success border-0"> <i class="ti ti-point-filled"></i>  Low</span> </p>
                                        </div>
                                        <div class="col-xl-5 col-lg-12 col-md-5 col-sm-12">
                                            <div class="d-flex align-items-center justify-content-xl-end justify-content-start flex-wrap gap-2">
                                                <p class="mb-0 text-dark">Assignee :</p>
                                                <img src="{{ asset('assets/img/users/avatar-9.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                                <p class="fw-medium d-flex align-items-center justify-content-between gap-2 text-nowrap mb-0 text-dark">Sophia Parker </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end row -->
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-center">
                            <a href="javascript:void(0);" class="btn btn-dark d-inline-flex align-items-center gap-1"> <i class="ti ti-loader"></i> Load More</a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <!-- Categories -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Ticket Categories </h5>
                            </div>
                            <div class="card-body">
                                <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">Authentication  <span class="count-value">4</span> </p>
                                <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">Billing  <span class="count-value">2</span> </p>
                                <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">Performance  <span class="count-value">5</span> </p>
                                <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">Reports  <span class="count-value">5</span> </p>
                                <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">Notifications  <span class="count-value">8</span> </p>
                            </div>
                        </div>
                        <!-- Support Agents -->
                        <div class="card mb-0">
                            <div class="card-header">
                                <h5 class="mb-0">Support Agents </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/img/users/avatar-5.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                        <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">Robert Johnson </p>
                                    </div>
                                    <span class="count-value">4</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/img/users/avatar-6.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                        <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">Isabella Cooper </p>
                                    </div>
                                    <span class="count-value">5</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/img/users/avatar-7.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                        <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">Ron Thompson </p>
                                    </div>
                                    <span class="count-value">2</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/img/users/avatar-8.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                        <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">Sophia Parker </p>
                                    </div>
                                    <span class="count-value">8</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/img/users/avatar-9.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                        <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">Mason Hayes </p>
                                    </div>
                                    <span class="count-value">4</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/img/users/avatar-10.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                        <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">Ethan Reynolds </p>
                                    </div>
                                    <span class="count-value">3</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/img/users/user-11.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                        <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">Liam Carter </p>
                                    </div>
                                    <span class="count-value">5</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/img/users/user-12.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                        <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">John Smith </p>
                                    </div>
                                    <span class="count-value">3</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/img/users/user-13.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                        <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">Noah Mitchell </p>
                                    </div>
                                    <span class="count-value">2</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/img/users/user-14.jpg') }}" alt="avatar" class="avatar avatar-sm rounded-circle">
                                        <p class="fw-medium d-flex align-items-center justify-content-between gap-2 flex-wrap mb-0">Linda Stanley </p>
                                    </div>
                                    <span class="count-value">6</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End row -->

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
@endpush

