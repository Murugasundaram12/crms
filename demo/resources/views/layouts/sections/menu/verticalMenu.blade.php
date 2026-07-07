<style>
    a {
        text-decoration: none;
    }

    a:hover {
        text-decoration: none !important;
    }

    .dark {
        font-weight: 900;
    }

    .dark:hover {
        color: darkcyan;
        text-decoration: none !important;
    }

    /* CSS for changing color on hover */
</style>
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme"
    style=" background-color:rgb(227 252 255) !important; ">

    <!-- ! Hide app brand if navbar-full -->
    <div class="app-brand demo">
        <a href="{{ url('/dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="{{ asset('assets/img/icons/logo12.png') }}" class="img-fluid" alt="Layout container"
                    style="width: 205px;margin-left:5px;margin-top:-1px;">
            </span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-autod-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1" style="margin-top: 30px;">
        <!--- header -->
        <!-- <li class="menu-header small text-uppercase">
    <span class="menu-header-text"></span>
  </li> -->
        <!--- header -->


        {{-- main menu --}}
        <!--- dashboard -->
        <li class="menu-item {{ \Request::route()->getName() == 'dashboard' ? 'active open' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle "></i>
                <div class="dark">Dashboard</div>
            </a>
        </li>
        <!--- dashboard -->

        <!--- roles -->
        @canany(['expenses-history', 'expenses-unpaid history', 'expenses-deleted history'])
            <li
                class="menu-item {{ \Request::route()->getName() == 'expenses-history' || \Request::route()->getName() == 'unpaid-history' || \Request::route()->getName() == 'expenses-delete_record' || \Request::route()->getName() == 'expenses-report-history' ? 'active open' : '' }} ">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <img src="{{ asset('assets/img/icons/amountexpenses.png') }}" alt="slack" class="me-3"
                        height="20">
                    <div class="dark">Expenses</div>
                </a>
                <ul class="menu-sub">

                    @can('expenses-history')
                        <li class="menu-item {{ \Request::route()->getName() == 'expenses-history' ? 'active open' : '' }}">
                            <a href="{{ route('expenses-history',['tab' => 1]) }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/icons8-payment-history-30.png') }}" alt="slack"
                                    class="me-3" height="20"> --}}
                                <div class="dark">Expenses History</div>
                            </a>
                        </li>
                    @endcan
                    @can('expenses-unpaid history')
                        <li class="menu-item {{ \Request::route()->getName() == 'unpaid-history' ? 'active open' : '' }}">
                            <a href="{{ route('unpaid-history') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/icons8-payment-history-30.png') }}" alt="slack"
                                    class="me-3" height="20"> --}}
                                <div class="dark">Unpaid History</div>
                            </a>
                        </li>
                    @endcan
                    @can('expenses-deleted history')
                        <li
                            class="menu-item {{ \Request::route()->getName() == 'expenses-delete_record' ? 'active open' : '' }}">
                            <a href="{{ route('expenses-delete_record') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/capital.png') }}" alt="slack" class="me-3"
                                    height="20"> --}}
                                <div class="dark">Deleted History</div>
                            </a>
                        </li>
                    @endcan
                     @can('expenses-history')
                        <li class="menu-item {{ \Request::route()->getName() == 'expenses-report-history' ? 'active open' : '' }}">
                            <a href="{{ route('expenses-report-history') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/icons8-payment-history-30.png') }}" alt="slack"
                                    class="me-3" height="20"> --}}
                                <div class="dark">Reports</div>
                            </a>
                        </li>
                    @endcan
                </ul>

            </li>


        @endcanany
        @canany(['labour expenses-list', 'labour expenses-weekly history', 'labour expenses-labour advance amount',
            'labour expenses-delete history'])
            <li
                class="menu-item  {{ \Request::route()->getName() == 'labour-expenses-history' || \Request::route()->getName() == 'labour-expenses-advance' || \Request::route()->getName() == 'labour-expenses-delete_record' ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <img src="{{ asset('assets/img/icons/labour-cost.png') }}" alt="slack" class="me-3"
                        height="20">
                    <div class="dark">Labour Expenses</div>
                </a>

                <ul class="menu-sub">
                    @can('labour expenses-list')
                        <li
                            class="menu-item  {{ \Request::route()->getName() == 'labour-expenses-history' ? 'active open' : '' }}">
                            <a href="{{ route('labour-expenses-history') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/icons8-payment-history-30.png') }}" alt="slack"
                                    class="me-3" height="20"> --}}
                                <div class="dark">Expense History</div>
                            </a>
                        </li>
                    @endcan
                    @can('labour expenses-weekly history')
                        <li class="menu-item labour_expense_history">
                            <a href="#" class="menu-link">
                                {{-- <img src="{{ asset('assets/img/icons/weekly-labour.png') }}"
                                    alt="slack" class="me-3" height="20"> --}}
                                <div class="dark">Weekly History</div>
                            </a>
                        </li>
                    @endcan
                    @can('labour expenses-labour advance amount')
                        <li
                            class="menu-item {{ \Request::route()->getName() == 'labour-expenses-advance' ? 'active open' : '' }}">
                            <a href="{{ route('labour-expenses-advance') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/hand-money.jpg') }}" alt="slack" class="me-3"
                                    height="20"> --}}
                                <div class="dark">Advance Amount</div>
                            </a>
                        </li>
                    @endcan
                    @can('labour expenses-delete history')
                        <li
                            class="menu-item {{ \Request::route()->getName() == 'labour-expenses-delete_record' ? 'active open' : '' }}">
                            <a href="{{ route('labour-expenses-delete_record') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/payment.png') }}" alt="slack" class="me-3"
                                    height="20"> --}}
                                <div class="dark">Deleted History</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany
        @canany(['vendor expenses-list'])
            <li
                class="menu-item  {{ \Request::route()->getName() == 'vendor-expenses-index' || \Request::route()->getName() == 'vendor-expenses-unpaid-history' || \Request::route()->getName() == 'vendor-expenses-advance-history' || \Request::route()->getName() == 'vendor-expenses-delete_record' ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <img src="{{ asset('assets/img/icons/vendor-expense.png') }}" alt="slack" class="me-3"
                        height="20">
                    <div class="dark">Vendor Expenses</div>
                </a>

                <ul class="menu-sub">
                    @can('vendor expenses-list')
                        <li
                            class="menu-item  {{ \Request::route()->getName() == 'vendor-expenses-index' ? 'active open' : '' }}">
                            <a href="{{ route('vendor-expenses-index') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/seller.png') }}" alt="slack" class="me-3"
                                    height="20"> --}}
                                <div class="dark">Expense History</div>
                            </a>
                        </li>
                    @endcan
                    @can('vendor expenses-unpaid history')
                        <li
                            class="menu-item {{ \Request::route()->getName() == 'vendor-expenses-unpaid-history' ? 'active open' : '' }}">
                            <a href="{{ route('vendor-expenses-unpaid-history') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/vendor-1.png') }}" alt="slack" class="me-3"
                                    height="20"> --}}
                                <div class="dark">Unpaid History</div>
                            </a>
                        </li>
                    @endcan
                    @can('vendor expenses-vendor advance amount')
                        <li
                            class="menu-item {{ \Request::route()->getName() == 'vendor-expenses-advance-history' ? 'active open' : '' }}">
                            <a href="{{ route('vendor-expenses-advance-history') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/vendor-2.png') }}" alt="slack" class="me-3"
                                    height="20"> --}}
                                <div class="dark">Advance Amount</div>
                            </a>
                        </li>
                    @endcan
                    @can('vendor expenses-deleted history')
                        <li
                            class="menu-item {{ \Request::route()->getName() == 'vendor-expenses-delete_record' ? 'active open' : '' }}">
                            <a href="{{ route('vendor-expenses-delete_record') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/payment.png') }}" alt="slack" class="me-3"
                                    height="20"> --}}
                                <div class="dark">Deleted History</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcan
        <!--- expenses -->
        @canany(['role-list', 'user-list', 'labour role-list', 'labour-list', 'vendor-list', 'category-list',
            'payment-list', 'client-list', 'project-list', 'stage-list'])
            <li
                class="menu-item {{ \Request::route()->getName() == 'client-index' || \Request::route()->getName() == 'project-index' || \Request::route()->getName() == 'roles.index' || \Request::route()->getName() == 'user-index' || \Request::route()->getName() == 'labourrole-index' || \Request::route()->getName() == 'labour-index' || \Request::route()->getName() == 'vendor-index' || \Request::route()->getName() == 'category-index' || \Request::route()->getName() == 'payment-index' || \Request::route()->getName() == 'stage-index' ? 'active open' : '' }} ">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <img src="{{ asset('assets/img/icons/search.png') }}" alt="slack" class="me-3 dark"
                        height="20">
                    <div class="dark">Category Added</div>
                </a>
                <ul class="menu-sub">
                    @can('client-list')
                        <li class="menu-item {{ \Request::route()->getName() == 'client-index' ? 'active open' : '' }}">
                            <a href="{{ route('client-index') }}" class="menu-link">
                                {{-- <i class="menu-icon tf-icons bx bx-user-circle"></i> --}}
                                <div class="dark">Client Details</div>
                            </a>
                        </li>
                    @endcan
                    <!--- client -->
                    <!--- project -->
                    @can('project-list')
                        <li class="menu-item {{ \Request::route()->getName() == 'project-index' ? 'active open' : '' }}">
                            <a href="{{ route('project-index') }}" class="menu-link">
                                {{-- <i class="fa fa-folder-open-o" aria-hidden="true"
                                    style="margin-right: 9px;margin-left:1px;"></i> --}}
                                <div class="dark">Project Details</div>
                            </a>
                        </li>
                    @endcan
                    @can('role-list')
                        <li class="menu-item {{ \Request::route()->getName() == 'roles.index' ? 'active open' : '' }}">
                            <a href="{{ route('roles.index') }}" class="menu-link">
                                {{-- <i class="menu-icon tf-icons bx bx-user-circle"></i> --}}
                                <div class="dark">Roles</div>
                            </a>
                        </li>
                    @endcan
                    @can('user-list')
                        <li class="menu-item {{ \Request::route()->getName() == 'user-index' ? 'active open' : '' }}">
                            <a href="{{ route('user-index') }}" class="menu-link">
                                {{-- <i class="menu-icon tf-icons bx bx-user-circle"></i> --}}
                                <div class="dark">Members</div>
                            </a>
                        </li>
                    @endcan
                    @can('labour role-list')
                        <li class="menu-item {{ \Request::route()->getName() == 'labourrole-index' ? 'active open' : '' }}">
                            <a href="{{ route('labourrole-index') }}" class="menu-link">
                                {{-- <i class="fa fa-wrench" style="margin-right: 9px;margin-left:1px;"></i> --}}
                                <div class="dark">Labour Role</div>
                            </a>
                        </li>
                    @endcan
                    @can('labour-list')
                        <li class="menu-item {{ \Request::route()->getName() == 'labour-index' ? 'active open' : '' }}">
                            <a href="{{ route('labour-index') }}" class="menu-link">
                                {{-- <i class="fa fa-wrench" style="margin-right: 9px;margin-left:1px;"></i> --}}
                                <div class="dark">Labour</div>
                            </a>
                        </li>
                    @endcan
                    <!--- labour -->
                    <!--- vendor -->
                    @can('vendor-list')
                        <li class="menu-item {{ \Request::route()->getName() == 'vendor-index' ? 'active open' : '' }}">
                            <a href="{{ route('vendor-index') }}" class="menu-link">
                                {{-- <i class="fa fa-users" style="margin-right:9px;margin-left:1px;"></i> --}}
                                <div class="dark">Vendor</div>
                            </a>
                        </li>
                    @endcan
                    <!--- vendor -->
                    <!-- main category -->
                    @can('maincategory-list')
                     <li class="menu-item {{ \Request::route()->getName() == 'maincategory.index' ? 'active open' : '' }}">
                            <a href="{{ route('maincategory.index') }}" class="menu-link">
                                {{-- <i class="menu-icon tf-icons bx bxs-category" style="margin-left:1px;"></i> --}}
                                <div class="dark">Main Category</div>
                            </a>
                        </li>
                    @endcan
                    <!-- main category -->
                    <!--- category -->
                    @can('category-list')
                        <li class="menu-item {{ \Request::route()->getName() == 'category-index' ? 'active open' : '' }}">
                            <a href="{{ route('category-index') }}" class="menu-link">
                                {{-- <i class="menu-icon tf-icons bx bxs-category" style="margin-left:1px;"></i> --}}
                                <div class="dark">Category</div>
                            </a>
                        </li>
                    @endcan
                    <!--- category -->
                    <!--- payment -->
                    @can('payment-list')
                        <li class="menu-item {{ \Request::route()->getName() == 'payment-index' ? 'active open' : '' }}">
                            <a href="{{ route('payment-index') }}" class="menu-link">
                                {{-- <i class="fa fa-money" style="margin-right:8px; margin-left:1px;"></i> --}}
                                <div class="dark">Payment</div>
                            </a>
                        </li>
                    @endcan
                    <!--- payment -->
                    <!--- client -->

                    @can('stage-list')
                        <li class="menu-item {{ \Request::route()->getName() == 'stage-index' ? 'active open' : '' }}">
                            <a href="{{ route('stage-index') }}" class="menu-link">
                                {{-- <img src="{{ asset('assets/img/icons/project-management.png') }}" alt="slack"
                                    class="me-3" height="20"> --}}
                                <div class="dark">Stages</div>
                            </a>
                        </li>
                    @endcan
                </ul>

            </li>


        @endcanany
        <!--- project -->
        <!--- transfer -->
        @canany(['transfer-history', 'wallet-history'])
            <li class="menu-item ">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <img src="{{ asset('assets/img/icons/history.png') }}" alt="slack" class="me-3"
                        height="20">
                    <div class="dark">History</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ \Request::route()->getName() == 'transfer-history' ? 'active open' : '' }}">
                        <a href="{{ route('transfer-history') }}" class="menu-link">
                            {{-- <img src="{{ asset('assets/img/icons/transfer-history.png') }}" alt="slack"
                                class="me-3" height="20"> --}}
                            <!-- <i class="menu-icon tf-icons bi bi-currency-exchange"></i> -->
                            <div class="dark">Transfer History</div>
                        </a>
                    </li>
                    @can('wallet-history')
                        <li class="menu-item {{ \Request::route()->getName() == 'wallet-history' ? 'active open' : '' }}">
                            <a href="{{ route('wallet-history') }}" class="menu-link">
                                {{-- <img src="{{ asset('assets/img/icons/wallet.png') }}" alt="slack" class="me-3"
                                    height="20"> --}}
                                <!-- <i class="menu-icon tf-icons bi bi-currency-exchange"></i> -->
                                <div class="dark">Wallet History</div>
                            </a>
                        </li>
                    @endcan
                    @can('vendor-history')
                    <li class="menu-item {{ \Request::route()->getName() == 'vendor-history' ? 'active open' : '' }}">
                        <a href="{{ route('transfer.vendor.history') }}" class="menu-link">
                            {{-- <img src="{{ asset('assets/img/icons/wallet.png') }}" alt="slack" class="me-3"
                                height="20"> --}}
                            <!-- <i class="menu-icon tf-icons bi bi-currency-exchange"></i> -->
                            <div class="dark">Vendor History</div>
                        </a>
                    </li>
                @endcan
                </ul>
            </li>
        @endcan
        <!--- transfer -->
        <!--- expenses -->

        <!--- reports -->
        @canany(['client-summary', 'payment-summary'])
            <li class="menu-item ">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <img src="{{ asset('assets/img/icons/reports.png') }}" alt="slack" class="me-3"
                        height="20">
                    <div class="dark">Reports</div>
                </a>
                <ul class="menu-sub">
                    @can('client-summary')
                        <li class="menu-item {{ \Request::route()->getName() == 'client-summary' ? 'active open' : '' }}">
                            <a href="{{ route('client-summary') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/client.png') }}" alt="slack" class="me-3"
                                    height="20"> --}}
                                <div class="dark">Client Summary</div>
                            </a>
                        </li>
                    @endcan
                    @can('payment-summary')
                        <li class="menu-item {{ \Request::route()->getName() == 'payment-summary' ? 'active open' : '' }}">
                            <a href="{{ route('payment-summary') }}" class="menu-link">
                                {{-- <img
                                    src="{{ asset('assets/img/icons/payment.png') }}" alt="slack" class="me-3"
                                    height="20"> --}}
                                <div class="dark">Payment Summary</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>

        @endcanany
        <!--- expenses -->
    </ul>

</aside>
<div class="modal fade" id="labour_total_popup" data-bs-backdrop="static" data-bs-keyboard="false"
    aria-hidden="true" aria-labelledby="exampleModalToggleLabel" tabindex="-1">
    <div class="modal-dialog modal-lg ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalToggleLabel">Weekly Salary details</h5>
                <button type="button" class="btn-close"  data-bs-dismiss="modal" aria-label="Close"  onclick="window.location.reload();"></button>
            </div>
            <div class="modal-body">
                <div class="labour_loadingsalary"></div>
            </div>

        </div>
    </div>
</div>


<script>
    $('.labour_expense_history').click(function() {
        $('.preloader').css('display', 'block');
        $.ajax({
            type: "get",
            url: "{{ route('labour-expenses-index') }}",
            dataType: 'json',
            success: function(html) {
                console.log(html);

                $('.labour_loadingsalary').html(html);
                $('.preloader').css('display', 'none');
                $('#labour_total_popup').modal('show');
            }
        });
    });
</script>
