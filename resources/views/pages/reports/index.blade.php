@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    @include('partials.alerts')

    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Reports</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
        </div>
    </div>

    <ul class="nav nav-tabs custom-tab-1 mb-4" id="reportTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="site-tab" data-bs-toggle="tab" href="#site" role="tab">
                Site Report
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="office-tab" data-bs-toggle="tab" href="#office" role="tab">
                Office Report
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="total-tab" data-bs-toggle="tab" href="#total" role="tab">
                Total Report
            </a>
        </li>
    </ul>

    <div class="tab-content" id="reportTabContent">
        <!-- Site Report -->
        <div class="tab-pane fade show active" id="site" role="tabpanel">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <select name="site_date_from" class="form-select">
                        <option>Date From</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="site_date_to" class="form-select">
                        <option>Date To</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="site_project" class="form-select">
                        <option>Project</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="site_client" class="form-select">
                        <option>Client</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Project</th>
                            <th>Activity</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2024-12-01</td>
                            <td>Project A</td>
                            <td>Site work</td>
                            <td>₹50,000</td>
                            <td>Completed</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Office Report -->
        <div class="tab-pane fade" id="office" role="tabpanel">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <select name="office_date_from" class="form-select">
                        <option>Date From</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="office_date_to" class="form-select">
                        <option>Date To</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="office_project" class="form-select">
                        <option>Project</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="office_client" class="form-select">
                        <option>Client</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Activity</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2024-12-01</td>
                            <td>Office meeting</td>
                            <td>₹5,000</td>
                            <td>Approved</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Total Report -->
        <div class="tab-pane fade" id="total" role="tabpanel">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <select name="total_date_from" class="form-select">
                        <option>Date From</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="total_date_to" class="form-select">
                        <option>Date To</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="total_project" class="form-select">
                        <option>Project</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="total_client" class="form-select">
                        <option>Client</option>
                    </select>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Total Projects</h5>
                            <h3>12</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Total Payments</h5>
                            <h3>₹2.5M</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Project</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Site</td>
                            <td>Project A</td>
                            <td>₹50,000</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection