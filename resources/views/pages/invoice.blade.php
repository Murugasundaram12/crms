@extends('layouts.app')

@section('title', 'Invoice')
@section('content_class', 'content-two')

@section('content')
<!-- Page Header -->
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
                    <div>
                        <h4 class="mb-1">Invoices</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Applications</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Invoices</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="gap-2 d-flex align-items-center flex-wrap">
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh"><i class="ti ti-refresh"></i></a>
                        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-transition-top"></i></a>
                    </div>
                </div>                
				<!-- End Page Header -->

                <!-- start row -->
                <div class="row">

                    <div class="col-xl-3 col-sm-6">
                        <div class="card flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-center overflow-hidden mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Total Invoice</p>
                                        <h6 class="mb-0">$3,237.94</h6>
                                    </div>
                                </div>
                                <div class="attendance-report-bar mb-2">
                                    <div class="progress" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="height: 5px;">
                                        <div class="progress-bar bg-pink" style="width: 85%"></div>
                                    </div>
                                </div>
                                <div>
                                    <p class="d-flex align-items-center text-truncate mb-0"><span class="text-success fs-12 d-flex align-items-center me-1"><i class="ti ti-arrow-wave-right-up me-1"></i>+32.40%</span>from last month</p>
                                </div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div><!-- end col -->

                    <div class="col-xl-3 col-sm-6">
                        <div class="card flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-center overflow-hidden mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Outstanding</p>
                                        <h6 class="mb-0">$3,237.94</h6>
                                    </div>
                                </div>
                                <div class="attendance-report-bar mb-2">
                                    <div class="progress" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="height: 5px;">
                                        <div class="progress-bar bg-purple" style="width: 50%"></div>
                                    </div>
                                </div>
                                <div>
                                    <p class="d-flex align-items-center text-truncate mb-0"><span class="text-danger fs-12 d-flex align-items-center me-1"><i class="ti ti-arrow-wave-right-up me-1"></i>-4.40%</span>from last month</p>
                                </div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div><!-- end col -->

                    <div class="col-xl-3 col-sm-6">
                        <div class="card flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-center overflow-hidden mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Draft</p>
                                        <h6 class="mb-0">$3,237.94</h6>
                                    </div>
                                </div>
                                <div class="attendance-report-bar mb-2">
                                    <div class="progress" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="height: 5px;">
                                        <div class="progress-bar bg-warning" style="width: 30%"></div>
                                    </div>
                                </div>
                                <div>
                                    <p class="d-flex align-items-center text-truncate mb-0"><span class="text-success fs-12 d-flex align-items-center me-1"><i class="ti ti-arrow-wave-right-up me-1"></i>12%</span>from last month</p>
                                </div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div><!-- end col -->

                    <div class="col-xl-3 col-sm-6">
                        <div class="card flex-fill">
                            <div class="card-body">
                                <div class="d-flex align-items-center overflow-hidden mb-2">
                                    <div>
                                        <p class="mb-1 text-truncate">Total Overdue</p>
                                        <h6 class="mb-0">$3,237.94</h6>
                                    </div>
                                </div>
                                <div class="attendance-report-bar mb-2">
                                    <div class="progress" role="progressbar" aria-label="Success example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="height: 5px;">
                                        <div class="progress-bar bg-danger" style="width: 20%"></div>
                                    </div>
                                </div>
                                <div>
                                    <p class="d-flex align-items-center text-truncate mb-0"><span class="text-danger fs-12 d-flex align-items-center me-1"><i class="ti ti-arrow-wave-right-up me-1"></i>-15.40%</span>from last month</p>
                                </div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div><!-- end col -->

                </div>
                <!-- end row -->

                <!-- start row -->
                <div class="row">

                    <div class="col-sm-12">
                        <div>
                            <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-3 mb-3">
                                <h5 class="d-flex align-items-center mb-0">Invoices<span class="badge bg-soft-dark ms-2 text-dark fs-12">2000 Invoices</span></h5>
                                <a href="{{ route('page', ['slug' => 'add-invoices']) }}" class="btn btn-md btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Add Invoices</a>
                            </div>
                            <div class="card-body p-0">

                                <div class="table-responsive">
                                    <table class="table table-nowrap border">
                                        <thead class="table-light">
                                            <tr>
                                                <th>
                                                    <div class="form-check form-check-md">
                                                        <input class="form-check-input" type="checkbox" id="select-all">
                                                    </div>
                                                </th>
                                                <th></th>
                                                <th>Invoice</th>
                                                <th>Name</th>
                                                <th>Created On</th>
                                                <th>Total</th>
                                                <th>Amount Due</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-1454</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-01.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Anthony Lewis</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="54353a203c3b3a2d14312c35392438317a373b39">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>14 Jan 2024, 04:27 AM </td>
                                                <td>$300</td>
                                                <td>$0</td>
                                                <td>14 Jan 2024, 04:27 AM</td>
                                                <td>
                                                    <span class="badge badge-soft-success">
														Paid
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-6571</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-09.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Brian Villalobos</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="d1b3a3b8b0bf91b4a9b0bca1bdb4ffb2bebc">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>21 Jan 2024, 03:19 AM</td>
                                                <td>$547</td>
                                                <td>$200</td>
                                                <td>21 Jan 2024, 03:19 AM</td>
                                                <td>
                                                    <span class="badge badge-soft-danger">
														Overdue
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-2245</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-01.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Harvey Smith</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="70181102061509301508111d001c155e131f1d">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>20 Feb 2024, 12:15 PM</td>
                                                <td>$325</td>
                                                <td>$65</td>
                                                <td>20 Feb 2024, 12:15 PM</td>
                                                <td>
                                                    <span class="badge badge-soft-primary">
														Pending
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-1456</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-02.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Stephan Peralt</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="3a4a5f485b567a5f425b574a565f14595557">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>15 Mar 2024, 12:11 AM</td>
                                                <td>$471</td>
                                                <td>$145</td>
                                                <td>15 Mar 2024, 12:11 AM</td>
                                                <td>
                                                    <span class="badge badge-soft-primary">
														Pending
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-0045</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-03.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Doglas Martini</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="adc0ccdfd9c3c4dadfedc8d5ccc0ddc1c883cec2c0">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>12 Apr 2024, 05:48 PM</td>
                                                <td>$147</td>
                                                <td>$32</td>
                                                <td>12 Apr 2024, 05:48 PM</td>
                                                <td>
                                                    <span class="badge badge-soft-danger">
														Overdue
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-6244</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-02.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Linda Ray</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="0371627a37363543667b626e736f662d606c6e">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>20 Apr 2024, 06:11 PM</td>
                                                <td>$654</td>
                                                <td>$140</td>
                                                <td>20 Apr 2024, 06:11 PM</td>
                                                <td>
                                                    <span class="badge badge-soft-warning">
														Draft
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-9565</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-06.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Elliot Murray</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="3459414646554d74514c55594458511a575b59">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>14 Jan 2024, 04:27 AM </td>
                                                <td>$300</td>
                                                <td>$0</td>
                                                <td>14 Jan 2024, 04:27 AM</td>
                                                <td>
                                                    <span class="badge badge-soft-success">
														Paid
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-6874</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-07.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Rebecca Smtih</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="aeddc3dac7c6eecbd6cfc3dec2cb80cdc1c3">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>02 Sep 2024, 09:21 PM</td>
                                                <td>$654</td>
                                                <td>$65</td>
                                                <td>02 Sep 2024, 09:21 PM</td>
                                                <td>
                                                    <span class="badge badge-soft-success">
														Paid
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-1454</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-08.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Anthony Lewis</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="e889869c80878691a88d90898598848dc68b8785">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>14 Jan 2024, 04:27 AM </td>
                                                <td>$300</td>
                                                <td>$0</td>
                                                <td>14 Jan 2024, 04:27 AM</td>
                                                <td>
                                                    <span class="badge badge-soft-warning">
														Draft
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-6587</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-09.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Connie Waters</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="1f7c707171767a5f7a677e726f737a317c7072">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>15 Nov 2024, 12:44 PM</td>
                                                <td>$987</td>
                                                <td>$47</td>
                                                <td>15 Nov 2024, 12:44 PM</td>
                                                <td>
                                                    <span class="badge badge-soft-primary">
														Pending
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="form-check form-check-md"><input class="form-check-input" type="checkbox"></div>
                                                </td>
                                                <td>
                                                    <div class="set-star rating-select"><i class="ti ti-star-filled fs-16"></i></div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('page', ['slug' => 'invoice-details']) }}">INV-5879</a>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="avatar avatar-sm me-2">
                                                            <img src="{{ asset('assets/img/users/user-10.jpg') }}" class="rounded-circle" alt="user">
                                                        </a>
                                                        <div>
                                                            <h6 class="fw-medium mb-0 fs-14"><a href="{{ route('page', ['slug' => 'invoice-details']) }}">Lori Broaddus</a>
                                                            </h6>
                                                            <span class="fs-12"><a href="https://crms.dreamstechnologies.com/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="7c1e0e131d1818090f3c19041d110c1019521f1311">[email&#160;protected]</a></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>10 Dec 2024, 11:23 PM</td>
                                                <td>$365</td>
                                                <td>$21</td>
                                                <td>10 Dec 2024, 11:23 PM</td>
                                                <td>
                                                    <span class="badge badge-soft-danger">
														Overdue
													</span>
                                                </td>
                                                <td>
                                                    <div class="d-inline-flex align-items-center">
                                                        <a href="{{ route('page', ['slug' => 'invoice-details']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-eye"></i></a>
                                                        <a href="{{ route('page', ['slug' => 'edit-invoices']) }}" class="btn btn-icon btn-sm btn-outline-white border-0"><i class="ti ti-edit"></i></a>
                                                        <a href="#delete_modal" class="btn btn-icon btn-sm btn-outline-white border-0" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div><!-- end col -->
                    
                </div>
                <!-- end row -->

		    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
@endpush

