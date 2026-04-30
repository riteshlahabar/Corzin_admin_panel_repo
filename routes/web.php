<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Analytics\AnalyticsController;
use App\Http\Controllers\Farmer\FarmerListController;
use App\Http\Controllers\Farmer\AnimalListController;
use App\Http\Controllers\Farmer\MilkProduceListController;
use App\Http\Controllers\Farmer\FeedingListController;
use App\Http\Controllers\Farmer\DairyListController;
use App\Http\Controllers\Farmer\AnimalLifecycleController;
use App\Http\Controllers\Farmer\HealthManagementController;
use App\Http\Controllers\Farmer\FarmerPlanController;
use App\Http\Controllers\Farmer\FarmerSubscriptionController;
use App\Http\Controllers\Doctor\DoctorAppointmentController;
use App\Http\Controllers\Doctor\DoctorListController;
use App\Http\Controllers\Doctor\DoctorSettingController;
use App\Http\Controllers\Doctor\DoctorVisitedController;
use App\Http\Controllers\Doctor\DoctorPlanController;
use App\Http\Controllers\Doctor\DoctorSubscriptionController;
use App\Http\Controllers\Shop\ShopProductController;
use App\Http\Controllers\Shop\AnimalBuySellController;
use App\Http\Controllers\Reproductive\ReproductiveListController;
use App\Http\Controllers\Setting\DiseaseController;
use App\Http\Controllers\Setting\FeedTypeController;
use App\Http\Controllers\Setting\NotificationTemplateController;
use App\Http\Controllers\AdminNotificationController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/notifications/{source}/{id}/read', [AdminNotificationController::class, 'markRead'])->name('notifications.read');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('farmer')->group(function () {
    Route::get('/list', [FarmerListController::class, 'index'])->name('farmer.list');
    Route::get('/list/create', [FarmerListController::class, 'create'])->name('farmer.create');
    Route::post('/list', [FarmerListController::class, 'store'])->name('farmer.store');
    Route::get('/list/{farmer}/edit', [FarmerListController::class, 'edit'])->name('farmer.edit');
    Route::put('/list/{farmer}', [FarmerListController::class, 'update'])->name('farmer.update');
    Route::post('/list/{farmer}/toggle', [FarmerListController::class, 'toggle'])->name('farmer.toggle');

    Route::get('/animals', [AnimalListController::class, 'index'])->name('farmer.animals');
    Route::get('/pans', [AnimalListController::class, 'panList'])->name('farmer.pans');
    Route::get('/animals/create', [AnimalListController::class, 'create'])->name('animal.create');
    Route::post('/animals', [AnimalListController::class, 'store'])->name('animal.store');
    Route::get('/animals/{animal}/edit', [AnimalListController::class, 'edit'])->name('animal.edit');
    Route::put('/animals/{animal}', [AnimalListController::class, 'update'])->name('animal.update');
    Route::post('/animals/{animal}/toggle', [AnimalListController::class, 'toggle'])->name('animal.toggle');

    Route::get('/milk-production', [MilkProduceListController::class, 'index'])->name('farmer.milk');
    Route::get('/feeding', [FeedingListController::class, 'index'])->name('farmer.feeding');
    Route::post('/feeding', [FeedingListController::class, 'store'])->name('farmer.feeding.store');
    Route::get('/dairy', [DairyListController::class, 'index'])->name('farmer.dairy');
    Route::post('/dairy', [DairyListController::class, 'store'])->name('farmer.dairy.store');
    Route::get('/plan', [FarmerPlanController::class, 'index'])->name('farmer.plan.index');
    Route::post('/plan', [FarmerPlanController::class, 'store'])->name('farmer.plan.store');
    Route::put('/plan/{farmerPlan}', [FarmerPlanController::class, 'update'])->name('farmer.plan.update');
    Route::get('/subscription', [FarmerSubscriptionController::class, 'index'])->name('farmer.subscription.index');
    Route::post('/subscription', [FarmerSubscriptionController::class, 'store'])->name('farmer.subscription.store');
});

Route::prefix('animal-lifecycle')->group(function () {
    Route::get('/active', [AnimalLifecycleController::class, 'active'])->name('animal.lifecycle.active');
    Route::get('/sold', [AnimalLifecycleController::class, 'sold'])->name('animal.lifecycle.sold');
    Route::get('/death', [AnimalLifecycleController::class, 'death'])->name('animal.lifecycle.death');
    Route::get('/pan-transfer', [AnimalLifecycleController::class, 'panTransfer'])->name('animal.lifecycle.pan_transfer');
});

Route::prefix('reproductive')->group(function () {
    Route::get('/', [ReproductiveListController::class, 'index'])->name('reproductive.index');
    Route::post('/', [ReproductiveListController::class, 'store'])->name('reproductive.store');
});

