@php($currentUser = auth()->user())
@php($moduleSearchItems = collect([
    ['label' => 'Dashboard', 'route' => route('dashboard'), 'permission' => null, 'keywords' => ['dashboard', 'home']],
    ['label' => 'Clients', 'route' => route('clients.index'), 'permission' => 'clients-list', 'keywords' => ['client', 'clients', 'customer', 'customers']],
    ['label' => 'Projects', 'route' => route('projects.index'), 'permission' => 'projects-list', 'keywords' => ['project', 'projects']],
    ['label' => 'Tasks', 'route' => route('tasks.index'), 'permission' => 'tasks-list', 'keywords' => ['task', 'tasks']],
    ['label' => 'Payments', 'route' => route('payments.index'), 'permission' => 'payments-list', 'keywords' => ['payment', 'payments']],
    ['label' => 'Payment Stages', 'route' => route('payment-stages.index'), 'permission' => 'payment-stages-list', 'keywords' => ['payment stage', 'payment stages', 'stages']],
    ['label' => 'Variations', 'route' => route('variations.index'), 'permission' => 'variations-list', 'keywords' => ['variation', 'variations']],
    ['label' => 'Manage Users', 'route' => route('manage-users'), 'permission' => 'employees-list', 'keywords' => ['user', 'users', 'manage users', 'employee', 'employees']],
    ['label' => 'Employee Salaries', 'route' => route('employee-salaries.index'), 'permission' => 'employees-salary-list', 'keywords' => ['salary', 'salaries', 'employee salary', 'employee salaries']],
    ['label' => 'Labour Roles', 'route' => route('labour_roles.index'), 'permission' => 'labour-roles-list', 'keywords' => ['labour role', 'labour roles']],
    ['label' => 'Labours', 'route' => route('labours.index'), 'permission' => 'labours-list', 'keywords' => ['labour', 'labours', 'worker', 'workers']],
    ['label' => 'Roles', 'route' => route('roles.index'), 'permission' => 'roles-list', 'keywords' => ['role', 'roles']],
    ['label' => 'Permissions', 'route' => route('permissions.index'), 'permission' => 'permissions-list', 'keywords' => ['permission', 'permissions']],
])->map(fn ($item) => $item + ['allowed' => blank($item['permission']) || ($currentUser?->hasPermission($item['permission']) ?? false)]))

