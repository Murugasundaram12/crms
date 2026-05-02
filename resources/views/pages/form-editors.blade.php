@extends('layouts.app')

@section('title', 'Form Picker')
@section('content_class', 'pb-0')

@section('content')
<!-- Page Header -->
                <div class="mb-4">
                    <h4 class="mb-1">Editors</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Forms</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Editors</li>
                        </ol>
                    </nav>
				</div>
				<!-- End Page Header -->
                

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Quill Editor</h5>
                    </div>

                    <div class="card-body">
                        <p class="text-muted">Snow is a clean, flat toolbar theme.</p>

                        <div id="snow-editor" style="height: 300px;">
                            <h3><span class="ql-size-large">Hello World!</span></h3>
                            <p><br></p>
                            <h3>This is an simple editable area.</h3>
                            <p><br></p>
                            <ul>
                                <li>
                                    Select a text to reveal the toolbar.
                                </li>
                                <li>
                                    Edit rich document on-the-fly, so elastic!
                                </li>
                            </ul>
                            <p><br></p>
                            <p>
                                End of simple area
                            </p>
                        </div><!-- end snow editor-->
                    </div> <!-- end card body -->
                </div> <!-- end card -->

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Quill Bubble Editor</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Bubble is a simple tooltip based theme.</p>

                        <div id="bubble-editor" style="height: 300px;">
                            <h3><span class="ql-size-large">Hello World!</span></h3>
                            <p><br></p>
                            <h3>This is an simple editable area.</h3>
                            <p><br></p>
                            <ul>
                                <li>
                                    Select a text to reveal the toolbar.
                                </li>
                                <li>
                                    Edit rich document on-the-fly, so elastic!
                                </li>
                            </ul>
                            <p><br></p>
                            <p>
                                End of simple area
                            </p>
                        </div> <!-- end snow editor-->
                    </div> <!-- end card body -->
                </div> <!-- end card -->

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/quill/quill.core.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/quill/quill.snow.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/quill/quill.bubble.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/js/form-quill.js') }}"></script>
@endpush

