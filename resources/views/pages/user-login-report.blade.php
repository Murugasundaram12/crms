@extends('layouts.app')

@section('title', 'User Login Report')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">User Login Report</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item">Reports</li>
                                <li class="breadcrumb-item active" aria-current="page">User Login Report</li>
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

                <div class="row">
                    <div class="col-md-12 col-xl-7 d-flex">		
						<div class="card flex-fill">
							<div class="card-header">
								<div class="mb-0 fs-16 fw-bold text-dark">Login Split</div>
							</div>
							<div class="card-body">
								<div id="login-split"></div>
                                <div class="d-flex alig-items-center gap-2 justify-content-center mt-2">
                                    <span class="fw-medium border rounded text-gray-5 d-flex align-items-center px-2 gap-1"><i class="ti ti-circle-filled fs-8 text-success"></i> Successful Logins</span>
                                    <span class="fw-medium border rounded text-gray-5 d-flex align-items-center px-2 gap-1"><i class="ti ti-circle-filled fs-8 text-danger"></i> Failed Logins</span>
                                </div>
							</div>
                        </div>
					</div>
                    <div class="col-md-12 col-xl-5 d-flex">		
						<div class="row row-gap-3 mb-4 flex-fill">
                            <div class="col-md-6 d-flex">
                                <div class="card flex-fill mb-0">
                                <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2">
                                            <div>
                                                <p class="mb-1 fs-13">Total Users</p>
                                                <span class="fw-bold fs-28 text-dark">460</span>
                                            </div>
                                            <span class="avatar avatar-md rounded bg-orange text-white">
                                                <i class="ti ti-users fs-20"></i>
                                            </span>                                                
                                        </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                        <p class="mb-0 fs-13">From Last Month</p>
                                    </div>
                                </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex">
                                <div class="card flex-fill mb-0">
                                <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2">
                                            <div>
                                                <p class="mb-1 fs-13">Active Users</p>
                                                <span class="fw-bold fs-28 text-dark">380</span>
                                            </div>
                                            <span class="avatar avatar-md rounded bg-teal text-white">
                                                <i class="ti ti-user-edit fs-20"></i>
                                            </span>                                                
                                        </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                        <p class="mb-0 fs-13">From Last Month</p>
                                    </div>
                                </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex">
                                <div class="card flex-fill mb-0">
                                <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2">
                                            <div>
                                                <p class="mb-1 fs-13">New Users</p>
                                                <span class="fw-bold fs-28 text-dark">280</span>
                                            </div>
                                            <span class="avatar avatar-md rounded bg-info text-white">
                                                <i class="ti ti-user-plus fs-20"></i>
                                            </span>                                                
                                        </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                        <p class="mb-0 fs-13">From Last Month</p>
                                    </div>
                                </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex">
                                <div class="card flex-fill mb-0">
                                <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2">
                                            <div>
                                                <p class="mb-1 fs-13">Login Success Rate</p>
                                                <span class="fw-bold fs-28 text-dark">85%</span>
                                            </div>
                                            <span class="avatar avatar-md rounded bg-success text-white">
                                                <i class="ti ti-login fs-20"></i>
                                            </span>                                                
                                        </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                        <p class="mb-0 fs-13">From Last Month</p>
                                    </div>
                                </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex">
                                <div class="card flex-fill mb-0">
                                <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2">
                                            <div>
                                                <p class="mb-1 fs-13">Inactive Users</p>
                                                <span class="fw-bold fs-28 text-dark">120</span>
                                            </div>
                                            <span class="avatar avatar-md rounded bg-danger text-white">
                                                <i class="ti ti-user-x fs-20"></i>
                                            </span>                                                
                                        </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                        <p class="mb-0 fs-13">From Last Month</p>
                                    </div>
                                </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex">
                                <div class="card flex-fill mb-0">
                                <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2">
                                            <div>
                                                <p class="mb-1 fs-13">Avg Login Time</p>
                                                <span class="fw-bold fs-28 text-dark">1.2 <span class="fs-14 text-muted fw-normal">sec</span></span>
                                            </div>
                                            <span class="avatar avatar-md rounded bg-pink text-white">
                                                <i class="ti ti-device-desktop fs-20"></i>
                                            </span>                                                
                                        </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-success bg-opacity-10 px-2 text-success border-0 fs-10 rounded-pill">+2.5%</span>
                                        <p class="mb-0 fs-13">From Last Month</p>
                                    </div>
                                </div>
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
                                                    <span>Total Logins</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Successful Logins</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Failed Logins</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-2">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Avg Session Time (Min)</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>     
                                                </label>
                                            </div>
                                        </li>
                                        <li class="gap-1 d-flex align-items-center mb-0">       
                                            <i class="ti ti-columns me-1"></i>                                     
                                            <div class="form-check form-switch w-100 ps-0">
                                                                                                    
                                                <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                    <span>Last Login</span>   
                                                    <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch">     
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
                            <table class="table table-nowrap" id="user-login-report">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Total Logins</th>
                                        <th>Successful Logins</th>
                                        <th>Failed Logins</th>
                                        <th>Avg Session Time (Min)</th>
                                        <th>Last Login</th>
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
<script src="{{ asset('assets/json/user-login-report.js') }}"></script>
@endpush

