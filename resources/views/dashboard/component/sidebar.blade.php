<div class="leftside-menu">

    <!-- Brand Logo Light -->
    <a href="{{route('admin.home')}}" class="logo logo-light">
                <span class="logo-lg">
                    <img src="/logo.png" alt="logo">
                </span>
        <span class="logo-sm">
                    <img src="/favicon.png" alt="small logo">
                </span>
    </a>

    <!-- Brand Logo Dark -->
    <a href="{{route('admin.home')}}" class="logo logo-dark">
                <span class="logo-lg">
                    <img src="/assets/front-end/assets/images/logo-dark.png" alt="dark logo">
                </span>
        <span class="logo-sm">
                    <img src="/assets/front-end/assets/images/favicon.png" alt="small logo">
                </span>
    </a>

    <!-- Sidebar Hover Menu Toggle Button -->
    <div class="button-sm-hover" data-bs-toggle="tooltip" data-bs-placement="right" title="Show Full Sidebar">
        <i class="ri-checkbox-blank-circle-line align-middle"></i>
    </div>

    <!-- Full Sidebar Menu Close Button -->
    <div class="button-close-fullsidebar">
        <i class="ri-close-fill align-middle"></i>
    </div>

    <!-- Sidebar -->
    <!-- Sidebar -->
    <div class="h-100" id="leftside-menu-container" data-simplebar>
        <!--- Sidemenu -->
        <ul class="side-nav">

            <li class="side-nav-title">Navigation</li>

            <!-- Dashboard - Always visible or check view dashboard permission -->
            <li class="side-nav-item">
                <a href="{{ route('admin.home') }}" class="side-nav-link {{ request()->routeIs('admin.home') ? 'sidenav-active' : '' }}">
                    <i class="uil-home-alt"></i>
                    <span> Dashboard </span>
                </a>
            </li>

            <!-- User Management Section -->
            @if(Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) ||
                Auth::guard('admin')->user()->hasAnyPermission(['view all users', 'view active users', 'view suspended users', 'manage kyc', 'manage user roles', 'manage user permissions', 'view user activity logs'])))
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarUsers" aria-expanded="false" aria-controls="sidebarUsers" class="side-nav-link">
                        <i class="uil-users-alt"></i>
                        <span> User Management</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebarUsers">
                        <ul class="side-nav-second-level pt-1">
                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view all users'))
                                <li>
                                    <a href="{{ route('admin.management') }}" class="side-nav-link {{ request()->routeIs('admin.management') ? 'sidenav-active' : '' }}">All Users</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view active users'))
                                <li>
                                    <a href="{{ route('active.users') }}" class="side-nav-link {{ request()->routeIs('active.users') ? 'sidenav-active' : '' }}">Active Users</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view suspended users'))
                                <li>
                                    <a href="{{ route('suspended.users') }}" class="side-nav-link {{ request()->routeIs('suspended.users') ? 'sidenav-active' : '' }}">Suspended Users</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('manage user roles'))
                                <li>
                                    <a href="{{ route('users.role') }}" class="side-nav-link {{ request()->routeIs('users.role') ? 'sidenav-active' : '' }}">User Roles</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('manage user permissions'))
                                <li>
                                    <a href="{{ route('users.permission') }}" class="side-nav-link {{ request()->routeIs('users.permission') ? 'sidenav-active' : '' }}">User Permissions</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view user activity logs'))
                                <li>
                                    <a href="{{ route('users.activity') }}" class="side-nav-link {{ request()->routeIs('users.activity') ? 'sidenav-active' : '' }}">User Activity Logs</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </li>
            @endif

            <!-- KYC Management Section -->
            @if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']))
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarKYC" aria-expanded="false" aria-controls="sidebarKYC" class="side-nav-link">
                        <i class="uil-user-check"></i>
                        <span>KYC Management</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebarKYC">
                        <ul class="side-nav-second-level pt-1">
                            @if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']))
                                <li>
                                    <a href="{{ route('users.kyc') }}" class="side-nav-link {{ request()->routeIs('users.kyc') ? 'sidenav-active' : '' }}">
                                        <i class="uil-cog me-1"></i> Manage KYC
                                    </a>
                                </li>
                            @endif

                            <li>
                                <a href="{{route('users.tier_1')}}" class="side-nav-link {{ request()->routeIs('users.tier_1') ? 'sidenav-active' : '' }}">
                                    <i class="uil-check-circle me-1"></i> Tier 1
                                </a>
                            </li>
                            <li>
                                <a href="{{route('users.tier_2')}}" class="side-nav-link {{ request()->routeIs('users.tier_2') ? 'sidenav-active' : '' }}">
                                    <i class="uil-check-circle me-1"></i> Tier 2
                                </a>
                            </li>
                            <li>
                                <a href="{{route('users.tier_3')}}" class="side-nav-link {{ request()->routeIs('users.tier_3') ? 'sidenav-active' : '' }}">
                                    <i class="uil-check-circle me-1"></i> Tier 3
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endif

            <!-- Transaction Management Section -->
            @if(Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) ||
                Auth::guard('admin')->user()->hasAnyPermission(['view user transactions', 'view all transactions', 'view pending transactions', 'view failed transactions', 'view successful transactions', 'view transaction reports'])))
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarTransactions" aria-expanded="false" aria-controls="sidebarTransactions" class="side-nav-link">
                        <i class="uil-exchange-alt"></i>
                        <span> Transaction Management</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebarTransactions">
                        <ul class="side-nav-second-level pt-1">
                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view user transactions'))
                                <li>
                                    <a href="{{route('wallet-report')}}" class="side-nav-link {{ request()->routeIs('wallet-report') ? 'sidenav-active' : '' }}">User Transactions</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view all transactions'))
                                <li>
                                    <a href="{{ route('all.transactions') }}" class="side-nav-link {{ request()->routeIs('all.transactions') ? 'sidenav-active' : '' }}">All Transactions</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view pending transactions'))
                                <li>
                                    <a href="{{ route('pending.transactions') }}" class="side-nav-link {{ request()->routeIs('pending.transactions') ? 'sidenav-active' : '' }}">Pending Transactions</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view failed transactions'))
                                <li>
                                    <a href="{{ route('failed.transactions') }}" class="side-nav-link {{ request()->routeIs('failed.transactions') ? 'sidenav-active' : '' }}">Failed Transactions</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view successful transactions'))
                                <li>
                                    <a href="{{ route('successful.transactions') }}" class="side-nav-link {{ request()->routeIs('successful.transactions') ? 'sidenav-active' : '' }}">Successful Transfer</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </li>
            @endif

            <!-- Wallet Management Section -->
            @if(Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) ||
                Auth::guard('admin')->user()->hasAnyPermission(['view wallet overview', 'view wallet funding'])))
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarWallet" aria-expanded="false" aria-controls="sidebarWallet" class="side-nav-link">
                        <i class="uil-wallet"></i>
                        <span> Wallet Management</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebarWallet">
                        <ul class="side-nav-second-level pt-1">
                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view wallet overview'))
                                <li>
                                    <a href="{{route('wallet-report')}}" class="side-nav-link {{ request()->routeIs('wallet-report') ? 'sidenav-active' : '' }}">Wallet Overview</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view wallet funding'))
                                <li>
                                    <a href="{{route('wallet-funding')}}" class="side-nav-link {{ request()->routeIs('wallet-funding') ? 'sidenav-active' : '' }}">Wallet Funding</a>
                                </li>
                            @endif


                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view wallet funding'))
                                <li>
                                    <a href="{{route('configure-payment')}}" class="side-nav-link {{ request()->routeIs('configure-payment') ? 'sidenav-active' : '' }}">Gateway Configuration</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </li>
            @endif

            <!-- Airtime to Cash Records Section -->
            @if(Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) ||
                Auth::guard('admin')->user()->hasAnyPermission(['view fraudulent transaction reports'])))
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarAirtimeRecords" aria-expanded="false" aria-controls="sidebarAirtimeRecords" class="side-nav-link">
                        <i class="uil-file-check"></i>
                        <span>Airtime to Cash</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebarAirtimeRecords">
                        <ul class="side-nav-second-level pt-1">
                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view fraudulent transaction reports'))
                                <li>
                                    <a href="{{ route('airtime_to_cash') }}" class="side-nav-link {{ request()->routeIs('airtime_to_cash') ? 'sidenav-active' : '' }}">
                                        <i class="ri-exchange-dollar-line"></i>
                                        <span>Airtime to Cash</span>
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('airtime_to_cash_records') }}" class="side-nav-link {{ request()->routeIs('airtime_to_cash_records') ? 'sidenav-active' : '' }}">
                                        <i class="ri-file-list-2-line"></i>
                                        <span>All Records</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </li>
            @endif

            <!-- Security & Transaction Monitoring Section -->
            @if(Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view fraudulent transaction reports')))
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarSecurity" aria-expanded="false" aria-controls="sidebarSecurity" class="side-nav-link">
                        <i class="uil-shield-check"></i>
                        <span>Security & Monitoring</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebarSecurity">
                        <ul class="side-nav-second-level pt-1">
                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view fraudulent transaction reports'))
                                <li>
                                    <a href="{{ route('wallet-transactions.fraud.checks') }}" class="side-nav-link {{ request()->routeIs('wallet-transactions.fraud.checks') ? 'sidenav-active' : '' }}">
                                        Fraudulent Reports
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </li>
            @endif

            <!-- System Configuration Section -->
            @if(Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view user transactions')))
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarSystemConfig" aria-expanded="false" aria-controls="sidebarSystemConfig" class="side-nav-link">
                        <i class="uil-cog"></i>
                        <span>System Configuration</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebarSystemConfig">
                        <ul class="side-nav-second-level pt-1">

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view wallet funding'))
                                <li>
                                    <a href="{{route('transaction.fee')}}" class="side-nav-link {{ request()->routeIs('transaction.fee') ? 'sidenav-active' : '' }}">Transaction Fee</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view user transactions'))
                                <li>
                                    <a href="{{route('dollar_rate')}}" class="side-nav-link {{ request()->routeIs('dollar_rate') ? 'sidenav-active' : '' }}">Set Dollar Rate</a>
                                </li>
                            @endif

                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view user transactions'))
                                <li>
                                    <a href="{{route('tier_settings')}}" class="side-nav-link {{ request()->routeIs('tier_settings') ? 'sidenav-active' : '' }}">Tier Settings</a>
                                </li>


                                    <li>
                                        <a href="{{route('set_preferred_provider')}}" class="side-nav-link {{ request()->routeIs('set_preferred_provider') ? 'sidenav-active' : '' }}">Payment Gateway</a>
                                    </li>



                            @endif
                        </ul>
                    </div>
                </li>
            @endif

            <!-- Support & Communication Section -->
            @if(Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) ||
                Auth::guard('admin')->user()->hasAnyPermission(['send announcements', 'view all announcements'])))
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#sidebarSupport" aria-expanded="false" aria-controls="sidebarSupport" class="side-nav-link">
                        <i class="uil-headphones"></i>
                        <span> Support & Communication</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="sidebarSupport">
                        <ul class="side-nav-second-level pt-1">
                            @if(Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('send announcements'))
                                <li>
                                    <a href="{{ route('broadcast-message') }}" class="side-nav-link {{ request()->routeIs('broadcast-message') ? 'sidenav-active' : '' }}">
                                        Announcements
                                        <span class="badge bg-warning"></span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </li>
            @endif


            <!-- Banners -->
            @if(Auth::guard('admin')->check() && (
                    Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) ||
                    Auth::guard('admin')->user()->hasPermission('send announcements')
                ))
                <li class="side-nav-item">
                    <a href="{{ route('banners.index') }}" class="side-nav-link {{ request()->routeIs('banners.index') ? 'sidenav-active' : '' }}">
                        <i class="ri-image-line"></i>
                        <span> Banners </span>
                    </a>
                </li>
            @endif



            <!-- Notifications -->
            @if(Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('view notifications')))
                <li class="side-nav-item">
                    <a href="{{ route('user.notification') }}" class="side-nav-link {{ request()->routeIs('user.notification') ? 'sidenav-active' : '' }}">
                        <i class="ri-notification-line"></i>
                        <span> Notifications </span>
                    </a>
                </li>
            @endif

            <!-- Settings -->
            @if(Auth::guard('admin')->check() && (Auth::guard('admin')->user()->hasRole(['super-admin', 'developer']) || Auth::guard('admin')->user()->hasPermission('manage settings')))
                <li class="side-nav-item">
                    <a href="{{ route('user.settings') }}" class="side-nav-link {{ request()->routeIs('user.settings') ? 'sidenav-active' : '' }}">
                        <i class="ri-settings-3-line"></i>
                        <span> Settings </span>
                    </a>
                </li>
            @endif

        </ul>

        <div class="clearfix"></div>
    </div>
</div>
