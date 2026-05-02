@extends('layouts.app')

@section('title', 'Flot Charts')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="mb-4">
                    <h4 class="mb-1">Chart Flot</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Charts</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Chart Flot</li>
                        </ol>
                    </nav>
				</div>
				<!-- End Page Header -->

				<!-- start row -->
                <div class="row">

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <div class="card-title">Bar Chart</div>
                            </div>
                            <div class="card-body">
                                <div id="morrisBar1" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <div class="card-title">Stacked Bar Chart </div>
                            </div>
                            <div class="card-body">
                                <div id="morrisBar3" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <div class="card-title">Line Chart</div>
                            </div>
                            <div class="card-body">
                                <div id="morrisLine1" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <div class="card-title">Area Chart</div>
                            </div>
                            <div class="card-body">
                                <div id="morrisArea1" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <div class="card-title">Line Chart</div>
                            </div>
                            <div class="card-body">
                                <div id="morrisBar6" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <div class="card-title">Line Chart</div>
                            </div>
                            <div class="card-body">
                                <div id="morrisBar7" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <div class="card-title">Donut Chart</div>
                            </div>
                            <div class="card-body">
                                <div id="morrisDonut1" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <div class="card-title">Line Chart</div>
                            </div>
                            <div class="card-body">
                                <div id="morrisline" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                </div>
				<!-- end row -->

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/morris/morris.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/morris/raphael-min.js') }}"></script>
<script src="{{ asset('assets/plugins/morris/morris.min.js') }}"></script>
<script src="{{ asset('assets/plugins/morris/chart-data.js') }}"></script>
@endpush

