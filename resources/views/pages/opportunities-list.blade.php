@extends('layouts.app')

@section('title', 'Opportunities List')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Opportunities<span class="badge badge-soft-primary ms-2">15</span></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Opportunities</li>
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
                
                <!-- card start -->
                <div class="card border-0 rounded-0">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="input-icon input-icon-start position-relative">
                            <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" placeholder="Search">
                        </div>
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-modal"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Oppurtunity</a>
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
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#owner" aria-expanded="false" aria-controls="owner">Account</a>
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
                                                                        NovaWave LLC
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        BlueSky Industries
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Silver Hawk
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Summit  Peak
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        RiverStone Ltd
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
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#type" aria-expanded="false" aria-controls="type">Oppurtunity Name</a>
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
                                                                        Enterprise Software 
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Cloud Migration Project
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Annual Support Renewal
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Digital Transformation
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Data Analytics Platform
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
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Owner</a>
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
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#Status" aria-expanded="false" aria-controls="Status">Stages</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="Status" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <ul>
                                                                <li>
                                                                     <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Negotiation
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Lost
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Proposal Sent
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Closed Won
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
									<a href="javascript:void(0);" class="btn bg-soft-indigo border-0"
										data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
											class="ti ti-columns-3 me-2"></i>Manage Columns</a>
									<div class="dropdown-menu dropdown-menu-md dropdown-md p-3">
                                        <ul>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Oppurtunity ID</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Oppurtunity Name</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Account</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Expected Value</span>   
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
                                                        <span>Probability</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Owner</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                    </label>
                                                </div>
                                            </li>
                                            <li class="gap-1 d-flex align-items-center mb-2">       
                                                <i class="ti ti-columns me-1"></i>                                     
                                                <div class="form-check form-switch w-100 ps-0">
                                                                                                     
                                                    <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                        <span>Expected Close Date</span>   
                                                        <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
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

                        <!-- Projects List -->
                        <div class="table-responsive custom-table table-nowrap">
                            <table class="table table-nowrap" id="opportunities-list">
                                <thead class="table-light">
                                    <tr>
                                        <th>Opportunity ID</th>
                                        <th>Oppurtunity Name</th>
                                        <th>Account</th>
                                        <th>Expected Value</th>
                                        <th>Stage</th>
                                        <th>Expected Close Date</th>
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
<script src="{{ asset('assets/plugins/choices.js/public/assets/scripts/choices.min.js') }}"></script>
<script src="{{ asset('assets/plugins/intltelinput/js/intlTelInput.js') }}"></script>
<script src="{{ asset('assets/plugins/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/plugins/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('assets/json/opportunities-list.js') }}"></script>
@endpush

