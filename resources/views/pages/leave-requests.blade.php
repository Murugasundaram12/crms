@extends('layouts.app')

@section('title', 'Leave Requests')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Leave Requests<span class="badge badge-soft-primary ms-2">15</span></h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item">HRM</li>
                                <li class="breadcrumb-item active" aria-current="page">Leave Requests</li>
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
                <div class="row">
                    <div class="col-xl-6 d-flex">
                       <div class="row flex-fill">
                          <div class="col-lg-6 d-flex">
                             <div class="card flex-fill mb-4 bg-success-gradient w-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <span class="avatar avatar-lg rounded bg-success text-white">
                                            <i class="ti ti-circle-check fs-24"></i>
                                        </span>
                                        <div class="text-end">
                                            <p class="mb-0 text-white">Success Rate</p>
                                            <span class="d-block fw-bold fs-28 text-white">228</span>                                            
                                        </div>
                                    </div>
                                    <p class="mb-2 pb-2 text-white border-bottom">Approved</p>
                                    <div class="d-flex align-items-center justify-content-between fs-12 text-white">
                                       Approval Rate <span>92%</span>
                                    </div>
                                </div>
                              </div>
                          </div>
                          <div class="col-lg-6 d-flex">
                             <div class="card flex-fill mb-4 bg-danger-gradient w-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <span class="avatar avatar-lg rounded bg-danger text-white">
                                            <i class="ti ti-x fs-24"></i>
                                        </span>
                                        <div class="text-end">
                                            <p class="mb-0 text-white">Declined</p>
                                            <span class="d-block fw-bold fs-28 text-white">8</span>                                            
                                        </div>
                                    </div>
                                    <p class="mb-2 pb-2 text-white border-bottom">Rejected</p>
                                    <div class="d-flex align-items-center justify-content-between fs-12 text-white">
                                       Rejection Rate <span>3.2%</span>
                                    </div>
                                </div>
                              </div>
                          </div>
                          <div class="col-lg-6 d-flex">
                             <div class="card flex-fill mb-4 bg-info-gradient w-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <span class="avatar avatar-lg rounded bg-info text-white">
                                            <i class="ti ti-user-question fs-24"></i>
                                        </span>
                                        <div class="text-end">
                                            <p class="mb-0 text-white">Total</p>
                                            <span class="d-block fw-bold fs-28 text-white">300</span>                                            
                                        </div>
                                    </div>
                                    <p class="mb-2 pb-2 text-white border-bottom">Leave Request</p>
                                    <div class="d-flex align-items-center justify-content-between fs-12 text-white">
                                       This Month <span>+10%</span>
                                    </div>
                                </div>
                              </div>
                          </div>
                          <div class="col-lg-6 d-flex">
                             <div class="card flex-fill mb-4 bg-warning-gradient w-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                        <span class="avatar avatar-lg rounded bg-warning text-white">
                                            <i class="ti ti-clock fs-24"></i>
                                        </span>
                                        <div class="text-end">
                                            <p class="mb-0 text-white">Urgent</p>
                                            <span class="d-block fw-bold fs-28 text-white">12</span>                                            
                                        </div>
                                    </div>
                                    <p class="mb-2 pb-2 text-white border-bottom">Pending Approvals</p>
                                    <div class="d-flex align-items-center justify-content-between fs-12 text-white">
                                       Action Required <span>+10%</span>
                                    </div>
                                </div>
                              </div>
                          </div>
                       </div>
                    </div>
                    <div class="col-xl-6 d-flex">
                        <div class="card flex-fill">
                            <div class="card-header border-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                                    <div class="mb-0 fs-17 fw-bold text-dark">Attendance Status</div>
                                    <div class="dropdown">
                                        <a class="dropdown-toggle btn btn-outline-light shadow" data-bs-toggle="dropdown" href="javascript:void(0);">
                                            Last 30 days
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="javascript:void(0);" class="dropdown-item">
                                                Last 15 days
                                            </a>
                                            <a href="javascript:void(0);" class="dropdown-item">
                                                Last 30 days
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row align-items-center">
                                    <div class="col-lg-6">
                                        <div id="attendance-status"></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="row row-gap-3">
                                            <div class="col-6 pe-1">
                                                <div class="p-3 rounded bg-light">
                                                    <div class="d-flex align-items-center gap-1 mb-1 fs-13"><i class="ti ti-circle-filled fs-10 text-success"></i> Approved</div>
                                                    <span class="text-dark fs-18 fw-bold">45</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 rounded bg-light">
                                                    <div class="d-flex align-items-center gap-1 mb-1 fs-13"><i class="ti ti-circle-filled fs-10 text-warning"></i> Pending</div>
                                                    <span class="text-dark fs-18 fw-bold">3</span>
                                                </div>
                                            </div>
                                            <div class="col-6 pe-1">
                                                <div class="p-3 rounded bg-light">
                                                    <div class="d-flex align-items-center gap-1 mb-1 fs-13"><i class="ti ti-circle-filled fs-10 text-danger"></i> Declined</div>
                                                    <span class="text-dark fs-18 fw-bold">5</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 rounded bg-light">
                                                    <div class="d-flex align-items-center gap-1 mb-1 fs-13"><i class="ti ti-circle-filled fs-10 text-info"></i> Request</div>
                                                    <span class="text-dark fs-18 fw-bold">23</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> 
                        </div>
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
                        <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-modal"><i class="ti ti-square-rounded-plus-filled me-1"></i>Add Leave Request</a>
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
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">Leave Type</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="collapseFive" data-bs-parent="#accordionExample">
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
                                                                        Annual Leave
                                                                    </label>
                                                                </li>
                                                                 <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Paternity Leave
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Maternity Leave
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Casual Leave
                                                                    </label>
                                                                </li>
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Emergency Leave
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
                                                        <a href="#" class="collapsed" data-bs-toggle="collapse" data-bs-target="#type" aria-expanded="false" aria-controls="type">Status</a>
                                                    </div>
                                                    <div class="filter-set-contents accordion-collapse collapse" id="type" data-bs-parent="#accordionExample">
                                                        <div class="filter-content-list bg-light rounded border p-2 shadow mt-2">
                                                            <ul class="mb-0">
                                                                <li class="mb-1">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Approved
                                                                    </label>
                                                                </li>
                                                                <li class="mb-0">
                                                                    <label class="dropdown-item px-2 d-flex align-items-center">
                                                                        <input class="form-check-input m-0 me-1" type="checkbox">
                                                                        Rejected
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

                        <!-- Projects List -->
                        <div class="table-responsive custom-table table-nowrap">
                            <table class="table table-nowrap" id="leave-request-list">
                                <thead class="table-light">
                                    <tr>
                                        <th>Leave ID</th>
                                        <th>Employee</th>
                                        <th>Leave Type</th>
                                        <th>Duration</th>
                                        <th>Days</th>
                                        <th>Status</th>
                                        <th class="no-sort">Action</th>
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
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
<script src="{{ asset('assets/plugins/choices.js/public/assets/scripts/choices.min.js') }}"></script>
<script src="{{ asset('assets/plugins/intltelinput/js/intlTelInput.js') }}"></script>
<script src="{{ asset('assets/plugins/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/plugins/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('assets/json/leave-request-list.js') }}"></script>
@endpush

