@extends('layouts.app')

@section('title', 'Staff Directory Grid')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Staff Directory</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Staff Directory</li>
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

                <!-- table header -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
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
                                                <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Employee</a>
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
                                                        <li>
                                                            <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 pt-0 d-flex">View More</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="filter-set-content">
                                            <div class="filter-set-content-head">
                                                <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#owner" aria-expanded="false" aria-controls="owner">Department Name</a>
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
                                                                Sales
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Marketing
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Engineering
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Designing
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Finance
                                                            </label>
                                                        </li>
                                                        <li>
                                                            <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 pt-0 d-flex">View More</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div> 
                                        <div class="filter-set-content">
                                            <div class="filter-set-content-head">
                                                <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#type" aria-expanded="false" aria-controls="type">Team Name</a>
                                            </div>
                                            <div class="filter-set-contents accordion-collapse collapse" id="type" data-bs-parent="#accordionExample">
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
                                                                Customer Success
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Product Strategy
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Business Operations
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Legal & Compliance
                                                            </label>
                                                        </li>
                                                        <li class="mb-1">
                                                            <label class="dropdown-item px-2 d-flex align-items-center">
                                                                <input class="form-check-input m-0 me-1" type="checkbox">
                                                                Business Intelligence
                                                            </label>
                                                        </li>                                                                
                                                        <li>
                                                            <a href="javascript:void(0);" class="link-primary text-decoration-underline p-2 pt-0 d-flex">View More</a>
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
                    <div class="d-flex align-items-center gap-2 flex-wrap">                                                 
                        <div class="d-flex align-items-center shadow p-2 rounded border bg-white view-icons">
                            <a href="{{ route('page', ['slug' => 'staff-directory-list']) }}" class="btn btn-sm p-1 border-0 fs-14"><i class="ti ti-list-tree"></i></a>
                            <a href="{{ route('page', ['slug' => 'staff-directory-grid']) }}" class="flex-shrink-0 btn btn-sm active p-1 border-0 ms-1 fs-14"><i class="ti ti-grid-dots"></i></a>
                        </div>
                            <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-modal"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Staff</a>
                    </div>
                </div>
                <!-- table header -->
                
                <!-- staff Grid -->
                <div class="row">
                    <div class="col-xl-4 col-md-6">
                        <div class="card border shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('page', ['slug' => 'contact-details']) }}"
                                            class="avatar avatar-md flex-shrink-0 me-2 position-relative">
                                            <img src="{{ asset('assets/img/profiles/avatar-16.jpg') }}" alt="img" class="rounded-circle">
                                           <span class="online text-success position-absolute end-0 bottom-0 fs-8"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1 text-dark"><a href="{{ route('page', ['slug' => 'contact-details']) }}" class="fw-semibold">Albert Morgan</a></div>
                                            <p class="text-default mb-0 fs-13"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="87e6ebe5e2f5f3c7e2ffe6eaf7ebe2a9e4e8ea">[email&#160;protected]</a></p>
                                        </div>
                                    </div>
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit-modal"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete_modal"><i
                                                    class="ti ti-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-light p-3 border rounded">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                      <span><i class="ti ti-briefcase text-dark"></i> Role</span><span class="fw-medium text-dark">Sales Lead</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                      <span><i class="ti ti-float-center text-dark"></i> Department</span><span class="fw-medium text-dark">Sales</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card border shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('page', ['slug' => 'contact-details']) }}"
                                            class="avatar avatar-md flex-shrink-0 me-2 position-relative">
                                            <img src="{{ asset('assets/img/profiles/avatar-12.jpg') }}" alt="img" class="rounded-circle">
                                           <span class="online text-danger position-absolute end-0 bottom-0 fs-8"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1 text-dark"><a href="{{ route('page', ['slug' => 'contact-details']) }}" class="fw-semibold">Katherine Brooks</a></div>
                                            <p class="text-default mb-0 fs-13"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="80ebe1f4e8e5f2e9eee5c0e5f8e1edf0ece5aee3efed">[email&#160;protected]</a></p>
                                        </div>
                                    </div>
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit-modal"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete_modal"><i
                                                    class="ti ti-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-light p-3 border rounded">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                      <span><i class="ti ti-briefcase text-dark"></i> Role</span><span class="fw-medium text-dark">Content Writer</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                      <span><i class="ti ti-float-center text-dark"></i> Department</span><span class="fw-medium text-dark">Marketing</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card border shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('page', ['slug' => 'contact-details']) }}"
                                            class="avatar avatar-md flex-shrink-0 me-2 position-relative">
                                            <img src="{{ asset('assets/img/profiles/avatar-08.jpg') }}" alt="img" class="rounded-circle">
                                           <span class="online text-success position-absolute end-0 bottom-0 fs-8"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1 text-dark"><a href="{{ route('page', ['slug' => 'contact-details']) }}" class="fw-semibold">Samantha Reed</a></div>
                                            <p class="text-default mb-0 fs-13"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="8af9ebe7ebe4fee2ebcaeff2ebe7fae6efa4e9e5e7">[email&#160;protected]</a></p>
                                        </div>
                                    </div>
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit-modal"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete_modal"><i
                                                    class="ti ti-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-light p-3 border rounded">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                      <span><i class="ti ti-briefcase text-dark"></i> Role</span><span class="fw-medium text-dark">Accountant</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                      <span><i class="ti ti-float-center text-dark"></i> Department</span><span class="fw-medium text-dark">Finance</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card border shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('page', ['slug' => 'contact-details']) }}"
                                            class="avatar avatar-md flex-shrink-0 me-2 position-relative">
                                            <img src="{{ asset('assets/img/profiles/avatar-10.jpg') }}" alt="img" class="rounded-circle">
                                           <span class="online text-success position-absolute end-0 bottom-0 fs-8"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1 text-dark"><a href="{{ route('page', ['slug' => 'contact-details']) }}" class="fw-semibold">William Anderson</a></div>
                                            <p class="text-default mb-0 fs-13"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="3f48565353565e527f5a475e524f535a115c5052">[email&#160;protected]</a></p>
                                        </div>
                                    </div>
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit-modal"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete_modal"><i
                                                    class="ti ti-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-light p-3 border rounded">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                      <span><i class="ti ti-briefcase text-dark"></i> Role</span><span class="fw-medium text-dark">UI Designer</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                      <span><i class="ti ti-float-center text-dark"></i> Department</span><span class="fw-medium text-dark">Design</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card border shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('page', ['slug' => 'contact-details']) }}"
                                            class="avatar avatar-md flex-shrink-0 me-2 position-relative">
                                            <img src="{{ asset('assets/img/profiles/avatar-24.jpg') }}" alt="img" class="rounded-circle">
                                           <span class="online text-success position-absolute end-0 bottom-0 fs-8"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1 text-dark"><a href="{{ route('page', ['slug' => 'contact-details']) }}" class="fw-semibold">Jonathan Mitchell</a></div>
                                            <p class="text-default mb-0 fs-13"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="ef8580818e9b878e81af8a978e829f838ac18c8082">[email&#160;protected]</a></p>
                                        </div>
                                    </div>
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit-modal"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete_modal"><i
                                                    class="ti ti-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-light p-3 border rounded">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                      <span><i class="ti ti-briefcase text-dark"></i> Role</span><span class="fw-medium text-dark">Support Lead</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                      <span><i class="ti ti-float-center text-dark"></i> Department</span><span class="fw-medium text-dark">Support</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card border shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('page', ['slug' => 'contact-details']) }}"
                                            class="avatar avatar-md flex-shrink-0 me-2 position-relative">
                                            <img src="{{ asset('assets/img/profiles/avatar-25.jpg') }}" alt="img" class="rounded-circle">
                                           <span class="online text-success position-absolute end-0 bottom-0 fs-8"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1 text-dark"><a href="{{ route('page', ['slug' => 'contact-details']) }}" class="fw-semibold">Jennifer Adams</a></div>
                                            <p class="text-default mb-0 fs-13"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="462c2328282f20233406233e272b362a236825292b">[email&#160;protected]</a></p>
                                        </div>
                                    </div>
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit-modal"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete_modal"><i
                                                    class="ti ti-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-light p-3 border rounded">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                      <span><i class="ti ti-briefcase text-dark"></i> Role</span><span class="fw-medium text-dark">Finance Analyst</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                      <span><i class="ti ti-float-center text-dark"></i> Department</span><span class="fw-medium text-dark">Finance</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card border shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('page', ['slug' => 'contact-details']) }}"
                                            class="avatar avatar-md flex-shrink-0 me-2 position-relative">
                                            <img src="{{ asset('assets/img/profiles/avatar-27.jpg') }}" alt="img" class="rounded-circle">
                                           <span class="online text-success position-absolute end-0 bottom-0 fs-8"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1 text-dark"><a href="{{ route('page', ['slug' => 'contact-details']) }}" class="fw-semibold">Alexander Carter</a></div>
                                            <p class="text-default mb-0 fs-13"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="6e0f020b160f000a0b1c2e0b160f031e020b400d0103">[email&#160;protected]</a></p>
                                        </div>
                                    </div>
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit-modal"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete_modal"><i
                                                    class="ti ti-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-light p-3 border rounded">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                      <span><i class="ti ti-briefcase text-dark"></i> Role</span><span class="fw-medium text-dark">Sales Rep</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                      <span><i class="ti ti-float-center text-dark"></i> Department</span><span class="fw-medium text-dark">Sales</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card border shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('page', ['slug' => 'contact-details']) }}"
                                            class="avatar avatar-md flex-shrink-0 me-2 position-relative">
                                            <img src="{{ asset('assets/img/profiles/avatar-10.jpg') }}" alt="img" class="rounded-circle">
                                           <span class="online text-success position-absolute end-0 bottom-0 fs-8"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1 text-dark"><a href="{{ route('page', ['slug' => 'contact-details']) }}" class="fw-semibold">William Anderson</a></div>
                                            <p class="text-default mb-0 fs-13"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="83f4eaefefeae2eec3e6fbe2eef3efe6ade0ecee">[email&#160;protected]</a></p>
                                        </div>
                                    </div>
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit-modal"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete_modal"><i
                                                    class="ti ti-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-light p-3 border rounded">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                      <span><i class="ti ti-briefcase text-dark"></i> Role</span><span class="fw-medium text-dark">UI Designer</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                      <span><i class="ti ti-float-center text-dark"></i> Department</span><span class="fw-medium text-dark">Design</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card border shadow">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('page', ['slug' => 'contact-details']) }}"
                                            class="avatar avatar-md flex-shrink-0 me-2 position-relative">
                                            <img src="{{ asset('assets/img/profiles/avatar-16.jpg') }}" alt="img" class="rounded-circle">
                                           <span class="online text-success position-absolute end-0 bottom-0 fs-8"><i class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                                        </a>
                                        <div>
                                            <div class="fs-14 mb-1 text-dark"><a href="{{ route('page', ['slug' => 'contact-details']) }}" class="fw-semibold">Albert Morgan</a></div>
                                            <p class="text-default mb-0 fs-13"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="afcec3cdcadddbefcad7cec2dfc3ca81ccc0c2">[email&#160;protected]</a></p>
                                        </div>
                                    </div>
                                    <div class="dropdown table-action">
                                        <a href="#" class="action-icon btn btn-icon btn-sm btn-outline-light shadow" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#edit-modal"><i
                                                    class="ti ti-edit text-blue"></i> Edit</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                data-bs-target="#delete_modal"><i
                                                    class="ti ti-trash"></i> Delete</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-light p-3 border rounded">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                      <span><i class="ti ti-briefcase text-dark"></i> Role</span><span class="fw-medium text-dark">Sales Lead</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                      <span><i class="ti ti-float-center text-dark"></i> Department</span><span class="fw-medium text-dark">Sales</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- staff Grid -->

                <div class="load-btn text-center">
                    <a href="javascript:void(0);" class="btn btn-dark"><i class="ti ti-loader me-1"></i> Load More</a>
                </div>

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/choices.js/public/assets/styles/choices.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/quill/quill.snow.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/intltelinput/css/intlTelInput.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/intltelinput/css/demo.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/flatpickr/flatpickr.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/choices.js/public/assets/scripts/choices.min.js') }}"></script>
<script src="{{ asset('assets/plugins/intltelinput/js/intlTelInput.js') }}"></script>
<script src="{{ asset('assets/plugins/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/plugins/flatpickr/flatpickr.min.js') }}"></script>
@endpush

