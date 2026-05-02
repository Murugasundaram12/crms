@extends('layouts.app')

@section('title', 'Lead Funnel Reports')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Lead Funnel</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item" aria-current="page">Reports </li>
                                <li class="breadcrumb-item active" aria-current="page">Lead Funnel </li>
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
                
                <!-- card start -->
                <div class="card">
                    <div class="card-header">
                        <div class="mb-0 fs-16 fw-bold text-dark">Leads Stages by Year</div>
                    </div>
                    <div class="card-body">
                        <!-- start row -->
                        <div class="row row-gap-3">
                            <div class="col-xl-4">
                                <!-- Item 1  -->
                                <div class="d-flex align-items-center gap-2 flex-wrap p-md-3 p-2 border border-color rounded leads-card mb-3">
                                    <div class="avatar avatar-lg bg-purple-200 border-purple">
                                        <img src="{{ asset('assets/img/icons/lead-icon-1.svg') }}" alt="icon">
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 d-flex align-items-center gap-2 text-dark fw-medium">New <i class="ti ti-arrow-right"></i> Contacted</p>
                                        <span class="mb-0 fs-12 d-block mb-1">1253 leads converted</span>
                                        <div class="progress" style="height: 4px; border-radius: 50px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 60%; border-radius: 50px;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Item 2  -->
                                <div class="d-flex align-items-center gap-2 flex-wrap p-md-3 p-2 border border-color rounded leads-card mb-3">
                                    <div class="avatar avatar-lg bg-indigo-200 border-indigo">
                                        <img src="{{ asset('assets/img/icons/lead-icon-2.svg') }}" alt="icon">
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 d-flex align-items-center gap-2 text-dark fw-medium">Contacted <i class="ti ti-arrow-right"></i> Qualified</p>
                                        <span class="mb-0 fs-12 d-block mb-1">1150 leads converted</span>
                                        <div class="progress" style="height: 4px; border-radius: 50px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 70%; border-radius: 50px;" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Item 3  -->
                                <div class="d-flex align-items-center gap-2 flex-wrap p-md-3 p-2 border border-color rounded leads-card mb-3">
                                    <div class="avatar avatar-lg bg-pink-200 border-pink">
                                        <img src="{{ asset('assets/img/icons/lead-icon-3.svg') }}" alt="icon">
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 d-flex align-items-center gap-2 text-dark fw-medium">Qualified <i class="ti ti-arrow-right"></i> Converted</p>
                                        <span class="mb-0 fs-12 d-block mb-1">800 leads converted</span>
                                        <div class="progress" style="height: 4px; border-radius: 50px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 60%; border-radius: 50px;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Item 4  -->
                                <div class="d-flex align-items-center gap-2 flex-wrap p-md-3 p-2 border border-color rounded leads-card mb-3">
                                    <div class="avatar avatar-lg bg-success-200 border-success">
                                        <img src="{{ asset('assets/img/icons/lead-icon-4.svg') }}" alt="icon">
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 d-flex align-items-center gap-2 text-dark fw-medium">Converted <i class="ti ti-arrow-right"></i> Won</p>
                                        <span class="mb-0 fs-12 d-block mb-1">650 leads converted</span>
                                        <div class="progress" style="height: 4px; border-radius: 50px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 70%; border-radius: 50px;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Item 5  -->
                                <div class="d-flex align-items-center gap-2 flex-wrap p-md-3 p-2 border border-color rounded leads-card">
                                    <div class="avatar avatar-lg bg-danger-200 border-danger">
                                        <img src="{{ asset('assets/img/icons/lead-icon-5.svg') }}" alt="icon">
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 d-flex align-items-center gap-2 text-dark fw-medium">Converted <i class="ti ti-arrow-right"></i> Lost</p>
                                        <span class="mb-0 fs-12 d-block mb-1">150 leads converted</span>
                                        <div class="progress" style="height: 4px; border-radius: 50px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 35%; border-radius: 50px;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                            <div class="col-xl-8">
                                <div id="leads-funnel-chart"></div>
                            </div>
                        </div>
                        <!-- end row -->
                    </div>
                </div>
                <!-- card end -->

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
                                                    <span>Ticket ID</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
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
                                                    <span>Campaign</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Lead Status</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Lead Owner</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Created Date</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Probability</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Status</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Action</span>   
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
                                    <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-ti ti-artboard me-2"></i>Source<i class="ti ti-chevron-down ms-2"></i></a>
                                    <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg bg-light p-0">
                                        <div class="filter-set-view p-3">                                            
                                            <div class="accordion" id="accordionExample3">
                                                <div class="filter-set-content mb-0">
                                                    <div class="filter-set-contents accordion-collapse collapse show" id="collapsefour" data-bs-parent="#accordionExample3">
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
                                                                        Google
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Insights
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Campaigns
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Google
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
                                <!-- 3nd Drop down -->
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-ti ti-status-change me-2"></i>Lead Status<i class="ti ti-chevron-down ms-2"></i></a>
                                    <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg bg-light p-0">
                                        <div class="filter-set-view p-3">                                            
                                            <div class="accordion" id="accordionExample2">
                                                <div class="filter-set-content mb-0">
                                                    <div class="filter-set-contents accordion-collapse collapse show" id="collapsefive" data-bs-parent="#accordionExample2">
                                                        <div class="filter-content-list">
                                                            <ul class="mb-0">
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Connected
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
                                                                        Not Connected
                                                                    </label>
                                                                </li>
                                                                <li class="mb-0">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Contacted
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
                            <table class="table table-nowrap" id="Leads-list">
                                <thead class="table-light">
                                    <tr>
                                        <th>Lead ID</th>
                                        <th>Lead Name</th>
                                        <th>Source</th>
                                        <th>Campaign</th>
                                        <th>Lead Status</th>
                                        <th>Lead Owner</th>
                                        <th>Created Date</th>
                                        <th>Probability</th>
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
<script src="{{ asset('assets/json/leadsfunnel-list.js') }}"></script>
@endpush

