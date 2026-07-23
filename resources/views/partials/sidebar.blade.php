<div class="sidebar" id="sidebar">
    @php($currentUser = auth()->user())
    @php($isReportsRoute = request()->routeIs('reports.index'))
    @php($reportType = request()->string('type')->toString())
    @php($reportType = in_array($reportType, ['site', 'office', 'total'], true) ? $reportType : 'site')
    @php($isExpenseHistoryRoute = request()->routeIs('expenses.history'))
    @php($isExpensesMenuActive = request()->routeIs('expenses.*') || request()->routeIs('expenseReports.*') || $isExpenseHistoryRoute)
    @php($isLabourExpensesMenuActive = request()->routeIs('labour-expenses.*'))
    @php($isVendorExpensesMenuActive = request()->routeIs('vendor-expenses.*'))
    @php($isHistoryMenuActive = request()->routeIs('transfers.*') || request()->routeIs('wallet.*') || request()->routeIs('vendor-expenses.history'))
    @php($isTrackingMenuActive = request()->routeIs('tracking.*'))
    @php($isToolsMaterialsMenuActive = request()->routeIs('tools-materials.*') || request()->routeIs('tools-material-assignments.*') || request()->routeIs('preorders.*'))
    @php($isDeviceManagementActive = request()->routeIs('device-management.*'))
    @php($isSalariesMenuActive = request()->routeIs('employee-salaries.*') || request()->routeIs('labour-salaries.*'))
    @php($isSettingsMenuActive = request()->routeIs('main_categories.*') || request()->routeIs('categories.*') || request()->routeIs('units.*') || request()->routeIs('payment-stages.*') || request()->routeIs('payment-methods.*'))

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

                        @if($currentUser && $currentUser->hasPermission('tools-materials-list'))
                            <li class="submenu {{ $isToolsMaterialsMenuActive ? 'active' : '' }}">
                                <a href="javascript:void(0);"
                                    class="{{ $isToolsMaterialsMenuActive ? 'subdrop active' : '' }}">
                                    <i class="ti ti-shopping-cart"></i><span>Tools & Materials</span><span
                                        class="menu-arrow"></span>
                                </a>
                                <ul style="{{ $isToolsMaterialsMenuActive ? 'display: block;' : 'display: none;' }}">
                                    <li><a class="{{ request()->routeIs('preorders.index') || request()->routeIs('preorders.show') || request()->routeIs('preorders.create') || request()->routeIs('preorders.edit') ? 'active' : '' }}"
                                            href="{{ route('preorders.index') }}">Preorders</a></li>
                                    <li><a class="{{ request()->routeIs('preorders.reports') ? 'active' : '' }}"
                                            href="{{ route('preorders.reports') }}">Preorder Reports</a></li>
                                    <li><a class="{{ request()->routeIs('tools-materials.*') ? 'active' : '' }}"
                                            href="{{ route('tools-materials.index') }}">Purchase List</a></li>
                                    <li><a class="{{ request()->routeIs('tools-material-assignments.*') ? 'active' : '' }}"
                                            href="{{ route('tools-material-assignments.index') }}">Assign / Transfer</a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('payments-list'))
                            <li><a href="{{ route('payments.index') }}"><i
                                        class="ti ti-report-money"></i><span>Payments</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('variations-list'))
                            <li><a href="{{ route('variations.index') }}"><i
                                        class="ti ti-git-branch"></i><span>Variations</span></a></li>
                        @endif

                        @if($currentUser && ($currentUser->hasPermission('employees-salary-list') || $currentUser->hasPermission('labour-salaries-list')))
                            <li class="submenu {{ $isSalariesMenuActive ? 'active' : '' }}">
                                <a href="javascript:void(0);" class="{{ $isSalariesMenuActive ? 'subdrop active' : '' }}">
                                    <i class="ti ti-cash me-2"></i><span>Salaries</span><span class="menu-arrow"></span>
                                </a>
                                <ul style="{{ $isSalariesMenuActive ? 'display: block;' : 'display: none;' }}">
                                    @if($currentUser->hasPermission('employees-salary-list'))
                                        <li><a class="{{ request()->routeIs('employee-salaries.*') ? 'active' : '' }}"
                                                href="{{ route('employee-salaries.index') }}">Employee Salaries</a></li>
                                    @endif
                                    @if($currentUser->hasPermission('labour-salaries-list'))
                                        <li><a class="{{ request()->routeIs('labour-salaries.*') ? 'active' : '' }}"
                                                href="{{ route('labour-salaries.index') }}">Labour Salaries</a></li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('employees-list'))
                            <li><a href="{{ route('manage-users') }}"><i class="ti ti-users"></i><span>Manage
                                        Users</span></a></li>
                            <li><a href="{{ route('device-management.index') }}"
                                    class="{{ $isDeviceManagementActive ? 'active' : '' }}"><i
                                        class="ti ti-device-mobile"></i><span>Device Management</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('attendance-list'))
                            <li><a href="{{ route('attendance.index') }}"><i
                                        class="ti ti-clock"></i><span>Attendance</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('employees-list'))
                            <li class="submenu {{ $isTrackingMenuActive ? 'active' : '' }}">
                                <a href="javascript:void(0);" class="{{ $isTrackingMenuActive ? 'subdrop active' : '' }}">
                                    <i class="ti ti-map-pin"></i><span>Employee Tracking</span><span
                                        class="menu-arrow"></span>
                                </a>
                                <ul style="{{ $isTrackingMenuActive ? 'display: block;' : 'display: none;' }}">
                                    <li><a class="{{ request()->routeIs('tracking.index') ? 'active' : '' }}"
                                            href="{{ route('tracking.index') }}">Timeline</a></li>
                                    <li><a class="{{ request()->routeIs('tracking.live-map') ? 'active' : '' }}"
                                            href="{{ route('tracking.live-map') }}">Live Location</a></li>
                                    <li><a class="{{ request()->routeIs('tracking.card-view') ? 'active' : '' }}"
                                            href="{{ route('tracking.card-view') }}">Card View</a></li>
                                    <li><a class="{{ request()->routeIs('tracking.debug-report') ? 'active' : '' }}"
                                            href="{{ route('tracking.debug-report') }}">Debug Report</a></li>
                                    <li><a class="{{ request()->routeIs('tracking.settings') ? 'active' : '' }}"
                                            href="{{ route('tracking.settings') }}">Settings</a></li>
                                </ul>
                            </li>
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

                        @if($currentUser && $currentUser->hasPermission('quotations-list'))
                            <li><a href="{{ route('quotations.list') }}"><i
                                        class="ti ti-file-dollar"></i><span>Quotations</span></a></li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('expenses-list'))
                            <li class="submenu {{ $isExpensesMenuActive ? 'active' : '' }}">
                                <a href="javascript:void(0);" class="{{ $isExpensesMenuActive ? 'subdrop active' : '' }}">
                                    <i class="ti ti-receipt-2"></i><span>Expenses</span><span class="menu-arrow"></span>
                                </a>
                                <ul style="{{ $isExpensesMenuActive ? 'display: block;' : 'display: none;' }}">
                                    <li><a class="{{ $isExpenseHistoryRoute ? 'active' : '' }}"
                                            href="{{ route('expenses.history') }}">Expenses History</a></li>
                                    <li><a class="{{ request()->routeIs('expenses.unpaid-history') ? 'active' : '' }}"
                                            href="{{ route('expenses.unpaid-history') }}">Unpaid History</a></li>
                                    <li><a class="{{ request()->routeIs('expenses.deleted-history') ? 'active' : '' }}"
                                            href="{{ route('expenses.deleted-history') }}">Deleted History</a></li>
                                    <li><a class="{{ request()->routeIs('expenseReports.index') ? 'active' : '' }}"
                                            href="{{ route('expenseReports.index') }}">Reports</a></li>
                                </ul>
                            </li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('expenses-list'))
                            <li class="submenu {{ $isLabourExpensesMenuActive ? 'active' : '' }}">
                                <a href="javascript:void(0);"
                                    class="{{ $isLabourExpensesMenuActive ? 'subdrop active' : '' }}">
                                    <i class="ti ti-user-cog"></i><span>Labour Expenses</span><span
                                        class="menu-arrow"></span>
                                </a>
                                <ul style="{{ $isLabourExpensesMenuActive ? 'display: block;' : 'display: none;' }}">
                                    <li><a class="{{ request()->routeIs('labour-expenses.history') ? 'active' : '' }}"
                                            href="{{ route('labour-expenses.history') }}">Expense History</a></li>
                                    <li><a class="{{ request()->routeIs('labour-expenses.weekly') ? 'active' : '' }}"
                                            href="{{ route('labour-expenses.weekly') }}">Weekly History</a></li>
                                    <li><a class="{{ request()->routeIs('labour-expenses.advance-history') ? 'active' : '' }}"
                                            href="{{ route('labour-expenses.advance-history') }}">Labour Wallet</a></li>
                                    <li><a class="{{ request()->routeIs('labour-expenses.deleted-history') ? 'active' : '' }}"
                                            href="{{ route('labour-expenses.deleted-history') }}">Deleted History</a></li>
                                </ul>
                            </li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('expenses-list'))
                            <li class="submenu {{ $isVendorExpensesMenuActive ? 'active' : '' }}">
                                <a href="javascript:void(0);"
                                    class="{{ $isVendorExpensesMenuActive ? 'subdrop active' : '' }}">
                                    <i class="ti ti-building-warehouse"></i><span>Vendor Expenses</span><span
                                        class="menu-arrow"></span>
                                </a>
                                <ul style="{{ $isVendorExpensesMenuActive ? 'display: block;' : 'display: none;' }}">
                                    <li><a class="{{ request()->routeIs('vendor-expenses.history') ? 'active' : '' }}"
                                            href="{{ route('vendor-expenses.history') }}">Expense History</a></li>
                                    <li><a class="{{ request()->routeIs('vendor-expenses.unpaid-history') ? 'active' : '' }}"
                                            href="{{ route('vendor-expenses.unpaid-history') }}">Unpaid History</a></li>
                                    <li><a class="{{ request()->routeIs('vendor-expenses.advance-history') ? 'active' : '' }}"
                                            href="{{ route('vendor-expenses.advance-history') }}">Advance Amount</a></li>
                                    <li><a class="{{ request()->routeIs('vendor-expenses.deleted-history') ? 'active' : '' }}"
                                            href="{{ route('vendor-expenses.deleted-history') }}">Deleted History</a></li>
                                </ul>
                            </li>
                        @endif

                        @if($currentUser && $currentUser->hasPermission('transfers-list'))
                            <li class="submenu {{ $isHistoryMenuActive ? 'active' : '' }}">
                                <a href="javascript:void(0);" class="{{ $isHistoryMenuActive ? 'subdrop active' : '' }}">
                                    <i class="ti ti-arrows-transfer-up-down"></i><span>History</span><span
                                        class="menu-arrow"></span>
                                </a>
                                <ul style="{{ $isHistoryMenuActive ? 'display: block;' : 'display: none;' }}">
                                    <li><a class="{{ request()->routeIs('transfers.*') ? 'active' : '' }}"
                                            href="{{ route('transfers.index') }}">Transfer History</a></li>
                                    <li><a class="{{ request()->routeIs('wallet.*') ? 'active' : '' }}"
                                            href="{{ route('wallet.index') }}">Wallet History</a></li>
                                    <li><a class="{{ request()->routeIs('vendor-expenses.history') ? 'active' : '' }}"
                                            href="{{ route('vendor-expenses.history') }}">Vendor History</a></li>
                                </ul>
                            </li>
                        @endif

                        @if($currentUser && ($currentUser->hasPermission('reports-list') || $currentUser->hasPermission('expense-reports-list')))
                            <li class="submenu {{ $isReportsRoute ? 'active' : '' }}">
                                <a href="javascript:void(0);" class="{{ $isReportsRoute ? 'subdrop active' : '' }}"><i
                                        class="ti ti-report-analytics"></i><span>Reports</span><span
                                        class="menu-arrow"></span></a>
                                <ul style="{{ $isReportsRoute ? 'display: block;' : 'display: none;' }}">
                                    <li><a class="{{ $isReportsRoute && $reportType === 'site' ? 'active' : '' }}"
                                            href="{{ route('reports.index', ['type' => 'site']) }}">Client Summary</a></li>
                                    <li><a class="{{ $isReportsRoute && $reportType === 'office' ? 'active' : '' }}"
                                            href="{{ route('reports.index', ['type' => 'office']) }}">Payment Summary</a>
                                    </li>
                                    <li><a class="{{ $isReportsRoute && $reportType === 'total' ? 'active' : '' }}"
                                            href="{{ route('reports.index', ['type' => 'total']) }}">Total Report</a></li>
                                </ul>
                            </li>
                        @endif

                        <li class="submenu {{ $isSettingsMenuActive ? 'active' : '' }}">
                            <a href="javascript:void(0);" class="{{ $isSettingsMenuActive ? 'subdrop active' : '' }}">
                                <i class="ti ti-settings"></i><span>Settings</span><span class="menu-arrow"></span>
                            </a>
                            <ul style="{{ $isSettingsMenuActive ? 'display: block;' : 'display: none;' }}">
                                @if($currentUser && $currentUser->hasPermission('main-categories-list'))
                                    <li><a class="{{ request()->routeIs('main_categories.*') ? 'active' : '' }}"
                                            href="{{ route('main_categories.index') }}">Main Categories</a></li>
                                @endif
                                @if($currentUser && $currentUser->hasPermission('categories-list'))
                                    <li><a class="{{ request()->routeIs('categories.*') ? 'active' : '' }}"
                                            href="{{ route('categories.index') }}">Categories</a></li>
                                @endif
                                @if($currentUser && $currentUser->hasPermission('units-list'))
                                    <li><a class="{{ request()->routeIs('units.*') ? 'active' : '' }}"
                                            href="{{ route('units.index') }}">Unit Master</a></li>
                                @endif
                                @if($currentUser && $currentUser->hasPermission('payment-stages-list'))
                                    <li><a class="{{ request()->routeIs('payment-stages.*') ? 'active' : '' }}"
                                            href="{{ route('payment-stages.index') }}">Payment Stages</a></li>
                                @endif
                                @if($currentUser && $currentUser->hasPermission('payment-methods-list'))
                                    <li><a class="{{ request()->routeIs('payment-methods.*') ? 'active' : '' }}"
                                            href="{{ route('payment-methods.index') }}">Payment Method Master</a></li>
                                @endif
                            </ul>
                        </li>

                        @if($currentUser && $currentUser->hasPermission('roles-list'))
                            <li class="sidebar-submenu">
                                <a href="{{ route('roles.index') }}">
                                    <i class="ti ti-user-shield"></i><span>Roles</span>
                                </a>
                            </li>
                        @endif

                        @if($currentUser)
                        @php($canLeaveRequests = $currentUser->hasPermission('leave-requests-list') || $currentUser->hasPermission('leave-requests-edit') || $currentUser->hasPermission('leave-requests-delete'))
                        @if($canLeaveRequests)
                            <li class="sidebar-submenu">
                                <a href="{{ route('leaveRequests.index') }}" class="sidebar-link">
                                    <i class="ti ti-message-star"></i>
                                    <span>Leave Requests</span>
                                </a>
                            </li>
                        @endif
                        @endif

                        @if($currentUser && $currentUser->hasPermission('permissions-list'))
                            <li>
                                <a href="{{ route('permissions.index') }}">
                                    <i class="ti ti-shield-check"></i><span>Permissions</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
