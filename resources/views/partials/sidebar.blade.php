<div class="sidebar" id="sidebar">
    @php($currentUser = auth()->user())

    <!-- Start Logo -->
    <div class="sidebar-logo">
        <div>
            <!-- Logo Normal -->
            <a href="{{ route('dashboard') }}" class="logo logo-normal">
                <img src="{{ asset('assets/img/logo.svg') }}" alt="Logo">
            </a>

            <!-- Logo Small -->
            <a href="{{ route('dashboard') }}" class="logo-small">
                <img src="{{ asset('assets/img/logo-small.svg') }}" alt="Logo">
            </a>

            <!-- Logo Dark -->
            <a href="{{ route('dashboard') }}" class="dark-logo">
                <img src="{{ asset('assets/img/logo-white.svg') }}" alt="Logo">
            </a>
        </div>
        <button class="sidenav-toggle-btn btn border-0 p-0 active" id="toggle_btn">
            <i class="ti ti-arrow-bar-to-left"></i>
        </button>

        <!-- Sidebar Menu Close -->
        <button class="sidebar-close">
            <i class="ti ti-x align-middle"></i>
        </button>
    </div>
    <!-- End Logo -->

    <!-- Sidenav Menu -->
    <div class="sidebar-inner" data-simplebar>
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title"><span>Menus</span></li>
                <li>
                    <ul>
                        <li><a href="{{ route('dashboard') }}"><i class="ti ti-dashboard"></i><span>Dashboard</span></a>
                        </li>

                        @if($currentUser && $currentUser->hasPermission('clients-list'))
                            <li><a href="{{ route('clients.index') }}"><i class="ti ti-user-up"></i><span>Clients</span></a>
                            </li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('projects-list'))
                            <li><a href="{{ route('projects.index') }}"><i
                                        class="ti ti-atom-2"></i><span>Projects</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('tasks-list'))
                            <li><a href="{{ route('tasks.index') }}"><i class="ti ti-list-check"></i><span>Tasks</span></a>
                            </li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('payments-list'))
                            <li><a href="{{ route('payments.index') }}"><i
                                        class="ti ti-report-money"></i><span>Payments</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('payment-stages-list'))
                            <li><a href="{{ route('payment-stages.index') }}"><i
                                        class="ti ti-list-numbers"></i><span>Payment Stages</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('variations-list'))
                            <li><a href="{{ route('variations.index') }}"><i
                                        class="ti ti-git-branch"></i><span>Variations</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('employees-list'))
                            <li><a href="/manage-users"><i class="ti ti-users"></i><span>Manage Users</span></a></li>
                            @if($currentUser && $currentUser->hasPermission('employees-salary-list'))
                                <li class="sidebar-submenu"><a href="{{ route('employee-salaries.index') }}"><i
                                            class="ti ti-cash me-2"></i><span>Employee Salaries</span></a></li>
                            @endif
                        @endif

                        @if($currentUser && $currentUser->hasPermission('labour-roles-list'))
                            <li><a href="{{ route('labour_roles.index') }}"><i class="ti ti-briefcase"></i><span>Labour
                                        Roles</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('labours-list'))
                            <li><a href="{{ route('labours.index') }}"><i
                                        class="ti ti-user-cog"></i><span>Labours</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('vendors-list'))
                            <li><a href="{{ route('vendors.index') }}"><i
                                        class="ti ti-building-warehouse"></i><span>Vendors</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('main-categories-list'))
                            <li><a href="{{ route('main_categories.index') }}"><i class="ti ti-list-tree"></i><span>Main
                                        Categories</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('categories-list'))
                            <li><a href="{{ route('categories.index') }}"><i
                                        class="ti ti-list-details"></i><span>Categories</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('quotations-list'))
                            <li><a href="{{ route('quotations.list') }}"><i
                                        class="ti ti-file-dollar"></i><span>Quotations</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('expenses-list'))
                            <li><a href="{{ route('expenses.index') }}"><i
                                        class="ti ti-receipt-2"></i><span>Expenses</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('roles-list'))
                                    <li>
                                    <li class="sidebar-submenu"><a href="{{ route('roles.index') }}"><i
                                                class="ti ti-user-shield"></i><span>Roles</span></a></li>
                            </li>
                        @endif

                @if($currentUser && $currentUser->hasPermission('permissions-list'))
                    <li><a href="{{ route('permissions.index') }}"><i
                                class="ti ti-shield-check"></i><span>Permissions</span></a></li>
                @endif
            </ul>
            </li>
            </ul>
        </div>
    </div>

</div>