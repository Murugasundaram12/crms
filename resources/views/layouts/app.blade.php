<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', config('app.name'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/apple-icon.png') }}">

    <script src="{{ asset('assets/js/theme-script.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/tabler-icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/simplebar/simplebar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="app-style">
    <link rel="stylesheet" href="{{ asset('assets/css/crm-list-ui.css') }}">
    @include('partials.required-field-stars')

    @stack('styles')
</head>

<body>
    <div class="main-wrapper">
        @include('partials.header')
        @include('partials.sidebar')

        <div class="page-wrapper">
            <div class="content @yield('content_class', '')">
                @yield('content')
            </div>

            @include('partials.footer')
        </div>
    </div>

    @include('partials.delete-modal')

    <script src="{{ asset('assets/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>
    <script src="{{ asset('assets/js/laravel-active-menu.js') }}"></script>
    @include('partials.form-ux-guards')
    @auth
    <script>
        window.crmPermissionContext = @json($permissionUiContext ?? ['userPermissions' => [], 'permissionRoutes' => []]);
    </script>
    @endauth
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteModalElement = document.getElementById('crmDeleteModal');

            if (!deleteModalElement) {
                return;
            }

            const deleteTitle = document.getElementById('crmDeleteModalTitle');
            const deleteMessage = document.getElementById('crmDeleteModalMessage');
            const deleteConfirmButton = document.getElementById('crmDeleteConfirmButton');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || document.querySelector('input[name="_token"]')?.value
                || '';

            let deleteActionUrl = '';

            document.querySelectorAll('.crm-delete-trigger').forEach(function (button) {
                button.addEventListener('click', function () {
                    deleteActionUrl = button.getAttribute('data-delete-action') || '';
                    const modalTitle = button.getAttribute('data-delete-title') || 'Confirm Delete';
                    const modalMessage = button.getAttribute('data-delete-message') || 'Are you sure you want to delete this record?';

                    deleteTitle.textContent = modalTitle;
                    deleteMessage.textContent = modalMessage;
                });
            });

            if (deleteConfirmButton) {
                deleteConfirmButton.addEventListener('click', function () {
                    if (!deleteActionUrl) {
                        return;
                    }

                    const deleteForm = document.createElement('form');
                    deleteForm.method = 'POST';
                    deleteForm.action = deleteActionUrl;
                    deleteForm.style.display = 'none';

                    const csrfField = document.createElement('input');
                    csrfField.type = 'hidden';
                    csrfField.name = '_token';
                    csrfField.value = csrfToken;
                    deleteForm.appendChild(csrfField);

                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'DELETE';
                    deleteForm.appendChild(methodField);

                    document.body.appendChild(deleteForm);
                    deleteForm.submit();
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const context = window.crmPermissionContext;
            if (!context || !Array.isArray(context.userPermissions) || !Array.isArray(context.permissionRoutes)) {
                return;
            }

            const allowed = new Set(context.userPermissions);

            const rules = context.permissionRoutes.map(function (route) {
                const escaped = (route.uri || '')
                    .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
                    .replace(/\\\{[^}]+\\\}/g, '[^/]+');

                return {
                    regex: new RegExp('^' + escaped + '$'),
                    methods: Array.isArray(route.methods) ? route.methods.map(function (m) { return String(m).toUpperCase(); }) : ['GET'],
                    permission: route.permission
                };
            });

            function normalizePath(urlValue) {
                try {
                    const parsed = new URL(urlValue, window.location.origin);
                    return parsed.pathname.replace(/^\/+|\/+$/g, '');
                } catch (e) {
                    return null;
                }
            }

            function requiredPermission(path, method) {
                const upperMethod = String(method || 'GET').toUpperCase();
                for (const rule of rules) {
                    if (rule.methods.includes(upperMethod) && rule.regex.test(path)) {
                        return rule.permission;
                    }
                }
                return null;
            }

            function hideElement(el) {
                el.style.display = 'none';
                el.setAttribute('aria-hidden', 'true');
                if ('disabled' in el) {
                    el.disabled = true;
                }
            }

            document.querySelectorAll('a[href]').forEach(function (anchor) {
                const href = anchor.getAttribute('href') || '';
                if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                    return;
                }

                const path = normalizePath(href);
                if (path === null) {
                    return;
                }

                const permission = requiredPermission(path, 'GET');
                if (permission && !allowed.has(permission)) {
                    hideElement(anchor);
                }
            });

            document.querySelectorAll('[data-delete-action]').forEach(function (trigger) {
                const action = trigger.getAttribute('data-delete-action') || '';
                const path = normalizePath(action);
                if (path === null) {
                    return;
                }

                const permission = requiredPermission(path, 'DELETE');
                if (permission && !allowed.has(permission)) {
                    hideElement(trigger);
                }
            });

            document.querySelectorAll('form[action]').forEach(function (form) {
                const action = form.getAttribute('action') || '';
                const path = normalizePath(action);
                if (path === null) {
                    return;
                }

                let method = (form.getAttribute('method') || 'GET').toUpperCase();
                const spoof = form.querySelector('input[name="_method"]');
                if (spoof && spoof.value) {
                    method = spoof.value.toUpperCase();
                }

                const permission = requiredPermission(path, method);
                if (permission && !allowed.has(permission)) {
                    hideElement(form);
                }
            });
        });
    </script>

    @stack('scripts')
    <script src="{{ asset('assets/js/custom-alerts.js') }}"></script>
</body>

</html>
