<?php

use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\Analytics\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Doctor\DoctorAppointmentController;
use App\Http\Controllers\Doctor\DoctorListController;
use App\Http\Controllers\Doctor\DoctorPlanController;
use App\Http\Controllers\Doctor\DoctorRatingController;
use App\Http\Controllers\Doctor\DoctorReferralController;
use App\Http\Controllers\Doctor\DoctorSettingController;
use App\Http\Controllers\Doctor\DoctorSubscriptionController;
use App\Http\Controllers\Doctor\DoctorVisitedController;
use App\Http\Controllers\Farmer\AnimalLifecycleController;
use App\Http\Controllers\Farmer\AnimalListController;
use App\Http\Controllers\Farmer\DairyListController;
use App\Http\Controllers\Farmer\DietPlanListController;
use App\Http\Controllers\Farmer\FarmerListController;
use App\Http\Controllers\Farmer\FarmerPlanController;
use App\Http\Controllers\Farmer\FarmerReferralController;
use App\Http\Controllers\Farmer\FarmerSettingController;
use App\Http\Controllers\Farmer\FeedSubtypeController;
use App\Http\Controllers\Farmer\FarmerSubscriptionController;
use App\Http\Controllers\Farmer\FeedingListController;
use App\Http\Controllers\Farmer\HealthManagementController;
use App\Http\Controllers\Farmer\MilkProduceListController;
use App\Http\Controllers\Farmer\PregnancyListController;
use App\Http\Controllers\Setting\AdminRoleController;
use App\Http\Controllers\Setting\AdminUserController;
use App\Http\Controllers\Setting\BackupController;
use App\Http\Controllers\Setting\DiseaseController;
use App\Http\Controllers\Setting\FeedTypeController;
use App\Http\Controllers\Setting\LanguageController;
use App\Http\Controllers\Setting\NotificationTemplateController;
use App\Http\Controllers\Setting\VaccineController;
use App\Http\Controllers\Shop\AnimalBuySellController;
use App\Http\Controllers\Shop\ShopProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth', 'admin.active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/notifications/{source}/{id}/read', [AdminNotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::prefix('farmer')->group(function () {
        Route::get('/list', [FarmerListController::class, 'index'])->middleware('permission:farmer_list.view')->name('farmer.list');
        Route::get('/list/create', [FarmerListController::class, 'create'])->middleware('permission:farmer_list.add')->name('farmer.create');
        Route::post('/list', [FarmerListController::class, 'store'])->middleware('permission:farmer_list.add')->name('farmer.store');
        Route::get('/list/{farmer}/edit', [FarmerListController::class, 'edit'])->middleware('permission:farmer_list.edit')->name('farmer.edit');
        Route::put('/list/{farmer}', [FarmerListController::class, 'update'])->middleware('permission:farmer_list.edit')->name('farmer.update');
        Route::post('/list/{farmer}/toggle', [FarmerListController::class, 'toggle'])->middleware('permission:farmer_list.status')->name('farmer.toggle');

        Route::get('/animals', [AnimalListController::class, 'index'])->middleware('permission:animal_list.view')->name('farmer.animals');
        Route::get('/pans', [AnimalListController::class, 'panList'])->middleware('permission:pan_list.view')->name('farmer.pans');
        Route::post('/pans', [AnimalListController::class, 'storePan'])->middleware('permission:pan_list.add')->name('farmer.pans.store');
        Route::post('/pans/transfer', [AnimalListController::class, 'transferPanAnimal'])->middleware('permission:pan_list.transfer')->name('farmer.pans.transfer');
        Route::delete('/pans/{pan}', [AnimalListController::class, 'destroyPan'])->middleware('permission:pan_list.delete')->name('farmer.pans.destroy');
        Route::get('/animals/import/template', [AnimalListController::class, 'downloadImportTemplate'])->middleware('permission:animal_list.import')->name('animal.import.template');
        Route::post('/animals/import', [AnimalListController::class, 'importAnimals'])->middleware('permission:animal_list.import')->name('animal.import');
        Route::get('/animals/create', [AnimalListController::class, 'create'])->middleware('permission:animal_list.add')->name('animal.create');
        Route::post('/animals', [AnimalListController::class, 'store'])->middleware('permission:animal_list.add')->name('animal.store');
        Route::get('/animals/{animal}/edit', [AnimalListController::class, 'edit'])->middleware('permission:animal_list.edit')->name('animal.edit');
        Route::put('/animals/{animal}', [AnimalListController::class, 'update'])->middleware('permission:animal_list.edit')->name('animal.update');
        Route::post('/animals/{animal}/toggle', [AnimalListController::class, 'toggle'])->middleware('permission:animal_list.status')->name('animal.toggle');

        Route::get('/milk-production', [MilkProduceListController::class, 'index'])->middleware('permission:milk_production.view')->name('farmer.milk');
        Route::get('/milk-production/create', [MilkProduceListController::class, 'create'])->middleware('permission:milk_production.add')->name('farmer.milk.create');
        Route::post('/milk-production', [MilkProduceListController::class, 'store'])->middleware('permission:milk_production.add')->name('farmer.milk.store');
        Route::get('/feeding', [FeedingListController::class, 'index'])->middleware('permission:feeding.view')->name('farmer.feeding');
        Route::post('/feeding', [FeedingListController::class, 'store'])->middleware('permission:feeding.add')->name('farmer.feeding.store');
        Route::get('/diet-plan', [DietPlanListController::class, 'index'])->middleware('permission:diet_plan.view')->name('farmer.diet-plan');
        Route::get('/diet-plan/create', [DietPlanListController::class, 'create'])->middleware('permission:diet_plan.add')->name('farmer.diet-plan.create');
        Route::get('/diet-plan/{plan}/edit', [DietPlanListController::class, 'edit'])->middleware('permission:diet_plan.edit')->name('farmer.diet-plan.edit');
        Route::post('/diet-plan', [DietPlanListController::class, 'store'])->middleware('permission:diet_plan.add')->name('farmer.diet-plan.store');
        Route::put('/diet-plan/{plan}', [DietPlanListController::class, 'update'])->middleware('permission:diet_plan.edit')->name('farmer.diet-plan.update');
        Route::delete('/diet-plan/{plan}', [DietPlanListController::class, 'destroy'])->middleware('permission:diet_plan.delete')->name('farmer.diet-plan.destroy');
        Route::get('/pregnancy', [PregnancyListController::class, 'index'])->middleware('permission:pregnancy.view')->name('farmer.pregnancy');
        Route::get('/pregnancy/create', [PregnancyListController::class, 'create'])->middleware('permission:pregnancy.add')->name('farmer.pregnancy.create');
        Route::post('/pregnancy', [PregnancyListController::class, 'store'])->middleware('permission:pregnancy.add')->name('farmer.pregnancy.store');
        Route::get('/pregnancy/{pregnancy}/edit', [PregnancyListController::class, 'edit'])->middleware('permission:pregnancy.edit')->name('farmer.pregnancy.edit');
        Route::put('/pregnancy/{pregnancy}', [PregnancyListController::class, 'update'])->middleware('permission:pregnancy.edit')->name('farmer.pregnancy.update');
        Route::delete('/pregnancy/{pregnancy}', [PregnancyListController::class, 'destroy'])->middleware('permission:pregnancy.delete')->name('farmer.pregnancy.destroy');
        Route::get('/dairy', [DairyListController::class, 'index'])->middleware('permission:dairy.view')->name('farmer.dairy');
        Route::post('/dairy', [DairyListController::class, 'store'])->middleware('permission:dairy.add')->name('farmer.dairy.store');
        Route::put('/dairy/{dairy}', [DairyListController::class, 'update'])->middleware('permission:dairy.edit')->name('farmer.dairy.update');
        Route::delete('/dairy/{dairy}', [DairyListController::class, 'destroy'])->middleware('permission:dairy.delete')->name('farmer.dairy.destroy');
        Route::get('/feed-subtypes', [FeedSubtypeController::class, 'index'])->middleware('permission:farmer_feed_subtypes.view')->name('farmer.feed-subtypes.index');
        Route::get('/feed-subtypes/create', [FeedSubtypeController::class, 'create'])->middleware('permission:farmer_feed_subtypes.add')->name('farmer.feed-subtypes.create');
        Route::post('/feed-subtypes', [FeedSubtypeController::class, 'store'])->middleware('permission:farmer_feed_subtypes.add')->name('farmer.feed-subtypes.store');
        Route::get('/feed-subtypes/{feedSubtype}/edit', [FeedSubtypeController::class, 'edit'])->middleware('permission:farmer_feed_subtypes.edit')->name('farmer.feed-subtypes.edit');
        Route::put('/feed-subtypes/{feedSubtype}', [FeedSubtypeController::class, 'update'])->middleware('permission:farmer_feed_subtypes.edit')->name('farmer.feed-subtypes.update');
        Route::post('/feed-subtypes/{feedSubtype}/toggle', [FeedSubtypeController::class, 'toggle'])->middleware('permission:farmer_feed_subtypes.status')->name('farmer.feed-subtypes.toggle');
        Route::delete('/feed-subtypes/{feedSubtype}', [FeedSubtypeController::class, 'destroy'])->middleware('permission:farmer_feed_subtypes.delete')->name('farmer.feed-subtypes.destroy');
        Route::get('/settings', [FarmerSettingController::class, 'index'])->middleware('permission:farmer_settings.view')->name('farmer.settings');
        Route::post('/settings/support-contact', [FarmerSettingController::class, 'updateSupportContact'])->middleware('permission:farmer_settings.edit')->name('farmer.settings.support-contact.update');
        Route::post('/settings/banner', [FarmerSettingController::class, 'uploadBanner'])->middleware('permission:farmer_settings.edit')->name('farmer.settings.banner.upload');
        Route::delete('/settings/banner/{farmerBanner}', [FarmerSettingController::class, 'destroyBanner'])->middleware('permission:farmer_settings.edit')->name('farmer.settings.banner.destroy');
        Route::get('/referred', [FarmerReferralController::class, 'index'])->middleware('permission:farmer_referred.view')->name('farmer.referred');
        Route::get('/plan', [FarmerPlanController::class, 'index'])->middleware('permission:farmer_plan.view')->name('farmer.plan.index');
        Route::post('/plan', [FarmerPlanController::class, 'store'])->middleware('permission:farmer_plan.add')->name('farmer.plan.store');
        Route::put('/plan/{farmerPlan}', [FarmerPlanController::class, 'update'])->middleware('permission:farmer_plan.edit')->name('farmer.plan.update');
        Route::get('/subscription', [FarmerSubscriptionController::class, 'index'])->middleware('permission:farmer_subscription.view')->name('farmer.subscription.index');
        Route::post('/subscription', [FarmerSubscriptionController::class, 'store'])->middleware('permission:farmer_subscription.add')->name('farmer.subscription.store');
    });

    Route::prefix('animal-lifecycle')->group(function () {
        Route::get('/active', [AnimalLifecycleController::class, 'active'])->middleware('permission:animal_lifecycle_active.view')->name('animal.lifecycle.active');
        Route::post('/active/{animal}/sell', [AnimalLifecycleController::class, 'sell'])->middleware('permission:animal_lifecycle_active.view')->name('animal.lifecycle.active.sell');
        Route::post('/active/{animal}/cancel-selling', [AnimalLifecycleController::class, 'cancelSelling'])->middleware('permission:animal_lifecycle_active.view')->name('animal.lifecycle.active.cancel_selling');
        Route::post('/active/{animal}/sold', [AnimalLifecycleController::class, 'markSold'])->middleware('permission:animal_lifecycle_active.view')->name('animal.lifecycle.active.sold');
        Route::post('/active/{animal}/death', [AnimalLifecycleController::class, 'markDeath'])->middleware('permission:animal_lifecycle_active.view')->name('animal.lifecycle.active.death');
        Route::post('/active/{animal}/transfer', [AnimalLifecycleController::class, 'transferFromActive'])->middleware('permission:animal_lifecycle_active.view')->name('animal.lifecycle.active.transfer');
        Route::get('/sold', [AnimalLifecycleController::class, 'sold'])->middleware('permission:animal_lifecycle_sold.view')->name('animal.lifecycle.sold');
        Route::get('/death', [AnimalLifecycleController::class, 'death'])->middleware('permission:animal_lifecycle_death.view')->name('animal.lifecycle.death');
        Route::get('/pan-transfer', [AnimalLifecycleController::class, 'panTransfer'])->middleware('permission:animal_lifecycle_pan_transfer.view')->name('animal.lifecycle.pan_transfer');
    });

    Route::prefix('health')->group(function () {
        Route::get('/medical', [HealthManagementController::class, 'medical'])->middleware('permission:health_mastitis.view')->name('health.medical');
        Route::post('/medical', [HealthManagementController::class, 'storeMedical'])->middleware('permission:health_mastitis.add')->name('health.medical.store');
        Route::get('/mastitis', [HealthManagementController::class, 'mastitis'])->middleware('permission:health_mastitis.view')->name('health.mastitis');
        Route::post('/mastitis', [HealthManagementController::class, 'storeMastitis'])->middleware('permission:health_mastitis.add')->name('health.mastitis.store');
        Route::post('/mastitis/treatment', [HealthManagementController::class, 'storeMastitisTreatment'])->middleware('permission:health_mastitis.add')->name('health.mastitis.treatment');
        Route::post('/mastitis/recover', [HealthManagementController::class, 'recoverMastitis'])->middleware('permission:health_mastitis.edit')->name('health.mastitis.recover');
        Route::get('/vaccination', [HealthManagementController::class, 'vaccination'])->middleware('permission:health_vaccination.view')->name('health.vaccination');
        Route::post('/vaccination', [HealthManagementController::class, 'storeVaccination'])->middleware('permission:health_vaccination.add')->name('health.vaccination.store');
        Route::get('/dmi', [HealthManagementController::class, 'dmi'])->middleware('permission:health_dmi.view')->name('health.dmi');
        Route::post('/dmi', [HealthManagementController::class, 'storeDmi'])->middleware('permission:health_dmi.add')->name('health.dmi.store');
    });

    Route::prefix('doctor')->group(function () {
        Route::get('/', [DoctorListController::class, 'index'])->middleware('permission:doctor_list.view')->name('doctor.index');
        Route::get('/register', [DoctorListController::class, 'create'])->middleware('permission:doctor_registration.add')->name('doctor.create');
        Route::post('/register', [DoctorListController::class, 'store'])->middleware('permission:doctor_registration.add')->name('doctor.store');
        Route::get('/live-location', [DoctorListController::class, 'liveLocation'])->middleware('permission:doctor_list.view')->name('doctor.live_location');
        Route::get('/appointments', [DoctorAppointmentController::class, 'index'])->middleware('permission:doctor_appointments.view')->name('doctor.appointments');
        Route::post('/appointments/{appointment}/assign-doctor', [DoctorAppointmentController::class, 'assignDoctor'])->middleware('permission:doctor_appointments.assign')->name('doctor.appointments.assign');
        Route::get('/visited', [DoctorVisitedController::class, 'index'])->middleware('permission:doctor_visited.view')->name('doctor.visited');
        Route::get('/settings', [DoctorSettingController::class, 'index'])->middleware('permission:doctor_settings.view')->name('doctor.settings');
        Route::get('/ratings', [DoctorRatingController::class, 'index'])->middleware('permission:doctor_ratings.view')->name('doctor.ratings');
        Route::get('/referred', [DoctorReferralController::class, 'index'])->middleware('permission:doctor_referred.view')->name('doctor.referred');
        Route::post('/settings', [DoctorSettingController::class, 'update'])->middleware('permission:doctor_settings.edit')->name('doctor.settings.update');
        Route::post('/settings/banner', [DoctorSettingController::class, 'uploadBanner'])->middleware('permission:doctor_settings.edit')->name('doctor.settings.banner.upload');
        Route::delete('/settings/banner/{doctorBanner}', [DoctorSettingController::class, 'destroyBanner'])->middleware('permission:doctor_settings.edit')->name('doctor.settings.banner.destroy');
        Route::get('/plan', [DoctorPlanController::class, 'index'])->middleware('permission:doctor_plan.view')->name('doctor.plan.index');
        Route::post('/plan', [DoctorPlanController::class, 'store'])->middleware('permission:doctor_plan.add')->name('doctor.plan.store');
        Route::put('/plan/{doctorPlan}', [DoctorPlanController::class, 'update'])->middleware('permission:doctor_plan.edit')->name('doctor.plan.update');
        Route::get('/subscription', [DoctorSubscriptionController::class, 'index'])->middleware('permission:doctor_subscription.view')->name('doctor.subscription.index');
        Route::post('/subscription', [DoctorSubscriptionController::class, 'store'])->middleware('permission:doctor_subscription.add')->name('doctor.subscription.store');
        Route::get('/{doctor}', [DoctorListController::class, 'show'])->middleware('permission:doctor_list.view')->name('doctor.show');
        Route::post('/{doctor}/toggle-approval', [DoctorListController::class, 'toggleApproval'])->middleware('permission:doctor_list.status')->name('doctor.toggle_approval');
    });

    Route::prefix('shop')->group(function () {
        Route::get('/', [ShopProductController::class, 'index'])->middleware('permission:shop_products.view,shop_orders.view')->name('shop.index');
        Route::post('/', [ShopProductController::class, 'store'])->middleware('permission:shop_products.add')->name('shop.store');
        Route::post('/categories', [ShopProductController::class, 'storeCategory'])->middleware('permission:shop_products.add')->name('shop.categories.store');
        Route::put('/categories/{category}', [ShopProductController::class, 'updateCategory'])->middleware('permission:shop_products.edit')->name('shop.categories.update');
        Route::post('/units', [ShopProductController::class, 'storeUnit'])->middleware('permission:shop_products.add')->name('shop.units.store');
        Route::put('/units/{unit}', [ShopProductController::class, 'updateUnit'])->middleware('permission:shop_products.edit')->name('shop.units.update');
        Route::post('/orders/{order}/status', [ShopProductController::class, 'updateOrderStatus'])->middleware('permission:shop_orders.status')->name('shop.orders.status');
        Route::get('/animal-buy-sell', [AnimalBuySellController::class, 'index'])->middleware('permission:shop_animal_buy_sell.view')->name('shop.animal_buy_sell');
        Route::post('/animal-buy-sell/{animal}/cancel', [AnimalBuySellController::class, 'cancel'])->middleware('permission:shop_animal_buy_sell.view')->name('shop.animal_buy_sell.cancel');
    });

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/farmer', [AnalyticsController::class, 'farmerAnalysis'])->middleware('permission:analytics_farmer.view')->name('farmer');
        Route::get('/dairy', [AnalyticsController::class, 'dairyAnalysis'])->middleware('permission:analytics_dairy.view')->name('dairy');
        Route::get('/doctor', [AnalyticsController::class, 'doctorAnalysis'])->middleware('permission:analytics_doctor.view')->name('doctor');
        Route::get('/earnings', [AnalyticsController::class, 'earnings'])->middleware('permission:analytics_earnings.view')->name('earnings');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/diseases', [DiseaseController::class, 'index'])->middleware('permission:settings_diseases.view')->name('diseases.index');
        Route::post('/diseases', [DiseaseController::class, 'store'])->middleware('permission:settings_diseases.add')->name('diseases.store');
        Route::put('/diseases/{disease}', [DiseaseController::class, 'update'])->middleware('permission:settings_diseases.edit')->name('diseases.update');
        Route::post('/diseases/{disease}/toggle', [DiseaseController::class, 'toggle'])->middleware('permission:settings_diseases.status')->name('diseases.toggle');

        Route::get('/feed-types', [FeedTypeController::class, 'index'])->middleware('permission:settings_feed_types.view')->name('feed-types.index');
        Route::post('/feed-types', [FeedTypeController::class, 'store'])->middleware('permission:settings_feed_types.add')->name('feed-types.store');
        Route::put('/feed-types/{feedType}', [FeedTypeController::class, 'update'])->middleware('permission:settings_feed_types.edit')->name('feed-types.update');
        Route::post('/feed-types/{feedType}/toggle', [FeedTypeController::class, 'toggle'])->middleware('permission:settings_feed_types.status')->name('feed-types.toggle');

        Route::get('/vaccines', [VaccineController::class, 'index'])->middleware('permission:settings_vaccines.view')->name('vaccines.index');
        Route::post('/vaccines', [VaccineController::class, 'store'])->middleware('permission:settings_vaccines.add')->name('vaccines.store');
        Route::put('/vaccines/{vaccine}', [VaccineController::class, 'update'])->middleware('permission:settings_vaccines.edit')->name('vaccines.update');
        Route::post('/vaccines/{vaccine}/toggle', [VaccineController::class, 'toggle'])->middleware('permission:settings_vaccines.status')->name('vaccines.toggle');

        Route::get('/templates', [NotificationTemplateController::class, 'index'])->middleware('permission:settings_templates.view')->name('templates.index');
        Route::put('/templates/{template}', [NotificationTemplateController::class, 'update'])->middleware('permission:settings_templates.edit')->name('templates.update');
        Route::post('/templates/{template}/toggle', [NotificationTemplateController::class, 'toggle'])->middleware('permission:settings_templates.status')->name('templates.toggle');

        Route::get('/roles', [AdminRoleController::class, 'index'])->middleware('permission:settings_roles.view')->name('roles.index');
        Route::get('/roles/create', [AdminRoleController::class, 'create'])->middleware('permission:settings_roles.add')->name('roles.create');
        Route::post('/roles', [AdminRoleController::class, 'store'])->middleware('permission:settings_roles.add')->name('roles.store');
        Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->middleware('permission:settings_roles.edit')->name('roles.update');
        Route::post('/roles/{role}/toggle', [AdminRoleController::class, 'toggle'])->middleware('permission:settings_roles.status')->name('roles.toggle');

        Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:settings_users.view')->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->middleware('permission:settings_users.add')->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('permission:settings_users.edit')->name('users.update');
        Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggle'])->middleware('permission:settings_users.status')->name('users.toggle');

        Route::get('/language', [LanguageController::class, 'index'])->middleware('permission:settings_language.view')->name('language.index');

        Route::get('/backup', [BackupController::class, 'index'])->middleware('permission:settings_backup.view')->name('backup.index');
        Route::get('/backup/download', [BackupController::class, 'download'])->middleware('permission:settings_backup.export')->name('backup.download');
    });
});