<header class="navbar-header">
    <div class="page-container topbar-menu d-flex align-items-center justify-content-between w-100">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('dashboard') }}" class="logo">
                <span class="logo-light">
                    <span class="logo-lg"><img src="{{ asset('assets/img/logo.svg') }}" alt="logo"></span>
                    <span class="logo-sm"><img src="{{ asset('assets/img/logo-small.svg') }}" alt="small logo"></span>
                </span>
                <span class="logo-dark">
                    <span class="logo-lg"><img src="{{ asset('assets/img/logo-white.svg') }}" alt="dark logo"></span>
                </span>
            </a>

            <a id="mobile_btn" class="mobile-btn" href="#sidebar">
                <i class="ti ti-menu-deep fs-24"></i>
            </a>

            <button class="sidenav-toggle-btn btn border-0 p-0" id="toggle_btn2">
                <i class="ti ti-arrow-bar-to-right"></i>
            </button>

            <div class="me-auto d-flex align-items-center header-search d-lg-flex d-none">
                <div class="input-icon position-relative me-2">
                    <input type="text" class="form-control module-search-input" placeholder="Search Keyword">
                    <span class="input-icon-addon d-inline-flex p-0 header-search-icon"><i class="ti ti-command"></i></span>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center">
            <div class="header-item d-flex d-lg-none me-2">
                <button class="topbar-link btn" data-bs-toggle="modal" data-bs-target="#searchModal">
                    <i class="ti ti-search fs-16"></i>
                </button>
            </div>

            <div class="header-item">
                <div class="dropdown me-2">
                    <a href="javascript:void(0);" class="btn topbar-link btnFullscreen"><i class="ti ti-maximize"></i></a>
                </div>
            </div>

            <div class="header-item d-none d-sm-flex">
                <div class="dropdown me-2">
                    <a href="javascript:void(0);" class="btn topbar-link topbar-teal-link" data-bs-toggle="dropdown">
                        <i class="ti ti-layout-grid-add"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-2">
                        <a href="{{ route('clients.index') }}" class="dropdown-item">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="d-flex mb-1 fw-semibold text-dark">Clients</span>
                                </div>
                                <i class="ti ti-chevron-right-pipe text-dark"></i>
                            </div>
                        </a>
                        <a href="{{ route('projects.index') }}" class="dropdown-item">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="d-flex mb-1 fw-semibold text-dark">Projects</span>
                                </div>
                                <i class="ti ti-chevron-right-pipe text-dark"></i>
                            </div>
                        </a>
                        <a href="{{ route('tasks.index') }}" class="dropdown-item">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="d-flex mb-1 fw-semibold text-dark">Tasks</span>
                                </div>
                                <i class="ti ti-chevron-right-pipe text-dark"></i>
                            </div>
                        </a>
                        <a href="{{ route('payments.index') }}" class="dropdown-item">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="d-flex mb-1 fw-semibold text-dark">Payments</span>
                                </div>
                                <i class="ti ti-chevron-right-pipe text-dark"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="header-line"></div>

            <div class="dropdown profile-dropdown d-flex align-items-center justify-content-center ms-2">
                <a href="javascript:void(0);" class="topbar-link dropdown-toggle drop-arrow-none position-relative"
                    data-bs-toggle="dropdown" data-bs-offset="0,22" aria-haspopup="false" aria-expanded="false">
                    <img src="{{ asset($currentUser?->avatar ?: 'assets/img/users/user-01.jpg') }}" width="38"
                        class="rounded-1 d-flex" alt="user-image">
                    <span class="online text-success"><i
                            class="ti ti-circle-filled d-flex bg-white rounded-circle border border-1 border-white"></i></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-2">
                    <div class="d-flex align-items-center bg-light rounded-3 p-2 mb-2">
                        <img src="{{ asset($currentUser?->avatar ?: 'assets/img/users/user-01.jpg') }}"
                            class="rounded-circle" width="42" height="42" alt="Img">
                        <div class="ms-2">
                            <p class="fw-medium text-dark mb-0">{{ $currentUser?->name ?? 'User' }}</p>
                            <span class="d-block fs-13">{{ $currentUser?->role ?? 'Team Member' }}</span>
                        </div>
                    </div>

                    @if($currentUser?->hasPermission('employees-list'))
                        <a href="{{ route('manage-users') }}" class="dropdown-item">
                            <i class="ti ti-user-circle me-1 align-middle"></i>
                            <span class="align-middle">Manage Users</span>
                        </a>
                    @endif

                    @if($currentUser?->hasPermission('employees-salary-list'))
                        <a href="{{ route('employee-salaries.index') }}" class="dropdown-item">
                            <i class="ti ti-cash me-1 align-middle"></i>
                            <span class="align-middle">Employee Salaries</span>
                        </a>
                    @endif

                    @if($currentUser?->hasPermission('roles-list'))
                        <a href="{{ route('roles.index') }}" class="dropdown-item">
                            <i class="ti ti-settings me-1 align-middle"></i>
                            <span class="align-middle">Roles</span>
                        </a>
                    @endif

                    @if($currentUser?->hasPermission('permissions-list'))
                        <a href="{{ route('permissions.index') }}" class="dropdown-item">
                            <i class="ti ti-help-circle me-1 align-middle"></i>
                            <span class="align-middle">Permissions</span>
                        </a>
                    @endif

                    <div class="pt-2 mt-2 border-top">
                        <a href="{{ route('logout') }}" class="dropdown-item text-danger">
                            <i class="ti ti-logout me-1 fs-17 align-middle"></i>
                            <span class="align-middle">Sign Out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- Search Modal -->
<div class="modal fade" id="searchModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-transparent">
            <div class="card shadow-none mb-0">
                <div class="px-3 py-2 d-flex flex-row align-items-center" id="search-top">
                    <i class="ti ti-search fs-22"></i>
                    <input type="search" class="form-control border-0 module-search-input" placeholder="Search">
                    <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close"><i
                            class="ti ti-x fs-22"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="moduleAccessDeniedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Access Denied</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="moduleAccessDeniedMessage">You do not have permission to open this module.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const moduleItems = @json($moduleSearchItems->values());
            const searchInputs = document.querySelectorAll('.module-search-input');
            const deniedModalElement = document.getElementById('moduleAccessDeniedModal');
            const deniedMessage = document.getElementById('moduleAccessDeniedMessage');
            const deniedModal = deniedModalElement ? new bootstrap.Modal(deniedModalElement) : null;

            const findModule = (query) => {
                const search = query.trim().toLowerCase();

                if (!search) {
                    return null;
                }

                return moduleItems.find((item) =>
                    item.label.toLowerCase().includes(search) ||
                    item.keywords.some((keyword) => keyword.toLowerCase().includes(search) || search.includes(keyword.toLowerCase()))
                ) || null;
            };

            searchInputs.forEach((input) => {
                input.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();

                    const moduleItem = findModule(input.value);

                    if (!moduleItem) {
                        return;
                    }

                    if (!moduleItem.allowed) {
                        if (deniedMessage) {
                            deniedMessage.textContent = `You do not have permission to open the ${moduleItem.label} module.`;
                        }

                        deniedModal?.show();
                        input.value = '';
                        return;
                    }

                    window.location.href = moduleItem.route;
                });
            });
        });
    </script>
@endpush
