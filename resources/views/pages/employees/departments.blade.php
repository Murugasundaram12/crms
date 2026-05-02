@extends('layouts.app')

@section('title', 'Departments')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Departments<span class="badge badge-soft-primary ms-2">125</span></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Departments</li>
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
                
                <!-- card Header start -->
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-4">
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
                        <div class="input-icon input-icon-start position-relative">
                            <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search">
                        </div>
                    </div>
                        
                    <div class="d-inline-flex align-items-center flex-wrap gap-3">
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
                                                    <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">Department Name</a>
                                                </div>
                                                <div class="filter-set-contents accordion-collapse collapse show" id="collapseThree" data-bs-parent="#accordionExample">
                                                    <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                        <ul>
                                                            <li>
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    Sales
                                                                </label>
                                                            </li>
                                                                <li>
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    Engineering
                                                                </label>
                                                            </li>
                                                            <li>
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    Marketing 
                                                                </label>
                                                            </li>
                                                            <li>
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    Designing
                                                                </label>
                                                            </li>
                                                            <li>
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    Finance
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
                                                    <a href="#" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">Department Head</a>
                                                </div>
                                                <div class="filter-set-contents accordion-collapse collapse" id="collapseTwo" data-bs-parent="#accordionExample">
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
                                                    <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#location" aria-expanded="false" aria-controls="location">Location</a>
                                                </div>
                                                <div class="filter-set-contents accordion-collapse collapse" id="location" data-bs-parent="#accordionExample">
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
                                                    <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">Status</a>
                                                </div>
                                                <div class="filter-set-contents accordion-collapse collapse" id="collapseFive" data-bs-parent="#accordionExample">
                                                    <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                        <ul class="mb-0">
                                                            <li class="mb-1">
                                                                <label class="dropdown-item px-2 d-flex align-items-center">
                                                                    <input class="form-check-input m-0 me-1" type="checkbox">
                                                                    Active
                                                                </label>
                                                            </li>
                                                            <li class="mb-1">
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
                                            <a href="{{ route('page', ['slug' => 'contacts-list']) }}" class="btn btn-primary w-100">Filter</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-inline-flex align-items-center shadow p-1 rounded border view-icons bg-white">
                            <a href="{{ route('page', ['slug' => 'departments-list']) }}" class="btn p-2 border-0 fs-14"><i class="ti ti-list-tree"></i></a>
                            <a href="{{ route('page', ['slug' => 'departments']) }}" class="flex-shrink-0 btn p-2 border-0 ms-1 fs-14 active"><i class="ti ti-grid-dots"></i></a>
                        </div>
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_department"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Department</a>
                    </div>
                </div>
                <!-- card Header end -->

                <!-- start row -->
                <div class="row row-gap-4 mb-4">
                    <!-- Item 1 -->
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <h4 class="fs-14 fw-semibold mb-0">Sales</h4>
                                    <div>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                        <ul class="dropdown-menu p-2">                                    
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_department"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="#" class="avatar flex-shrink-0">
                                            <img src="{{ asset('assets/img/profiles/avatar-14.jpg') }}" alt="img" class="rounded-circle">
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1"><a href="#" class="fw-medium">Robert Johnson</a></div>
                                            <p class="text-default mb-0">Department Head</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <p class="mb-0">Total Members: <span class="fw-normal text-dark">18</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Item 2 -->
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <h4 class="fs-14 fw-semibold mb-0">Marketing</h4>
                                    <div>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                        <ul class="dropdown-menu p-2">                                    
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_department"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="#" class="avatar flex-shrink-0">
                                            <img src="{{ asset('assets/img/profiles/avatar-09.jpg') }}" alt="img" class="rounded-circle">
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1"><a href="#" class="fw-medium">Isabella Cooper</a></div>
                                            <p class="text-default mb-0">Department Head</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <p class="mb-0">Total Members: <span class="fw-normal text-dark">20</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Item 3 -->
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <h4 class="fs-14 fw-semibold mb-0">Development</h4>
                                    <div>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                        <ul class="dropdown-menu p-2">                                    
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_department"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="#" class="avatar flex-shrink-0">
                                            <img src="{{ asset('assets/img/profiles/avatar-16.jpg') }}" alt="img" class="rounded-circle">
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1"><a href="#" class="fw-medium">John Smith</a></div>
                                            <p class="text-default mb-0">Department Head</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <p class="mb-0">Total Members: <span class="fw-normal text-dark">30</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Item 4 -->
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <h4 class="fs-14 fw-semibold mb-0">Engineering</h4>
                                    <div>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                        <ul class="dropdown-menu p-2">                                    
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_department"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="#" class="avatar flex-shrink-0">
                                            <img src="{{ asset('assets/img/profiles/avatar-17.jpg') }}" alt="img" class="rounded-circle">
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1"><a href="#" class="fw-medium">Sophia Parker</a></div>
                                            <p class="text-default mb-0">Department Head</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <p class="mb-0">Total Members: <span class="fw-normal text-dark">12</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Item 5 -->
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <h4 class="fs-14 fw-semibold mb-0">Finance</h4>
                                    <div>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                        <ul class="dropdown-menu p-2">                                    
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_department"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="#" class="avatar flex-shrink-0">
                                            <img src="{{ asset('assets/img/profiles/avatar-17.jpg') }}" alt="img" class="rounded-circle">
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1"><a href="#" class="fw-medium">Emma Reynolds</a></div>
                                            <p class="text-default mb-0">Department Head</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <p class="mb-0">Total Members: <span class="fw-normal text-dark">31</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Item 6 -->
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <h4 class="fs-14 fw-semibold mb-0">Customer Support</h4>
                                    <div>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                        <ul class="dropdown-menu p-2">                                    
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_department"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="#" class="avatar flex-shrink-0">
                                            <img src="{{ asset('assets/img/profiles/avatar-02.jpg') }}" alt="img" class="rounded-circle">
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1"><a href="#" class="fw-medium">Liam Carter</a></div>
                                            <p class="text-default mb-0">Department Head</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <p class="mb-0">Total Members: <span class="fw-normal text-dark">44</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Item 7 -->
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <h4 class="fs-14 fw-semibold mb-0">Product Management</h4>
                                    <div>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                        <ul class="dropdown-menu p-2">                                    
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_department"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="#" class="avatar flex-shrink-0">
                                            <img src="{{ asset('assets/img/profiles/avatar-22.jpg') }}" alt="img" class="rounded-circle">
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1"><a href="#" class="fw-medium">Noah Mitchell</a></div>
                                            <p class="text-default mb-0">Department Head</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <p class="mb-0">Total Members: <span class="fw-normal text-dark">22</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Item 8 -->
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <h4 class="fs-14 fw-semibold mb-0">Operations</h4>
                                    <div>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                        <ul class="dropdown-menu p-2">                                    
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_department"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="#" class="avatar flex-shrink-0">
                                            <img src="{{ asset('assets/img/profiles/avatar-20.jpg') }}" alt="img" class="rounded-circle">
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1"><a href="#" class="fw-medium">Mason Hayes</a></div>
                                            <p class="text-default mb-0">Department Head</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <p class="mb-0">Total Members: <span class="fw-normal text-dark">33</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Item 9 -->
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                                    <h4 class="fs-14 fw-semibold mb-0">Engineering</h4>
                                    <div>
                                        <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-light" data-bs-toggle="dropdown" aria-label="more options"><i class="ti ti-dots-vertical"></i></a>
                                        <ul class="dropdown-menu p-2">                                    
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit_department"><i class="ti ti-edit me-2"></i>Edit</a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash me-2"></i>Delete</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="#" class="avatar flex-shrink-0">
                                            <img src="{{ asset('assets/img/profiles/avatar-21.jpg') }}" alt="img" class="rounded-circle">
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1"><a href="#" class="fw-medium">Ron Thompson</a></div>
                                            <p class="text-default mb-0">Department Head</p>
                                        </div>
                                    </div>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <p class="mb-0">Total Members: <span class="fw-normal text-dark">20</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end row -->
                <div class="d-flex align-items-center justify-content-center">
                    <a href="#" class="btn btn-dark d-flex align-items-center gap-2"><i class="ti ti-loader"></i> Load More</a>
                </div>
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

