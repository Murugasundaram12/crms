@extends('layouts.app')

@section('title', 'Apex Charts')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="mb-4">
                    <h4 class="mb-1">Apex Charts</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Charts</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Apex Charts</li>
                        </ol>
                    </nav>
				</div>
				<!-- End Page Header -->

				<!-- start row -->
                <div class="row">

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <h5 class="card-title">Apex Simple</h5>
                            </div>
                            <div class="card-body">
                                <div id="s-line" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <h5 class="card-title">Area Chart</h5>
                            </div>
                            <div class="card-body">
                                <div id="s-line-area" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <h5 class="card-title">Column Chart</h5>
                            </div>
                            <div class="card-body">
                                <div id="s-col" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <h5 class="card-title">Column Stacked Chart</h5>
                            </div>
                            <div class="card-body">
                                <div id="s-col-stacked" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->


                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <h5 class="card-title">Bar Chart</h5>
                            </div>
                            <div class="card-body">
                                <div id="s-bar" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <h5 class="card-title">Mixed Chart</h5>
                            </div>
                            <div class="card-body">
                                <div id="mixed-chart" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <h5 class="card-title">Donut Chart</h5>
                            </div>
                            <div class="card-body">
                                <div id="donut-chart" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-header">
                                <h5 class="card-title">Radial Chart</h5>
                            </div>
                            <div class="card-body">
                                <div id="radial-chart" class="chart-set"></div>
                            </div><!-- end card body -->
                        </div><!-- end card -->
                    </div> <!-- end col -->

                </div>
				<!-- end row -->

            </div>
@endsection

@push('scripts')
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
@endpush

