@extends('layouts.app')

@section('title', 'Tenant Ticket Details')

@section('content')
<h4 class="mb-4">Tenant Ticket Details</h4>

                <div class="row">
                    <div class="col-lg-10 mx-auto">

                        <div class="mb-3">
                            <a href="{{ route('page', ['slug' => 'tenant-support-tickets']) }}" class="d-inline-flex align-items-center fw-medium"><i class="ti ti-arrow-left me-1"></i>Back to Tenant Tickets</a>
                        </div>

                        <!-- Ticket Details -->
                        <div class="card mb-0">
                            <div class="card-body">

                                <div class="border br-5 mb-3 rounded">
                                    <div class="p-3 bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <h4 class="fs-16 d-flex align-items-center gap-2 flex-wrap">Login Access Error <span class="badge badge-outline-info">#TKT0020</span></h4>
                                        <div class="dropdown">
                                            <a href="javascript:void(0);" class="dropdown-toggle btn bg-white border d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                                <i class="ti ti-badge me-1"></i>Resolved
                                            </a>
                                            <ul class="dropdown-menu  dropdown-menu-end p-2">
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Resolved</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Inprogress</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Open</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">Closed</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="p-3">
                                        <div class="row row-cols-xl-5 row-cols-md-3 row-cols-sm-2 row-cols-1 row-gap-3">
                                            <div class="col">
                                                <h4 class="fs-13 fw-medium mb-1 text-body">Created By</h4>
                                                <div class="d-flex align-items-center gap-2">
                                                    <a href="javascript:void(0);" class="avatar avatar-xs rounded-circle"><img src="{{ asset('assets/img/icons/company-icon-01.svg') }}" class="flex-shrink-0 rounded-circle" alt="img"></a>
                                                    <a href="javascript:void(0);" class="text-truncate fw-medium">NovaWave LLC</a>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <h4 class="fs-13 fw-medium mb-1 text-body">Priority</h4>
                                                <span class="badge bg-danger d-inline-flex align-items-center badge-sm">High</span>
                                            </div>
                                            <div class="col">
                                                <h4 class="fs-13 fw-medium mb-1 text-body">Assigned To</h4>
                                                <div class="d-flex align-items-center gap-2">
                                                    <a href="javascript:void(0);" class="avatar avatar-xs rounded-circle"><img src="{{ asset('assets/img/users/user-07.jpg') }}" class="flex-shrink-0 rounded-circle" alt="img"></a>
                                                    <a href="javascript:void(0);" class="text-truncate fw-medium">Robert Johnson</a>
                                                </div>
                                            </div>
                                            <div class="col">
                                                <h4 class="fs-13 fw-medium mb-1 text-body">Created at</h4>
                                                <p class="fs-14 text-dark">20 Jan 2025</p>
                                            </div>
                                            <div class="col">
                                                <h4 class="fs-13 fw-medium mb-1 text-body">Last Updated</h4>
                                                <p class="fs-14 text-dark">18 Feb 2025</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">					
                                    <h5 class="mb-2 fs-16">Description</h5>
                                    <p class="mb-2">After applying the updated CRM theme, I am experiencing significant layout and design problems that affect my workflow. I urgently need assistance to resolve these display issues and compatibility challenges with the existing modules and plugins to ensure smooth operations and a consistent user experience.</p>
                                    <p class="mb-0">IÃ¢â‚¬â„¢ve made several attempts to adjust theme settings and configurations, but the layout issues persist. The theme appears to conflict with essential CRM modules and custom workflows I heavily depend on.</p>
                                </div>
                                <div class="mb-4 pb-4 border-bottom">					
                                    <h5 class="mb-2 fs-16">Attachments</h5>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <div class="bg-light br-5 p-3 d-flex align-items-center border rounded">
                                            <span class="avatar d-flex align-items-center justify-content-center bg-danger me-2">
                                                <img src="{{ asset('assets/img/icons/pdf-1.svg') }}" alt="img" class="w-auto h-auto">
                                            </span>
                                            <div class="me-3">
                                                <h6 class="fs-14 fw-medium">Credentials.pdf</h6>
                                                <p class="fs-12 mb-0">45 KB</p>
                                            </div>
                                            <a href="javascript:void(0);" class="btn-icon btn-sm rounded-circle d-flex align-items-center justify-content-center bg-white">
                                                <i class="ti ti-download fs-16"></i>
                                            </a>
                                        </div>
                                        <div class="bg-light br-5 p-3 d-flex align-items-center border rounded">
                                            <span class="avatar d-flex align-items-center justify-content-center bg-success me-2">
                                                <img src="{{ asset('assets/img/icons/jpg-1.svg') }}" alt="img" class="w-auto h-auto">
                                            </span>
                                            <div class="me-3">
                                                <h6 class="fs-14 fw-medium">Image2.jpg</h6>
                                                <p class="fs-12 mb-0">38 KB</p>
                                            </div>
                                            <a href="javascript:void(0);" class="btn-icon btn-sm rounded-circle d-flex align-items-center justify-content-center bg-white">
                                                <i class="ti ti-download fs-16"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <div>
                                            <a href="javascript:void(0);" class="avatar rounded-circle"><img src="{{ asset('assets/img/profiles/avatar-27.jpg') }}" alt="img" class="avatar rounded-circle"></a>
                                        </div>
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <p class="fw-medium text-dark mb-0">Rely To :</p>
                                            <a href="javascript:void(0);" class="py-1 px-2 bg-light text-body fs-13 fw-normal rounded d-flex align-items-center">Michael Dawson (<span class="__cf_email__" data-cfemail="d5b8bcb6bdb4b0b9e4e7e695b0adb4b8a5b9b0fbb6bab8">[email&#160;protected]</span>) <i class="ti ti-x ms-1"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <h5 class="mb-2 fs-16">Message</h5>
                                    <div class="editor pages-editor">
                                        <p>Thank you for bringing this issue to our attention. We apologize for the inconvenience caused to your team. Our technical team is currently investigating the password reset and login issue on priority to restore access for your employees. We will keep you updated with progress and notify you as soon as the issue is resolved.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex align-items-center justify-content-end">
                                    <a href="javascript:void(0);" class="btn btn-light me-3">Cancel</a>
                                    <a href="javascript:void(0);" class="btn btn-primary d-flex align-items-center gap-1"> <i class="ti ti-send"></i> Send Reply</a>
                                </div>
                            </div>
                        </div>
                        <!-- /Ticket Details -->
                    </div>
                </div>

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/quill/quill.snow.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/quill/quill.min.js') }}"></script>
@endpush

