@extends('layouts.app')

@section('title', 'Leaflet Maps')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="mb-4">
                    <h4 class="mb-1">Leaflet Maps</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Maps</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Leaflet Maps</li>
                        </ol>
                    </nav>
				</div>
				<!-- End Page Header -->

                <!-- start row-->
                <div class="row">

                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Leaflet Map</div>
                            </div> 
                            <div class="card-body">
                                <div id="map"></div>
                            </div> <!-- end card-body -->
                        </div> <!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Map With Markers,circles and Polygons</div>
                            </div> 
                            <div class="card-body">
                                <div id="map1"></div>
                            </div> <!-- end card-body -->
                        </div> <!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Map With Popup</div>
                            </div> 
                            <div class="card-body">
                                <div id="map-popup"></div>
                            </div> <!-- end card-body -->
                        </div> <!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Map With Custom Icon</div>
                            </div> 
                            <div class="card-body">
                                <div id="map-custom-icon"></div>
                            </div> <!-- end card-body -->
                        </div> <!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Interactive Choropleth Map</div>
                            </div> 
                            <div class="card-body">
                                <div id="interactive-map"></div>
                            </div> <!-- end card-body -->
                        </div> <!-- end card -->
                    </div> <!-- end col -->

                </div>
                <!-- end row -->

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/leaflet/leaflet.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/leaflet/leaflet.js') }}"></script>
<script src="{{ asset('assets/js/leaflet.js') }}"></script>
@endpush

