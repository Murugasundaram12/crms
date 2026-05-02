@extends('layouts.app')

@section('title', 'Ratio Video')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="mb-4">
                    <h4 class="mb-1">Ratio Video</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Base UI</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Ratio Video</li>
                        </ol>
                    </nav>
				</div>
				<!-- End Page Header -->

                <!-- start row -->
                <div class="row">

                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Responsive Ratio video 21:9</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Use class <code>.ratio-21x9</code>
                                <!-- 21:9 aspect ratio -->
                                <div class="ratio ratio-21x9">
                                    <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?autohide=0&amp;showinfo=0&amp;controls=0"></iframe>
                                </div>
                            </div> <!-- end card body -->
                        </div> <!-- end card -->

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Responsive Ratio video 1:1</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Use class <code>.ratio-1x1</code></p>
                                <!-- 1:1 aspect ratio -->
                                <div class="ratio ratio-1x1">
                                    <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?autohide=0&amp;showinfo=0&amp;controls=0"></iframe>
                                </div>
                            </div> <!-- end card body -->
                        </div> <!-- end card -->
                    </div> <!-- end col -->

                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Responsive Ratio video 16:9</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Use class <code>.ratio-16x9</code></p>
                                    <!-- 16:9 aspect ratio -->
                                    <div class="ratio ratio-16x9">
                                        <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?autohide=0&amp;showinfo=0&amp;controls=0"></iframe>
                                    </div>
                            </div> <!-- end card body -->
                        </div> <!-- end card -->

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Responsive Ratio video 4:3</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Use class <code>.ratio-4x3</code></p>
                                 <!-- 4:3 aspect ratio -->
                                 <div class="ratio ratio-4x3">
                                    <iframe src="https://www.youtube.com/embed/6bzTrChjEdc?autohide=0&amp;showinfo=0&amp;controls=0"></iframe>
                                </div>
                            </div> <!-- end card body -->
                        </div> <!-- end card -->
                    </div> <!-- end col -->

                </div>
                <!-- end row -->
                                
            </div>
@endsection

