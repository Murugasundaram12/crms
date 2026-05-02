@extends('layouts.app')

@section('title', 'Blog Details')

@section('content')
<div class="row">
                    <div class="col-lg-10 mx-auto">

                        <div class="mb-4">
                            <a href="{{ route('page', ['slug' => 'blogs']) }}" class="d-inline-flex align-items-center fw-medium"><i class="ti ti-arrow-left me-1"></i>All Blogs</a>
                        </div>
                        

                        <h4 class="mb-4">Improve Efficiency for Sales</h4>

                        <!-- Blog Details -->
                            <div class="mb-4 rounded">
                                <img src="{{ asset('assets/img/blogs/blog-details-1.jpg') }}" alt="img" class="img-fluid rounded w-100">
                            </div>

                            <div class="card mb-0">
                                <div class="card-body">
                                    <p>Boosting sales efficiency is essential for business growth, and a CRM system can play a vital role in streamlining sales processes. By centralizing customer data, automating repetitive tasks, and tracking interactions, CRM tools allow sales teams to focus more on closing deals and building relationships. It minimizes time spent on manual updates and follow-ups, ensuring no leads fall through the cracks. With insightful analytics and performance tracking, sales managers can make smarter, data-driven decisions. Ultimately, CRM platforms help businesses shorten sales cycles, increase conversion rates, and enhance customer satisfaction Ã¢â‚¬â€ all critical for driving long-term success.</p>
                                    <p class="mb-4">CRM systems not only organize your sales pipeline but also enable better team collaboration and communication. With real-time access to customer insights and sales activities, teams can respond faster and more effectively. This results in improved productivity, higher customer engagement, and a consistent approach to meeting sales goals and boosting revenue.</p>
                                    <h6 class="mb-3">Latest Tags</h6>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <a href="javascript:void(0);" class="btn btn-xs fs-12 fw-medium btn-light me-2">Sales Efficiency</a>
                                        <a href="javascript:void(0);" class="btn btn-xs fs-12 fw-medium btn-light me-2">CRM Strategies</a>
                                        <a href="javascript:void(0);" class="btn btn-xs fs-12 fw-medium btn-light me-2">Sales Productivity</a>
                                        <a href="javascript:void(0);" class="btn btn-xs fs-12 fw-medium btn-light me-2">Customer Relationship</a>
                                        <a href="javascript:void(0);" class="btn btn-xs fs-12 fw-medium btn-light me-2">Sales Automation</a>
                                        <a href="javascript:void(0);" class="btn btn-xs fs-12 fw-medium btn-light">Business Growth</a>
                                    </div>
                                </div>
                            </div>
                        <!-- /Blog Details -->
                    </div>
                </div>

            </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/plugins/quill/quill.snow.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/choices.js/public/assets/styles/choices.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/choices.js/public/assets/scripts/choices.min.js') }}"></script>
@endpush