Route::prefix('health')->group(function () {
    Route::get('/medical', [HealthManagementController::class, 'medical'])->name('health.medical');
    Route::post('/medical', [HealthManagementController::class, 'storeMedical'])->name('health.medical.store');
    Route::get('/mastitis', [HealthManagementController::class, 'mastitis'])->name('health.mastitis');
    Route::post('/mastitis', [HealthManagementController::class, 'storeMastitis'])->name('health.mastitis.store');
    Route::get('/dmi', [HealthManagementController::class, 'dmi'])->name('health.dmi');
    Route::post('/dmi', [HealthManagementController::class, 'storeDmi'])->name('health.dmi.store');
});
Route::prefix('doctor')->group(function () {
    Route::get('/', [DoctorListController::class, 'index'])->name('doctor.index');
    Route::get('/register', [DoctorListController::class, 'create'])->name('doctor.create');
    Route::post('/register', [DoctorListController::class, 'store'])->name('doctor.store');
    Route::get('/live-location', [DoctorListController::class, 'liveLocation'])->name('doctor.live_location');
    Route::get('/appointments', [DoctorAppointmentController::class, 'index'])->name('doctor.appointments');
    Route::post('/appointments/{appointment}/assign-doctor', [DoctorAppointmentController::class, 'assignDoctor'])->name('doctor.appointments.assign');
    Route::get('/visited', [DoctorVisitedController::class, 'index'])->name('doctor.visited');
    Route::get('/settings', [DoctorSettingController::class, 'index'])->name('doctor.settings');
    Route::post('/settings', [DoctorSettingController::class, 'update'])->name('doctor.settings.update');
    Route::post('/settings/banner', [DoctorSettingController::class, 'uploadBanner'])->name('doctor.settings.banner.upload');
    Route::delete('/settings/banner/{doctorBanner}', [DoctorSettingController::class, 'destroyBanner'])->name('doctor.settings.banner.destroy');
    Route::get('/plan', [DoctorPlanController::class, 'index'])->name('doctor.plan.index');
    Route::post('/plan', [DoctorPlanController::class, 'store'])->name('doctor.plan.store');
    Route::put('/plan/{doctorPlan}', [DoctorPlanController::class, 'update'])->name('doctor.plan.update');
    Route::get('/subscription', [DoctorSubscriptionController::class, 'index'])->name('doctor.subscription.index');
    Route::post('/subscription', [DoctorSubscriptionController::class, 'store'])->name('doctor.subscription.store');
    Route::get('/{doctor}', [DoctorListController::class, 'show'])->name('doctor.show');
    Route::post('/{doctor}/toggle-approval', [DoctorListController::class, 'toggleApproval'])->name('doctor.toggle_approval');
});

Route::prefix('shop')->group(function () {
    Route::get('/', [ShopProductController::class, 'index'])->name('shop.index');
    Route::post('/', [ShopProductController::class, 'store'])->name('shop.store');
    Route::post('/orders/{order}/status', [ShopProductController::class, 'updateOrderStatus'])->name('shop.orders.status');
    Route::get('/animal-buy-sell', [AnimalBuySellController::class, 'index'])->name('shop.animal_buy_sell');
});

Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('/farmer', [AnalyticsController::class, 'farmerAnalysis'])->name('farmer');
    Route::get('/doctor', [AnalyticsController::class, 'doctorAnalysis'])->name('doctor');
    Route::get('/earnings', [AnalyticsController::class, 'earnings'])->name('earnings');
});

Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/diseases', [DiseaseController::class, 'index'])->name('diseases.index');
    Route::post('/diseases', [DiseaseController::class, 'store'])->name('diseases.store');
    Route::put('/diseases/{disease}', [DiseaseController::class, 'update'])->name('diseases.update');
    Route::post('/diseases/{disease}/toggle', [DiseaseController::class, 'toggle'])->name('diseases.toggle');
    Route::get('/feed-types', [FeedTypeController::class, 'index'])->name('feed-types.index');
    Route::post('/feed-types', [FeedTypeController::class, 'store'])->name('feed-types.store');
    Route::put('/feed-types/{feedType}', [FeedTypeController::class, 'update'])->name('feed-types.update');
    Route::post('/feed-types/{feedType}/toggle', [FeedTypeController::class, 'toggle'])->name('feed-types.toggle');
    Route::get('/templates', [NotificationTemplateController::class, 'index'])->name('templates.index');
    Route::put('/templates/{template}', [NotificationTemplateController::class, 'update'])->name('templates.update');
    Route::post('/templates/{template}/toggle', [NotificationTemplateController::class, 'toggle'])->name('templates.toggle');
});
