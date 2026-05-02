@extends('layouts.app')

@section('title', 'User Activity Report')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">User Activity Report</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item">Reports</li>
                                <li class="breadcrumb-item active" aria-current="page">User Activity Report</li>
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

                <div class="row row-gap-3 mb-4 row-cols-1 row-cols-md-3 row-cols-xl-5">
                    <div class="col d-flex">
                        <div class="card flex-fill mb-0">
                           <div class="card-body">
                                <p class="mb-2 fs-13">Total Users</p>
                                <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-2">                                       
                                       <span class="avatar avatar-md rounded-circle bg-orange text-white">
                                            <i class="ti ti-users fs-20"></i>
                                        </span>
                                        <span class="d-block mb-0 fw-bold fs-28 text-dark">460</span>
                                </div>
                               <div class="d-flex align-items-center gap-2 flex-wrap fs-13">
                                   <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                   From Last Month
                               </div>
                           </div>
                        </div>
                    </div>
                     <div class="col d-flex">
                        <div class="card flex-fill mb-0">
                           <div class="card-body">
                                <p class="mb-2 fs-13">Total Calls Made</p>
                                <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-2">                                       
                                       <span class="avatar avatar-md rounded-circle bg-info text-white">
                                            <i class="ti ti-phone-call fs-20"></i>
                                        </span>
                                        <span class="d-block mb-0 fw-bold fs-28 text-dark">1200</span>
                                </div>
                               <div class="d-flex align-items-center gap-2 flex-wrap fs-13">
                                   <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                   From Last Month
                               </div>
                           </div>
                        </div>
                    </div>
                    <div class="col d-flex">
                        <div class="card flex-fill mb-0">
                           <div class="card-body">
                                <p class="mb-2 fs-13">Total Emails Sent</p>
                                <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-2">                                       
                                       <span class="avatar avatar-md rounded-circle bg-warning text-white">
                                            <i class="ti ti-mail fs-20"></i>
                                        </span>
                                        <span class="d-block mb-0 fw-bold fs-28 text-dark">1340</span>
                                </div>
                               <div class="d-flex align-items-center gap-2 flex-wrap fs-13">
                                   <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                   From Last Month
                               </div>
                           </div>
                        </div>
                    </div>
                    <div class="col d-flex">
                        <div class="card flex-fill mb-0">
                           <div class="card-body">
                                <p class="mb-2 fs-13">Total Meetings Conducted</p>
                                <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-2">                                       
                                       <span class="avatar avatar-md rounded-circle bg-pink text-white">
                                            <i class="ti ti-headset fs-20"></i>
                                        </span>
                                        <span class="d-block mb-0 fw-bold fs-28 text-dark">400</span>
                                </div>
                               <div class="d-flex align-items-center gap-2 flex-wrap fs-13">
                                   <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                   From Last Month
                               </div>
                           </div>
                        </div>
                    </div>
                    <div class="col d-flex">
                        <div class="card flex-fill mb-0">
                           <div class="card-body">
                                <p class="mb-2 fs-13">Total Tasks Completed</p>
                                <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-2">                                       
                                       <span class="avatar avatar-md rounded-circle bg-cyan text-white">
                                            <i class="ti ti-subtask fs-20"></i>
                                        </span>
                                        <span class="d-block mb-0 fw-bold fs-28 text-dark">380</span>
                                </div>
                               <div class="d-flex align-items-center gap-2 flex-wrap fs-13">
                                   <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                   From Last Month
                               </div>
                           </div>
                        </div>
                    </div>
                </div>
                
                <!-- card start -->
                <div class="card border-0 rounded-0">
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
                                                    <span>User</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Calls</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Emails</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Meetings</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Tasks Completed</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Follow ups</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Avg Response Time (hrs)</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-0">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Productivity Score</span>   
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
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow" data-bs-toggle="dropdown"><i class="ti ti-user me-2"></i>User Name</a>
                                    <div class="dropdown-menu">
                                        <div class="mb-2">
                                            <div class="input-icon-start input-icon position-relative">
                                                <span class="input-icon-addon fs-12">
                                                    <i class="ti ti-search"></i>
                                                </span>
                                                <input type="text" class="form-control form-control-md" placeholder="Search">
                                            </div>
                                        </div>
                                        <ul>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item">Robert Johnson</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Isabella Cooper</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> John Smith</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Sophia Parker</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Ethan Reynolds</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Liam Carter</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item"> Noah Mitchell</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">    
                                <a href="#" class="btn btn-primary"><i class="ti ti-player-play me-1"></i>Run Report</a>                            
                                <a href="#" class="btn btn-icon btn-outline-light shadow"><i class="ti ti-download"></i></a>
                            </div>
                        </div>
                        <!-- table header -->

                        <!-- Projects List -->
                        <div class="table-responsive custom-table table-nowrap">
                            <table class="table table-nowrap" id="user-activity-report">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Calls</th>
                                        <th>Emails</th>
                                        <th>Meetings</th>
                                        <th>Tasks Completed</th>
                                        <th>Follow ups</th>
                                        <th>Avg Response Time (hrs)</th>
                                        <th>Productivity Score</th>
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
<script src="{{ asset('assets/json/user-activity-report.js') }}"></script>
@endpush

