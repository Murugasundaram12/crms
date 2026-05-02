@extends('layouts.app')

@section('title', 'Lightbox')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="mb-4">
                    <h4 class="mb-1">Lightbox</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Advanced UI</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Lightbox</li>
                        </ol>
                    </nav>
				</div>
				<!-- End Page Header -->

                <!-- start row -->
                <div class="row">

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Single Image Lightbox</h5>
                            </div>
                            <div class="card-body pb-1">

								<!-- start row -->
                                <div class="row">

                                    <div class="col-md-4 mb-3">
                                        <a href="{{ asset('assets/img/media/img-01.jpg') }}" class="image-popup">
                                            <img src="{{ asset('assets/img/media/img-01.jpg') }}" class="img-fluid" alt="image">
                                        </a>
                                    </div> <!-- end col -->

                                    <div class="col-md-4 mb-3">
                                        <a href="{{ asset('assets/img/media/img-02.jpg') }}" class="image-popup">
                                            <img src="{{ asset('assets/img/media/img-02.jpg') }}" class="img-fluid" alt="image">
                                        </a>
                                    </div> <!-- end col -->

                                </div>
								<!-- end row -->

                            </div> <!-- end card body -->
                        </div> <!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Image with Description</h5>
                            </div>
                            <div class="card-body pb-1">

								<!-- start row -->
                                <div class="row">

                                    <div class="col-md-4 mb-3">
                                        <a href="{{ asset('assets/img/media/img-03.jpg') }}" class="image-popup-desc" data-title="Title 01" data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                            <img src="{{ asset('assets/img/media/img-03.jpg') }}" class="img-fluid" alt="work-thumbnail">
                                        </a>
                                    </div> <!-- end col -->

                                    <div class="col-md-4 mb-3">
                                        <a href="{{ asset('assets/img/media/img-04.jpg') }}" class="image-popup-desc" data-title="Title 02" data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                            <img src="{{ asset('assets/img/media/img-04.jpg') }}" class="img-fluid" alt="work-thumbnail">
                                        </a>
                                    </div> <!-- end col -->

                                    <div class="col-md-4 mb-3">
                                        <a href="{{ asset('assets/img/media/img-05.jpg') }}" class="image-popup-desc" data-title="Title 03" data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                            <img src="{{ asset('assets/img/media/img-05.jpg') }}" class="img-fluid" alt="work-thumbnail">
                                        </a>
                                    </div> <!-- end col -->

                                </div>
								<!-- end row -->

                            </div> <!-- end card body -->
                        </div> <!-- end card -->
                    </div> <!-- end col -->

                </div>
                <!-- end row -->

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/lightbox/glightbox.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/lightbox/glightbox.min.js') }}"></script>
<script src="{{ asset('assets/plugins/lightbox/lightbox.js') }}"></script>
@endpush

