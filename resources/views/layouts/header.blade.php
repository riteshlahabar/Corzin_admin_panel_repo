<div class="topbar d-print-none">
    <div class="container-fluid">
        <nav class="topbar-custom d-flex justify-content-between" id="topbar-custom"> 
            
            <ul class="topbar-item list-unstyled d-inline-flex align-items-center mb-0">                        
                <li>
                    <button class="nav-link mobile-menu-btn nav-icon" id="togglemenu">
                        <i class="iconoir-menu"></i>
                    </button>
                </li> 
                <li class="mx-2 welcome-text">
                    
                    <h5 class="mb-0 fw-semibold text-truncate">Dairy Management System</h5>
                    </li>                   
            </ul>
            <ul class="topbar-item list-unstyled d-inline-flex align-items-center mb-0">
                <li class="hide-phone app-search">
                    <form role="search" action="#" method="get">
                        <input type="search" name="search" class="form-control top-search mb-0" placeholder="Search here...">
                        <button type="submit"><i class="iconoir-search"></i></button>
                    </form>
                </li>     
               <li class="topbar-item">
                    <a class="nav-link nav-icon" href="javascript:void(0);" id="light-dark-mode">
                        <i class="iconoir-half-moon dark-mode"></i>
                        <i class="iconoir-sun-light light-mode"></i>
                    </a>                    
                </li>
    
                <li class="dropdown topbar-item">
                    <a class="nav-link dropdown-toggle arrow-none nav-icon" data-bs-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" aria-expanded="false" data-bs-offset="0,19">
                        <i class="iconoir-bell"></i>
                        <span class="alert-badge"></span>
                    </a>
                    <div class="dropdown-menu stop dropdown-menu-end dropdown-lg py-0">
                        <h5 class="dropdown-item-text m-0 py-3 d-flex justify-content-between align-items-center">
                            Notifications <a href="#" class="badge text-body-tertiary badge-pill">
                                <i class="iconoir-plus-circle fs-4"></i>
                            </a>
                        </h5>
                        <a href="pages-notifications.html" class="dropdown-item text-center text-dark fs-13 py-2">
                            View All <i class="fi-arrow-right"></i>
                        </a>
                    </div>
                </li>
    
                <li class="dropdown topbar-item">
                    <a class="nav-link dropdown-toggle arrow-none nav-icon" data-bs-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" aria-expanded="false" data-bs-offset="0,19">
                        <img src="{{ asset('assets/images/users/avatar-1.jpg') }}" alt="" class="thumb-md rounded-circle">
                    </a>
                    <div class="dropdown-menu dropdown-menu-end py-0">
                        <div class="d-flex align-items-center dropdown-item py-2 bg-secondary-subtle">
                            <div class="flex-shrink-0">
                                <img src="{{ asset('assets/images/users/avatar-1.jpg') }}" alt="" class="thumb-md rounded-circle">
                            </div>
                            <div class="flex-grow-1 ms-2 text-truncate align-self-center">
                                <h6 class="my-0 fw-medium text-dark fs-13">Corzin Group</h6>
                                <small class="text-muted mb-0">Dairy Management System</small>
                            </div>
                        </div>
                        <div class="dropdown-divider mt-0"></div>
                        <small class="text-muted px-2 pb-1 d-block">Account</small>
                        <a class="dropdown-item" href="pages-profile.html"><i class="las la-user fs-18 me-1 align-text-bottom"></i> Profile</a>
                        <a class="dropdown-item" href="pages-faq.html"><i class="las la-wallet fs-18 me-1 align-text-bottom"></i> Earning</a>
                        <small class="text-muted px-2 py-1 d-block">Settings</small>                        
                        <a class="dropdown-item" href="pages-profile.html"><i class="las la-cog fs-18 me-1 align-text-bottom"></i>Account Settings</a>
                        <a class="dropdown-item" href="pages-profile.html"><i class="las la-lock fs-18 me-1 align-text-bottom"></i> Security</a>
                        <a class="dropdown-item" href="pages-faq.html"><i class="las la-question-circle fs-18 me-1 align-text-bottom"></i> Help Center</a>                        
                        <div class="dropdown-divider mb-0"></div>
                        <a class="dropdown-item text-danger" href="auth-login.html"><i class="las la-power-off fs-18 me-1 align-text-bottom"></i> Logout</a>
                    </div>
                </li>
            </ul></nav>
        </div>
</div>