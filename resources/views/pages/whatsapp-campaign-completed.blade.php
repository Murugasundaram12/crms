@extends('layouts.app')

@section('title', 'Whatsapp Campaign')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Whatsapp Campaign<span class="badge badge-soft-primary ms-2">128</span></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Whatsapp Campaign</li>
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
                        <div class="card shadow border-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <div>
                                        <p class="mb-1">Campaign</p>
                                        <h4 class="mb-0 fs-16 text-primary">474</h4>
                                    </div>
                                    <span class="avatar avatar-lg rounded-circle bg-soft-primary text-primary fs-24 flex-shrink-0"><i class="ti ti-box fs-24"></i></span>
                                </div>
                                <div class="d-flex align-items-center text-dark fs-12">
                                    <span class="fs-12  text-success">+18.45% </span>
                                    Ã‚Â from last week
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-6 col-md-6 col-sm-6">
                        <div class="card shadow border-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <div>
                                        <p class="mb-1">Read Rate</p>
                                        <h4 class="mb-0 fs-16 text-warning">99%</h4>
                                    </div>
                                    <span class="avatar avatar-lg rounded-circle bg-soft-warning text-warning fs-24 flex-shrink-0"><i class="ti ti-book-download fs-24"></i></span>
                                </div>
                                <div class="d-flex align-items-center text-dark fs-12">
                                    <span class="fs-12  text-success">+16.55%</span>
                                    Ã‚Â from last week
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-6 col-md-6 col-sm-6">
                        <div class="card shadow border-info">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <div>
                                        <p class="mb-1">Reply Rate</p>
                                        <h4 class="mb-0 fs-16 text-info">80%</h4>
                                    </div>
                                    <span class="avatar avatar-lg rounded-circle bg-soft-info text-info fs-24 flex-shrink-0"><i class="ti ti-replace fs-24"></i></span>
                                </div>
                                <div class="d-flex align-items-center text-dark fs-12">
                                    <span class="fs-12  text-success">+3.21% </span>
                                    Ã‚Â from last week
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-6 col-md-6 col-sm-6">
                        <div class="card shadow border-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <div>
                                        <p class="mb-1">Active Campaign</p>
                                        <h4 class="mb-0 fs-16 text-success">325</h4>
                                    </div>
                                    <span class="avatar avatar-lg rounded-circle bg-soft-success text-success fs-24 flex-shrink-0"><i class="ti ti-align-box-right-stretch fs-24"></i></span>
                                </div>
                                <div class="d-flex align-items-center text-dark fs-12">
                                    <span class="fs-12  text-success">+18.45% </span>
                                    Ã‚Â from last week
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- row end -->

                <!-- Campaign Tab -->
                <div class="campaign-tab">
                    <ul class="nav nav-tabs nav-bordered mb-4">
                        <li class="nav-item">
                            <a href="{{ route('page', ['slug' => 'whatsapp-campaign']) }}" class="nav-link bg-transparent">Active Campaign</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('page', ['slug' => 'whatsapp-campaign-completed']) }}" class="nav-link active bg-transparent text-primary">Completed Campaign</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('page', ['slug' => 'whatsapp-campaign-archieved']) }}" class="nav-link bg-transparent">Archived Campaign</a>
                        </li>
                    </ul>
                </div>
                <!-- Campaign Tab -->
                
                <!-- card start -->
                <div class="card border-0 rounded-0">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="input-icon input-icon-start position-relative">
                            <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search">
                        </div>
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvas_add"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add New Campaign</a>
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
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">Name</a>
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
                                                                        Renewal Reminder
                                                                    </label>
                                                                </li>
                                                                 <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        New Feature Update
                                                                    </label>
                                                                </li>
                                                                 <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                       Subscription Expiry
                                                                    </label>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="filter-set-content">
                                                    <div class="filter-set-content-head">
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#owner" aria-expanded="false" aria-controls="owner">Status</a>
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
                                                                        Pending
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Completed
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Running
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Paused
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Bounced
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
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#date" aria-expanded="false" aria-controls="date">Start Date</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="date" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <div class="input-group w-auto input-group-flat">
                                                                <input type="text" class="form-control" data-provider="flatpickr" data-date-format="d M, Y">
                                                                <span class="input-group-text">
                                                                    <i class="ti ti-calendar"></i>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> 
                                                <div class="filter-set-content">
                                                    <div class="filter-set-content-head">
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#date2" aria-expanded="false" aria-controls="date2">End Date</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="date2" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <div class="input-group w-auto input-group-flat">
                                                                <input type="text" class="form-control" data-provider="flatpickr" data-date-format="d M, Y">
                                                                <span class="input-group-text">
                                                                    <i class="ti ti-calendar"></i>
                                                                </span>
                                                            </div>
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
									<a href="javascript:void(0);" class="btn bg-soft-indigo border-0"
										data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
											class="ti ti-columns-3 me-2"></i>Manage Columns</a>
									<div class="dropdown-menu dropdown-menu-md dropdown-md p-3">
                                        <ul>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Campaign ID</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Name</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Platform</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Objective</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Start Date</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>End Date</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Budget</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Leads Generated</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
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
                                            <li class="gap-1 d-flex align-items-center mb-0">       
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
                        <!-- table header -->

                        <!-- Campaign List -->
                        <div class="table-responsive table-nowrap custom-table">
                            <table class="table table-nowrap" id="whatsapp-campaign-list">
                                <thead class="table-light">
                                    <tr>
                                        <th class="no-sort">Campaign ID</th>
                                        <th class="no-sort">Name</th>
                                        <th class="no-sort">Audience Segment</th>
                                        <th class="no-sort">Message Template</th>
                                        <th>Sent Count</th>
                                        <th class="no-sort">Progress</th>
                                        <th>Status</th>
                                        <th class="text-end no-sort">Action</th>
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
<script src="{{ asset('assets/json/whatsapp-campaign-completed.js') }}"></script>
@endpush

