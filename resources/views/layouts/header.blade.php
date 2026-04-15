@php
    $topNotifications = collect();
    $topUnreadCount = 0;
    try {
        $shopRows = collect();
        $doctorRows = collect();

        if (\Illuminate\Support\Facades\Schema::hasTable('shop_admin_notifications')) {
            $shopRows = \App\Models\Shop\ShopAdminNotification::query()
                ->latest()
                ->limit(20)
                ->get()
                ->map(function ($row) {
                    return (object) [
                        'title' => $row->title,
                        'message' => $row->message,
                        'is_read' => (bool) $row->is_read,
                        'created_at' => $row->created_at,
                        'source' => 'Shop',
                    ];
                });
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('doctor_admin_notifications')) {
            $doctorRows = \App\Models\Doctor\DoctorAdminNotification::query()
                ->latest()
                ->limit(20)
                ->get()
                ->map(function ($row) {
                    return (object) [
                        'title' => $row->title,
                        'message' => $row->message,
                        'is_read' => (bool) $row->is_read,
                        'created_at' => $row->created_at,
                        'source' => 'Appointment',
                    ];
                });
        }

        $topNotifications = $shopRows
            ->concat($doctorRows)
            ->sortByDesc(fn ($item) => optional($item->created_at)->timestamp ?? 0)
            ->take(12)
            ->values();

        $topUnreadCount = $topNotifications->where('is_read', false)->count();
    } catch (\Throwable $e) {
        $topNotifications = collect();
        $topUnreadCount = 0;
    }
@endphp
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
                        @if($topUnreadCount > 0)
                            <span class="alert-badge"></span>
                        @endif
                    </a>
                    <div class="dropdown-menu stop dropdown-menu-end dropdown-lg py-0">
                        <h5 class="dropdown-item-text m-0 py-3 d-flex justify-content-between align-items-center">
                            Notifications
                            <span class="badge text-bg-light">{{ $topNotifications->count() }}</span>
                        </h5>
                        <div style="max-height: 280px; overflow-y: auto;">
                            @forelse($topNotifications as $n)
                                <div class="dropdown-item py-2 border-top">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <small class="fw-semibold text-dark">{{ $n->title }}</small>
                                        <small class="text-muted">{{ optional($n->created_at)->diffForHumans() }}</small>
                                    </div>
                                    <div class="small text-muted">{{ $n->message }}</div>
                                    <div class="mt-1">
                                        <span class="badge text-bg-secondary">{{ $n->source }}</span>
                                        @if(!$n->is_read)
                                            <span class="badge text-bg-warning">New</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="dropdown-item py-3 text-center text-muted">No notifications yet</div>
                            @endforelse
                        </div>
                    </div>
                </li>
    
                <li class="dropdown topbar-item">
                    <a id="profile-menu-toggle" class="nav-link dropdown-toggle arrow-none nav-icon" data-bs-toggle="dropdown" href="#" role="button"
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
