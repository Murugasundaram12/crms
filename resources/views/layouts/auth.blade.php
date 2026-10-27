<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', config('app.name')) | Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Streamline your business with our advanced CRM template.">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-icon.png') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="app-style">
    @include('partials.required-field-stars')

    @stack('styles')
</head>

<body class="account-page bg-white">
    <div class="main-wrapper">
        <div class="overflow-hidden p-3 acc-vh">
            @yield('content')
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>
    @include('partials.form-ux-guards')
    @php($crmValidatorJsVersion = max(
        file_exists(public_path('assets/js/crm-ajax-form-validator.js')) ? filemtime(public_path('assets/js/crm-ajax-form-validator.js')) : 0,
        file_exists(base_path('assets/js/crm-ajax-form-validator.js')) ? filemtime(base_path('assets/js/crm-ajax-form-validator.js')) : 0
    ))
    <script src="{{ asset('assets/js/crm-ajax-form-validator.js') }}?v={{ $crmValidatorJsVersion }}"></script>

    @stack('scripts')
</body>

</html>
