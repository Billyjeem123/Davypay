<!-- ========== Topbar Start ========== -->
<div class="navbar-custom">
    <div class="topbar container-fluid">
        <div class="d-flex align-items-center gap-lg-2 gap-1">

            <!-- Topbar Brand Logo -->
            <div class="logo-topbar">
                <!-- Logo light -->
                <a href="{{route('admin.home')}}" class="logo-light">
                            <span class="logo-lg">
                                <img src="/logo.png" alt="logo">
                            </span>
                    <span class="logo-sm">
                                <img src="/favicon.png" alt="small logo">
                            </span>
                </a>

                <!-- Logo Dark -->
                <a href="{{route('admin.home')}}" class="logo-dark">
                            <span class="logo-lg">
                                <img src="/logo-dark.png" alt="dark logo">
                            </span>
                    <span class="logo-sm">
                                <img src="/favicon.png" alt="small logo">
                            </span>
                </a>
            </div>

            <!-- Sidebar Menu Toggle Button -->
            <button class="button-toggle-menu">
                <i class="ri-menu-5-line"></i>
            </button>

            <!-- Horizontal Menu Toggle Button -->
            <button class="navbar-toggle" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <div class="lines">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </button>

            <!-- Topbar Search Form -->
            <div class="app-search d-none d-lg-block">
                <form>
                    <div class="input-group">
                        <input type="search" class="form-control dropdown-toggle" placeholder="Search..."
                               id="top-search">
                        <span class="ri-search-line search-icon"></span>
                        <button class="input-group-text btn btn-primary" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <ul class="topbar-menu d-flex align-items-center gap-3">
            <li class=" d-lg-none">
                <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button"
                   aria-haspopup="false" aria-expanded="false">
                    <i class="ri-search-line font-22"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-animated dropdown-lg p-0">
                    <form class="p-3">
                        <input type="search" class="form-control" placeholder="Search ...">
                    </form>
                </div>
            </li>
            <li class="dropdown">
                <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button"
                   aria-haspopup="false" aria-expanded="false">
                    <i class="ri-question-answer-fill font-22"></i>
                    &nbsp;
                    <span> Feedback? </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated dropdown-lg py-0">
                    <div class="p-2 border-top-0 border-start-0 border-end-0 border-dashed border">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0 font-16 fw-semibold"> Give Feedback</h6>
                            </div>
                        </div>
                    </div>
                    <div class="px-2" style="max-height: 300px;" data-simplebar>
                        <form action="" class="mt-2">
                            <div class="mb-2">
                                        <textarea class="form-control" id="feedback" rows="5"
                                                  placeholder="Lorem lipsum....."></textarea>
                            </div>
                            <div class="mb-3">
                                <button class="btn btn-primary w-100">Submit</button>
                            </div>
                        </form>
                    </div>
            </li>


            <li class="d-none d-sm-inline-block position-relative">
                <a href="{{ route('user.notification') }}" class="nav-link position-relative">
                    <i class="ri-notification-fill font-22"></i>

                    @if(auth('admin')->check() && auth('admin')->user()->unreadNotifications->count() > 0)
                        <span class="badge bg-danger rounded-pill position-absolute"
                              style="top: 2px; right: 2px; font-size: 10px; padding: 4px 6px;">
                {{ auth('admin')->user()->unreadNotifications->count() }}
            </span>
                    @endif
                </a>
            </li>



            <li class="dropdown">
                <a class="nav-link dropdown-toggle arrow-none nav-user px-2" data-bs-toggle="dropdown" href="#"
                   role="button" aria-haspopup="false" aria-expanded="false">
                    <div class="avatar-xs">
                                <span class="avatar-title bg-black rounded-circle">
                                    <i class="ri-user-fill "></i>
                                </span>
                    </div>
                    <span class="d-lg-flex flex-column gap-1 d-none">
                             <h5 class="my-0">
                          {{ Auth::guard('admin')->user()?->first_name }} {{ Auth::guard('admin')->user()?->last_name }}
                             </h5>


                            </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown">
                    <!-- item-->
                    <div class=" dropdown-header noti-title">
                        <h6 class="text-overflow m-0">Welcome !</h6>
                    </div>


                    <!-- item-->
                    <a href="{{route('user.settings')}}" class="dropdown-item">
                        <i class="ri-user-settings-line font-16 me-1"></i>
                        <span>Settings</span>
                    </a>

                    <!-- item-->
                    <a href="{{route('admin.logout')}}" class="dropdown-item">
                        <i class="ri-login-circle-line font-16 me-1"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </li>
        </ul>
    </div>
</div>
<!-- ========== Topbar End ========== -->
