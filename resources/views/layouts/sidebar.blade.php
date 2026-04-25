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

    <li class="nav-item">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="iconoir-report-columns menu-icon me-2"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="#farmerMenu" data-bs-toggle="collapse" role="button">
            <i class="iconoir-user menu-icon me-2"></i>
            <span>Farmer Data</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse" id="farmerMenu">
            <ul class="nav flex-column ms-4">
                <li class="menu-item"><a href="{{ route('farmer.list') }}" class="nav-link"><i class="iconoir-group me-2"></i> Farmer List</a></li>
                <li class="menu-item"><a href="{{ route('farmer.animals') }}" class="nav-link"><i class="fas fa-cow me-2"></i> Animal List</a></li>
                <li class="menu-item"><a href="{{ route('farmer.animals') }}#panListSection" class="nav-link"><i class="iconoir-list me-2"></i> PAN List</a></li>
                <li class="menu-item"><a href="{{ route('farmer.milk') }}" class="nav-link"><i class="iconoir-droplet me-2"></i> Milk Production</a></li>
                <li class="menu-item"><a href="{{ route('farmer.feeding') }}" class="nav-link"><i class="iconoir-leaf me-2"></i> Feeding</a></li>
                <li class="menu-item"><a href="{{ route('farmer.dairy') }}" class="nav-link"><i class="iconoir-building me-2"></i> Dairy</a></li>
                <li class="menu-item"><a href="{{ route('farmer.plan.index') }}" class="nav-link"><i class="iconoir-coins me-2"></i> Farmer Plan</a></li>
                <li class="menu-item"><a href="{{ route('farmer.subscription.index') }}" class="nav-link"><i class="iconoir-wallet me-2"></i> Farmer Subscription</a></li>
            </ul>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="{{ route('reproductive.index') }}">
            <i class="iconoir-heart menu-icon me-2"></i>
            <span>Reproductive</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="#lifecycleMenu" data-bs-toggle="collapse" role="button">
            <i class="iconoir-refresh-circle menu-icon me-2"></i>
            <span>Animal Lifecycle</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse" id="lifecycleMenu">
            <ul class="nav flex-column ms-4">
                <li><a href="{{ route('animal.lifecycle.active') }}" class="nav-link"><i class="iconoir-check me-2"></i> Active</a></li>
                <li><a href="{{ route('animal.lifecycle.sold') }}" class="nav-link"><i class="iconoir-shopping-bag-arrow-down me-2"></i> Sold</a></li>
                <li><a href="{{ route('animal.lifecycle.death') }}" class="nav-link"><i class="iconoir-xmark me-2"></i> Death</a></li>
                <li><a href="{{ route('animal.lifecycle.pan_transfer') }}" class="nav-link"><i class="iconoir-repeat me-2"></i> Pan Transfer</a></li>
            </ul>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="#healthMenu" data-bs-toggle="collapse" role="button">
            <i class="iconoir-health-shield menu-icon me-2"></i>
            <span>Health</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse" id="healthMenu">
            <ul class="nav flex-column ms-4">
                <li><a href="{{ route('health.medical') }}" class="nav-link"><i class="iconoir-hospital me-2"></i> Medical</a></li>
                <li><a href="{{ route('health.mastitis') }}" class="nav-link"><i class="iconoir-warning-triangle me-2"></i> Mastitis</a></li>
                <li><a href="{{ route('health.dmi') }}" class="nav-link"><i class="iconoir-calculator me-2"></i> DMI Calculator</a></li>
            </ul>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('doctor.*') ? 'active' : '' }}" href="#doctorMenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('doctor.*') ? 'true' : 'false' }}">
            <i class="iconoir-user menu-icon me-2"></i>
            <span>Doctor</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse {{ request()->routeIs('doctor.*') ? 'show' : '' }}" id="doctorMenu">
            <ul class="nav flex-column ms-4">
                <li class="menu-item">
                    <a href="{{ route('doctor.create') }}" class="nav-link {{ request()->routeIs('doctor.create') ? 'active' : '' }}">
                        <i class="iconoir-plus-circle me-2"></i> Register Doctor
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('doctor.index') }}" class="nav-link {{ request()->routeIs('doctor.index', 'doctor.show') ? 'active' : '' }}">
                        <i class="iconoir-page-search me-2"></i> Doctor List
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('doctor.live_location') }}" class="nav-link {{ request()->routeIs('doctor.live_location') ? 'active' : '' }}">
                        <i class="iconoir-map-pin me-2"></i> Live Location
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('doctor.appointments') }}" class="nav-link {{ request()->routeIs('doctor.appointments') ? 'active' : '' }}">
                        <i class="iconoir-calendar me-2"></i> Appointment
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('doctor.visited') }}" class="nav-link {{ request()->routeIs('doctor.visited') ? 'active' : '' }}">
                        <i class="iconoir-check-circle me-2"></i> Visited
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('doctor.settings') }}" class="nav-link {{ request()->routeIs('doctor.settings', 'doctor.settings.update') ? 'active' : '' }}">
                        <i class="iconoir-settings me-2"></i> Settings
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('doctor.plan.index') }}" class="nav-link {{ request()->routeIs('doctor.plan.*') ? 'active' : '' }}">
                        <i class="iconoir-coins me-2"></i> Doctor Plan
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('doctor.subscription.index') }}" class="nav-link {{ request()->routeIs('doctor.subscription.*') ? 'active' : '' }}">
                        <i class="iconoir-wallet me-2"></i> Doctor Subscription
                    </a>
                </li>
            </ul>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('shop.*') ? 'active' : '' }}" href="#shopMenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('shop.*') ? 'true' : 'false' }}">
            <i class="iconoir-cart menu-icon me-2"></i>
            <span>Shop</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse {{ request()->routeIs('shop.*') ? 'show' : '' }}" id="shopMenu">
            <ul class="nav flex-column ms-4">
                <li class="menu-item"><a href="{{ route('shop.index', ['tab' => 'add-product']) }}" class="nav-link {{ request('tab', 'add-product') === 'add-product' ? 'active' : '' }}">Add Product</a></li>
                <li class="menu-item"><a href="{{ route('shop.index', ['tab' => 'new-order']) }}" class="nav-link {{ request('tab') === 'new-order' ? 'active' : '' }}">New Order</a></li>
                <li class="menu-item"><a href="{{ route('shop.index', ['tab' => 'in-progress']) }}" class="nav-link {{ request('tab') === 'in-progress' ? 'active' : '' }}">Order In Progress</a></li>
                <li class="menu-item"><a href="{{ route('shop.index', ['tab' => 'completed']) }}" class="nav-link {{ request('tab') === 'completed' ? 'active' : '' }}">Order Completed</a></li>
                <li class="menu-item"><a href="{{ route('shop.index', ['tab' => 'payment']) }}" class="nav-link {{ request('tab') === 'payment' ? 'active' : '' }}">Order Payment</a></li>
            </ul>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('shop.animal_buy_sell') ? 'active' : '' }}" href="{{ route('shop.animal_buy_sell') }}">
            <i class="iconoir-shopping-bag-arrow-down menu-icon me-2"></i>
            <span>Animal Buy/Sell</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}" href="#analyticsMenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('analytics.*') ? 'true' : 'false' }}">
            <i class="iconoir-reports menu-icon me-2"></i>
            <span>Analytics</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse {{ request()->routeIs('analytics.*') ? 'show' : '' }}" id="analyticsMenu">
            <ul class="nav flex-column ms-4">
                <li class="menu-item">
                    <a href="{{ route('analytics.farmer') }}" class="nav-link {{ request()->routeIs('analytics.farmer') ? 'active' : '' }}">
                        <i class="iconoir-group me-2"></i> Farmer Analysis
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('analytics.doctor') }}" class="nav-link {{ request()->routeIs('analytics.doctor') ? 'active' : '' }}">
                        <i class="iconoir-health-shield me-2"></i> Doctor Analysis
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('analytics.earnings') }}" class="nav-link {{ request()->routeIs('analytics.earnings') ? 'active' : '' }}">
                        <i class="iconoir-dollar-circle me-2"></i> Earnings
                    </a>
                </li>
            </ul>
        </div>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="#settingsMenu" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('settings.*') ? 'true' : 'false' }}">
            <i class="iconoir-settings menu-icon me-2"></i>
            <span>Settings</span>
            <span class="menu-arrow"></span>
        </a>
        <div class="collapse {{ request()->routeIs('settings.*') ? 'show' : '' }}" id="settingsMenu">
            <ul class="nav flex-column ms-4">
                @if (Route::has('settings.diseases.index'))
                <li class="menu-item">
                    <a href="{{ route('settings.diseases.index') }}" class="nav-link {{ request()->routeIs('settings.diseases.*') ? 'active' : '' }}">
                        <i class="iconoir-plus-circle me-2"></i> Add Disease
                    </a>
                </li>
                @endif
                @if (Route::has('settings.templates.index'))
                <li class="menu-item">
                    <a href="{{ route('settings.templates.index') }}" class="nav-link {{ request()->routeIs('settings.templates.*') ? 'active' : '' }}">
                        <i class="iconoir-edit-pencil me-2"></i> Edit Templates
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>

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









