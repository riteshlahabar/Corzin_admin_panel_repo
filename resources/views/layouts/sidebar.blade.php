@php
    /** @var \App\Models\User|null $currentUser */
    $currentUser = auth()->user();
@endphp

<div class="startbar d-print-none">
   <div class="brand d-flex align-items-center justify-content-center" style="height:70px;">
    <a href="index.html" class="logo d-flex align-items-center">
        <img src="{{ asset('assets/images/logo-sm.png') }}" class="logo-sm" style="height:28px; display:none;">
        <img src="{{ asset('assets/images/logo-dark.png') }}" class="logo-lg logo-dark" style="height:40px; max-width:140px; object-fit:contain;">
    </a>
</div>

<div class="sidebar-divider" style="margin-top:4px;"></div>

    <div class="startbar-menu" >
        <div class="startbar-collapse" id="startbarCollapse" data-simplebar>
            <div class="d-flex align-items-start flex-column w-100">
              <ul class="navbar-nav mb-auto w-100">

    @if($currentUser?->hasPermission('dashboard.view'))
    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="iconoir-report-columns menu-icon me-2"></i>
            <span>Dashboard</span>
        </a>
    </li>
    @endif

    @if($currentUser?->hasAnyPermission([
        'farmer_list.view','animal_list.view','pan_list.view','milk_production.view','feeding.view',
        'diet_plan.view','pregnancy.view','dairy.view','farmer_settings.view','farmer_referred.view','farmer_plan.view',
        'farmer_subscription.view'
    ]))
    <li class="nav-item">
        <a class="nav-link" href="#farmerMenu" data-bs-toggle="collapse" role="button">
            <i class="iconoir-user menu-icon me-2"></i>
            <span>Farmer Data</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse" id="farmerMenu">
            <ul class="nav flex-column ms-4">
                @if($currentUser?->hasPermission('farmer_list.view'))
                <li class="menu-item"><a href="{{ route('farmer.list') }}" class="nav-link"><i class="iconoir-group me-2"></i> Farmer List</a></li>
                @endif
                @if($currentUser?->hasPermission('animal_list.view'))
                <li class="menu-item"><a href="{{ route('farmer.animals') }}" class="nav-link"><i class="fas fa-cow me-2"></i> Animal List</a></li>
                @endif
                @if($currentUser?->hasPermission('pan_list.view'))
                <li class="menu-item"><a href="{{ route('farmer.pans') }}" class="nav-link"><i class="iconoir-list me-2"></i> PAN List</a></li>
                @endif
                @if($currentUser?->hasPermission('milk_production.view'))
                <li class="menu-item"><a href="{{ route('farmer.milk') }}" class="nav-link"><i class="iconoir-droplet me-2"></i> Milk Production</a></li>
                @endif
                @if($currentUser?->hasPermission('feeding.view'))
                <li class="menu-item"><a href="{{ route('farmer.feeding') }}" class="nav-link"><i class="iconoir-leaf me-2"></i> Feeding</a></li>
                @endif
                @if($currentUser?->hasPermission('diet_plan.view'))
                <li class="menu-item"><a href="{{ route('farmer.diet-plan') }}" class="nav-link"><i class="iconoir-bowl-food me-2"></i> Diet Plan</a></li>
                @endif
                @if($currentUser?->hasPermission('pregnancy.view'))
                <li class="menu-item"><a href="{{ route('farmer.pregnancy') }}" class="nav-link"><i class="iconoir-healthcare me-2"></i> Pregnancy</a></li>
                @endif
                @if($currentUser?->hasPermission('dairy.view'))
                <li class="menu-item"><a href="{{ route('farmer.dairy') }}" class="nav-link"><i class="iconoir-building me-2"></i> Dairy</a></li>
                @endif
                @if($currentUser?->hasPermission('farmer_settings.view'))
                <li class="menu-item"><a href="{{ route('farmer.settings') }}" class="nav-link {{ request()->routeIs('farmer.settings*') ? 'active' : '' }}"><i class="iconoir-settings me-2"></i> Settings</a></li>
                @endif
                @if($currentUser?->hasPermission('farmer_referred.view'))
                <li class="menu-item"><a href="{{ route('farmer.referred') }}" class="nav-link"><i class="iconoir-gift me-2"></i> Refer &amp; Earn</a></li>
                @endif
                @if($currentUser?->hasPermission('farmer_plan.view'))
                <li class="menu-item"><a href="{{ route('farmer.plan.index') }}" class="nav-link"><i class="iconoir-coins me-2"></i> Farmer Plan</a></li>
                @endif
                @if($currentUser?->hasPermission('farmer_subscription.view'))
                <li class="menu-item"><a href="{{ route('farmer.subscription.index') }}" class="nav-link"><i class="iconoir-wallet me-2"></i> Farmer Subscription</a></li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    @if($currentUser?->hasAnyPermission([
        'animal_lifecycle_active.view','animal_lifecycle_sold.view','animal_lifecycle_death.view','animal_lifecycle_pan_transfer.view'
    ]))
    <li class="nav-item">
        <a class="nav-link" href="#lifecycleMenu" data-bs-toggle="collapse" role="button">
            <i class="iconoir-refresh-circle menu-icon me-2"></i>
            <span>Animal Lifecycle</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse" id="lifecycleMenu">
            <ul class="nav flex-column ms-4">
                @if($currentUser?->hasPermission('animal_lifecycle_active.view'))
                <li><a href="{{ route('animal.lifecycle.active') }}" class="nav-link"><i class="iconoir-check me-2"></i> Active</a></li>
                @endif
                @if($currentUser?->hasPermission('animal_lifecycle_sold.view'))
                <li><a href="{{ route('animal.lifecycle.sold') }}" class="nav-link"><i class="iconoir-shopping-bag-arrow-down me-2"></i> Sold</a></li>
                @endif
                @if($currentUser?->hasPermission('animal_lifecycle_death.view'))
                <li><a href="{{ route('animal.lifecycle.death') }}" class="nav-link"><i class="iconoir-xmark me-2"></i> Death</a></li>
                @endif
                @if($currentUser?->hasPermission('animal_lifecycle_pan_transfer.view'))
                <li><a href="{{ route('animal.lifecycle.pan_transfer') }}" class="nav-link"><i class="iconoir-repeat me-2"></i> Pan Transfer</a></li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    @if($currentUser?->hasAnyPermission(['health_dmi.view','health_mastitis.view','health_vaccination.view']))
    <li class="nav-item">
        <a class="nav-link" href="#healthMenu" data-bs-toggle="collapse" role="button">
            <i class="iconoir-health-shield menu-icon me-2"></i>
            <span>Health</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse" id="healthMenu">
            <ul class="nav flex-column ms-4">
                @if($currentUser?->hasPermission('health_dmi.view'))
                <li><a href="{{ route('health.dmi') }}" class="nav-link"><i class="iconoir-calculator me-2"></i> DMI Calculator</a></li>
                @endif
                @if($currentUser?->hasPermission('health_mastitis.view'))
                <li><a href="{{ route('health.mastitis') }}" class="nav-link"><i class="fa-solid fa-briefcase-medical me-2"></i> Mastitis</a></li>
                @endif
                @if($currentUser?->hasPermission('health_vaccination.view'))
                <li><a href="{{ route('health.vaccination') }}" class="nav-link"><i class="fa-solid fa-syringe me-2"></i> Vaccination</a></li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    @if($currentUser?->hasAnyPermission([
        'doctor_registration.add','doctor_list.view','doctor_appointments.view','doctor_visited.view',
        'doctor_settings.view','doctor_ratings.view','doctor_referred.view','doctor_plan.view','doctor_subscription.view'
    ]))
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doctor.*') ? 'active' : '' }}" href="#doctorMenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('doctor.*') ? 'true' : 'false' }}">
            <i class="iconoir-user menu-icon me-2"></i>
            <span>Doctor</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse {{ request()->routeIs('doctor.*') ? 'show' : '' }}" id="doctorMenu">
            <ul class="nav flex-column ms-4">
                @if($currentUser?->hasPermission('doctor_registration.add'))
                <li class="menu-item">
                    <a href="{{ route('doctor.create') }}" class="nav-link {{ request()->routeIs('doctor.create') ? 'active' : '' }}">
                        <i class="iconoir-plus-circle me-2"></i> Register Doctor
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('doctor_list.view'))
                <li class="menu-item">
                    <a href="{{ route('doctor.index') }}" class="nav-link {{ request()->routeIs('doctor.index', 'doctor.show') ? 'active' : '' }}">
                        <i class="iconoir-page-search me-2"></i> Doctor List
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('doctor_appointments.view'))
                <li class="menu-item">
                    <a href="{{ route('doctor.appointments') }}" class="nav-link {{ request()->routeIs('doctor.appointments') ? 'active' : '' }}">
                        <i class="iconoir-calendar me-2"></i> Appointment
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('doctor_visited.view'))
                <li class="menu-item">
                    <a href="{{ route('doctor.visited') }}" class="nav-link {{ request()->routeIs('doctor.visited') ? 'active' : '' }}">
                        <i class="iconoir-check-circle me-2"></i> Visited
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('doctor_settings.view'))
                <li class="menu-item">
                    <a href="{{ route('doctor.settings') }}" class="nav-link {{ request()->routeIs('doctor.settings', 'doctor.settings.update') ? 'active' : '' }}">
                        <i class="iconoir-settings me-2"></i> Settings
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('doctor_ratings.view'))
                <li class="menu-item">
                    <a href="{{ route('doctor.ratings') }}" class="nav-link {{ request()->routeIs('doctor.ratings') ? 'active' : '' }}">
                        <i class="iconoir-star me-2"></i> Rating
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('doctor_referred.view'))
                <li class="menu-item">
                    <a href="{{ route('doctor.referred') }}" class="nav-link {{ request()->routeIs('doctor.referred') ? 'active' : '' }}">
                        <i class="iconoir-share-android me-2"></i> Refer & Earn
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('doctor_plan.view'))
                <li class="menu-item">
                    <a href="{{ route('doctor.plan.index') }}" class="nav-link {{ request()->routeIs('doctor.plan.*') ? 'active' : '' }}">
                        <i class="iconoir-coins me-2"></i> Doctor Plan
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('doctor_subscription.view'))
                <li class="menu-item">
                    <a href="{{ route('doctor.subscription.index') }}" class="nav-link {{ request()->routeIs('doctor.subscription.*') ? 'active' : '' }}">
                        <i class="iconoir-wallet me-2"></i> Doctor Subscription
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    @if($currentUser?->hasAnyPermission(['shop_products.view','shop_orders.view']))
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('shop.*') ? 'active' : '' }}" href="#shopMenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('shop.*') ? 'true' : 'false' }}">
            <i class="iconoir-cart menu-icon me-2"></i>
            <span>Shop</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse {{ request()->routeIs('shop.*') ? 'show' : '' }}" id="shopMenu">
            <ul class="nav flex-column ms-4">
                @if($currentUser?->hasPermission('shop_products.view'))
                <li class="menu-item"><a href="{{ route('shop.index', ['tab' => 'add-product']) }}" class="nav-link {{ request('tab', 'add-product') === 'add-product' ? 'active' : '' }}">Product List</a></li>
                @endif
                @if($currentUser?->hasPermission('shop_orders.view'))
                <li class="menu-item"><a href="{{ route('shop.index', ['tab' => 'new-order']) }}" class="nav-link {{ request('tab') === 'new-order' ? 'active' : '' }}">New Order</a></li>
                <li class="menu-item"><a href="{{ route('shop.index', ['tab' => 'in-progress']) }}" class="nav-link {{ request('tab') === 'in-progress' ? 'active' : '' }}">Order In Progress</a></li>
                <li class="menu-item"><a href="{{ route('shop.index', ['tab' => 'completed']) }}" class="nav-link {{ request('tab') === 'completed' ? 'active' : '' }}">Order Completed</a></li>
                <li class="menu-item"><a href="{{ route('shop.index', ['tab' => 'payment']) }}" class="nav-link {{ request('tab') === 'payment' ? 'active' : '' }}">Order Payment</a></li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    @if($currentUser?->hasPermission('shop_animal_buy_sell.view'))
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('shop.animal_buy_sell') ? 'active' : '' }}" href="{{ route('shop.animal_buy_sell') }}">
            <i class="iconoir-shopping-bag-arrow-down menu-icon me-2"></i>
            <span>Animal Buy/Sell</span>
        </a>
    </li>
    @endif

    @if($currentUser?->hasAnyPermission(['analytics_farmer.view','analytics_dairy.view','analytics_doctor.view','analytics_earnings.view']))
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}" href="#analyticsMenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('analytics.*') ? 'true' : 'false' }}">
            <i class="iconoir-reports menu-icon me-2"></i>
            <span>Report</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse {{ request()->routeIs('analytics.*') ? 'show' : '' }}" id="analyticsMenu">
            <ul class="nav flex-column ms-4">
                @if($currentUser?->hasPermission('analytics_farmer.view'))
                <li class="menu-item">
                    <a href="{{ route('analytics.farmer') }}" class="nav-link {{ request()->routeIs('analytics.farmer') ? 'active' : '' }}">
                        <i class="iconoir-group me-2"></i> Farmer Report
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('analytics_dairy.view'))
                <li class="menu-item">
                    <a href="{{ route('analytics.dairy') }}" class="nav-link {{ request()->routeIs('analytics.dairy') ? 'active' : '' }}">
                        <i class="iconoir-building me-2"></i> Dairy Report
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('analytics_doctor.view'))
                <li class="menu-item">
                    <a href="{{ route('analytics.doctor') }}" class="nav-link {{ request()->routeIs('analytics.doctor') ? 'active' : '' }}">
                        <i class="iconoir-health-shield me-2"></i> Doctor Report
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('analytics_earnings.view'))
                <li class="menu-item">
                    <a href="{{ route('analytics.earnings') }}" class="nav-link {{ request()->routeIs('analytics.earnings') ? 'active' : '' }}">
                        <i class="iconoir-dollar-circle me-2"></i> Earnings Report
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>
    @endif

    @if($currentUser?->hasAnyPermission([
        'settings_diseases.view','settings_feed_types.view','settings_vaccines.view','settings_templates.view','settings_roles.view','settings_users.view','settings_backup.view'
    ]))
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="#settingsMenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('settings.*') ? 'true' : 'false' }}">
            <i class="iconoir-settings menu-icon me-2"></i>
            <span>Settings</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse {{ request()->routeIs('settings.*') ? 'show' : '' }}" id="settingsMenu">
            <ul class="nav flex-column ms-4">
                @if($currentUser?->hasPermission('settings_users.view'))
                <li class="menu-item">
                    <a href="{{ route('settings.users.index') }}" class="nav-link {{ request()->routeIs('settings.users.*') ? 'active' : '' }}">
                        <i class="iconoir-user me-2"></i> Add User
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('settings_roles.view'))
                <li class="menu-item">
                    <a href="{{ route('settings.roles.index') }}" class="nav-link {{ request()->routeIs('settings.roles.*') ? 'active' : '' }}">
                        <i class="iconoir-shield-question me-2"></i> Role
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('settings_diseases.view'))
                <li class="menu-item">
                    <a href="{{ route('settings.diseases.index') }}" class="nav-link {{ request()->routeIs('settings.diseases.*') ? 'active' : '' }}">
                        <i class="iconoir-plus-circle me-2"></i> Add Disease
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('settings_feed_types.view'))
                <li class="menu-item">
                    <a href="{{ route('settings.feed-types.index') }}" class="nav-link {{ request()->routeIs('settings.feed-types.*') ? 'active' : '' }}">
                        <i class="iconoir-leaf me-2"></i> Add Feed Type
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('settings_vaccines.view'))
                <li class="menu-item">
                    <a href="{{ route('settings.vaccines.index') }}" class="nav-link {{ request()->routeIs('settings.vaccines.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-syringe me-2"></i> Add Vaccine
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('settings_templates.view'))
                <li class="menu-item">
                    <a href="{{ route('settings.templates.index') }}" class="nav-link {{ request()->routeIs('settings.templates.*') ? 'active' : '' }}">
                        <i class="iconoir-edit-pencil me-2"></i> Edit Templates
                    </a>
                </li>
                @endif
                @if($currentUser?->hasPermission('settings_backup.view'))
                <li class="menu-item">
                    <a href="{{ route('settings.backup.index') }}" class="nav-link {{ request()->routeIs('settings.backup.*') ? 'active' : '' }}">
                        <i class="iconoir-database-backup me-2"></i> Backup Data
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>
    @endif

</ul></div>
        </div></div></div>

<script>
function toggleSidebarLogo() {
    const isCollapsed = document.body.getAttribute('data-sidebar-size') === 'sm';
    document.querySelectorAll('.logo-sm').forEach(el => {
        el.style.display = isCollapsed ? 'block' : 'none';
    });
    document.querySelectorAll('.logo-lg').forEach(el => {
        el.style.display = isCollapsed ? 'none' : 'block';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    toggleSidebarLogo();
});
document.addEventListener('click', function(e) {
    if (e.target.closest('#togglemenu')) {
        setTimeout(toggleSidebarLogo, 300);
    }
});
</script>
<script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

<style>
.sidebar-divider {
    height: 1px;
    width: 100%;
    background: rgba(0,0,0,0.08);
}
</style>
