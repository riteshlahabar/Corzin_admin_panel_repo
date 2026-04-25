<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Farmer\AuthController;
use App\Http\Controllers\Api\Farmer\FarmerController;
use App\Http\Controllers\Api\Farmer\AnimalController;
use App\Http\Controllers\Api\Farmer\MilkProductionController;
use App\Http\Controllers\Api\Farmer\AnimalTypeController;
use App\Http\Controllers\Api\Farmer\DairyController;
use App\Http\Controllers\Api\Farmer\FeedingController;
use App\Http\Controllers\Api\Farmer\ReproductiveController;
use App\Http\Controllers\Api\Farmer\HealthController;
use App\Http\Controllers\Api\Farmer\SubscriptionController;
use App\Http\Controllers\Api\Doctor\DoctorController as FarmerDoctorController;
use App\Http\Controllers\Api\Doctor\DoctorAppointmentController as FarmerDoctorAppointmentController;
use App\Http\Controllers\Api\Doctor\DoctorSettingController as FarmerDoctorSettingController;
use App\Http\Controllers\Api\DoctorApp\DoctorAppointmentController as DoctorAppAppointmentController;
use App\Http\Controllers\Api\DoctorApp\DoctorAppController;
use App\Http\Controllers\Api\DoctorApp\LocationController as DoctorAppLocationController;
use App\Http\Controllers\Api\DoctorApp\DoctorSettingController as DoctorAppSettingController;
use App\Http\Controllers\Api\Shop\ShopController;
use App\Http\Controllers\Api\WebPushTokenController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/check-user', [AuthController::class, 'checkUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('farmer')->group(function () {
    Route::post('/store', [FarmerController::class, 'store']);
    Route::get('/profile/{mobile}', [FarmerController::class, 'getProfileByMobile']);
    Route::post('/update/{id}', [FarmerController::class, 'update']);
    Route::post('/fcm-token/{id}', [FarmerController::class, 'updateFcmToken']);
    Route::post('/location/{id}', [FarmerController::class, 'updateCurrentLocation']);
});

Route::prefix('animal')->group(function () {
    Route::post('/store', [AnimalController::class, 'store']);
    Route::post('/update/{animal_id}', [AnimalController::class, 'update']);
    Route::post('/sell/{animal_id}', [AnimalController::class, 'markForSale']);
    Route::get('/types', [AnimalTypeController::class, 'index']);
    Route::get('/list/{farmer_id}', [AnimalController::class, 'listByFarmer']);
    Route::get('/pans/{farmer_id}', [AnimalController::class, 'panList']);
    Route::post('/pans', [AnimalController::class, 'createPan']);
    Route::post('/pans/{panId}/update', [AnimalController::class, 'updatePan']);
    Route::post('/pans/transfer', [AnimalController::class, 'transferPanAnimal']);
    Route::post('/lifecycle/{animal_id}', [AnimalController::class, 'updateLifecycle']);
    Route::get('/history/{farmer_id}', [AnimalController::class, 'history']);
});

Route::prefix('milk')->group(function () {
    Route::post('/', [MilkProductionController::class, 'store']);
    Route::get('/animal/{animal_id}', [MilkProductionController::class, 'index']);
    Route::get('/list/{farmer_id}', [MilkProductionController::class, 'listByFarmer']);
    Route::post('/update/{milk_id}', [MilkProductionController::class, 'update']);
});

Route::prefix('feeding')->group(function () {
    Route::post('/', [FeedingController::class, 'store']);
    Route::post('/update/{feeding_id}', [FeedingController::class, 'update']);
    Route::get('/types', [FeedingController::class, 'types']);
    Route::get('/list/{farmer_id}', [FeedingController::class, 'list']);
    Route::get('/summary/{farmer_id}', [FeedingController::class, 'summary']);
});

Route::prefix('dairy')->group(function () {
    Route::post('/', [DairyController::class, 'store']);
    Route::get('/list/{farmer_id}', [DairyController::class, 'index']);
    Route::get('/payments/{farmer_id}', [DairyController::class, 'payments']);
});

Route::prefix('reproductive')->group(function () {
    Route::post('/', [ReproductiveController::class, 'store']);
    Route::get('/list/{farmer_id}', [ReproductiveController::class, 'index']);
});

Route::prefix('health')->group(function () {
    Route::get('/medical/{farmer_id}', [HealthController::class, 'medicalList']);
    Route::post('/medical', [HealthController::class, 'storeMedical']);
    Route::get('/mastitis/{farmer_id}', [HealthController::class, 'mastitisList']);
    Route::post('/mastitis', [HealthController::class, 'storeMastitis']);
    Route::get('/dmi/{farmer_id}', [HealthController::class, 'dmiList']);
    Route::post('/dmi', [HealthController::class, 'storeDmi']);
});

Route::prefix('doctor')->group(function () {
    Route::get('/list', [FarmerDoctorController::class, 'index']);

    // corzin_doctor auth/profile APIs stay in DoctorApp folder controllers
    Route::post('/register', [DoctorAppController::class, 'register']);
    Route::post('/login', [DoctorAppController::class, 'login']);
    Route::post('/forgot-password', [DoctorAppController::class, 'forgotPassword']);
    Route::get('/profile/{doctor}', [DoctorAppController::class, 'profile']);
    Route::post('/profile/{doctor}/update', [DoctorAppController::class, 'updateProfile']);
    Route::post('/fcm-token/{doctor}', [DoctorAppController::class, 'updateFcmToken']);
    Route::post('/availability/{doctor}', [DoctorAppController::class, 'updateAvailability']);
    Route::post('/live-location/{doctor}', [DoctorAppController::class, 'updateLiveLocation']);
    Route::get('/locations/states', [DoctorAppLocationController::class, 'states']);
    Route::get('/locations/districts', [DoctorAppLocationController::class, 'districts']);
    Route::get('/locations/talukas', [DoctorAppLocationController::class, 'talukas']);
    Route::get('/locations/cities', [DoctorAppLocationController::class, 'cities']);

    // dairycorzin doctor APIs use Api\Doctor folder controllers
    Route::post('/appointments', [FarmerDoctorAppointmentController::class, 'store']);
    Route::get('/appointments/farmer/{farmer}', [FarmerDoctorAppointmentController::class, 'indexByFarmer']);
    Route::get('/appointments/{doctor}', [FarmerDoctorAppointmentController::class, 'indexByDoctor']);
    Route::post('/appointments/{appointment}/farmer-approval', [FarmerDoctorAppointmentController::class, 'farmerApproval']);
    Route::get('/settings', [FarmerDoctorSettingController::class, 'show']);
    Route::get('/diseases', [FarmerDoctorSettingController::class, 'diseases']);

    // corzin_doctor appointment actions use DoctorApp folder controllers
    Route::post('/appointments/{appointment}/propose', [DoctorAppAppointmentController::class, 'propose']);
    Route::post('/appointments/{appointment}/complete', [DoctorAppAppointmentController::class, 'complete']);
    Route::post('/appointments/{appointment}/doctor-decision', [DoctorAppAppointmentController::class, 'doctorDecision']);
    Route::post('/appointments/{appointment}/verify-otp', [DoctorAppAppointmentController::class, 'verifyOtp']);
    Route::post('/appointments/{appointment}/start-treatment', [DoctorAppAppointmentController::class, 'startTreatment']);
    Route::post('/appointments/{appointment}/treatment', [DoctorAppAppointmentController::class, 'updateTreatment']);
    Route::get('/appointments/{appointment}/continuation-animals', [DoctorAppAppointmentController::class, 'continuationAnimals']);
    Route::post('/appointments/{appointment}/continue', [DoctorAppAppointmentController::class, 'continueWithAnimal']);
    Route::post('/appointments/{appointment}/cancel-followup', [FarmerDoctorAppointmentController::class, 'cancelFollowup']);
    Route::post('/appointments/{appointment}/live-location', [DoctorAppAppointmentController::class, 'updateLiveLocation']);
});

Route::prefix('doctor-app')->group(function () {
    Route::get('/list', [DoctorAppController::class, 'index']);
    Route::post('/register', [DoctorAppController::class, 'register']);
    Route::post('/login', [DoctorAppController::class, 'login']);
    Route::post('/forgot-password', [DoctorAppController::class, 'forgotPassword']);
    Route::get('/profile/{doctor}', [DoctorAppController::class, 'profile']);
    Route::post('/profile/{doctor}/update', [DoctorAppController::class, 'updateProfile']);
    Route::post('/fcm-token/{doctor}', [DoctorAppController::class, 'updateFcmToken']);
    Route::post('/availability/{doctor}', [DoctorAppController::class, 'updateAvailability']);
    Route::post('/live-location/{doctor}', [DoctorAppController::class, 'updateLiveLocation']);
    Route::get('/locations/states', [DoctorAppLocationController::class, 'states']);
    Route::get('/locations/districts', [DoctorAppLocationController::class, 'districts']);
    Route::get('/locations/talukas', [DoctorAppLocationController::class, 'talukas']);
    Route::get('/locations/cities', [DoctorAppLocationController::class, 'cities']);
    Route::post('/appointments', [DoctorAppAppointmentController::class, 'store']);
    Route::get('/appointments/farmer/{farmer}', [DoctorAppAppointmentController::class, 'indexByFarmer']);
    Route::get('/appointments/{doctor}', [DoctorAppAppointmentController::class, 'indexByDoctor']);
    Route::post('/appointments/{appointment}/propose', [DoctorAppAppointmentController::class, 'propose']);
    Route::post('/appointments/{appointment}/complete', [DoctorAppAppointmentController::class, 'complete']);
    Route::post('/appointments/{appointment}/doctor-decision', [DoctorAppAppointmentController::class, 'doctorDecision']);
    Route::post('/appointments/{appointment}/farmer-approval', [DoctorAppAppointmentController::class, 'farmerApproval']);
    Route::post('/appointments/{appointment}/verify-otp', [DoctorAppAppointmentController::class, 'verifyOtp']);
    Route::post('/appointments/{appointment}/start-treatment', [DoctorAppAppointmentController::class, 'startTreatment']);
    Route::post('/appointments/{appointment}/treatment', [DoctorAppAppointmentController::class, 'updateTreatment']);
    Route::get('/appointments/{appointment}/continuation-animals', [DoctorAppAppointmentController::class, 'continuationAnimals']);
    Route::post('/appointments/{appointment}/continue', [DoctorAppAppointmentController::class, 'continueWithAnimal']);
    Route::post('/appointments/{appointment}/cancel-followup', [DoctorAppAppointmentController::class, 'cancelFollowup']);
    Route::post('/appointments/{appointment}/live-location', [DoctorAppAppointmentController::class, 'updateLiveLocation']);
    Route::get('/settings', [DoctorAppSettingController::class, 'show']);
    Route::get('/diseases', [DoctorAppSettingController::class, 'diseases']);
});

Route::prefix('subscription')->group(function () {
    Route::get('/plans', [SubscriptionController::class, 'plans']);
});

Route::prefix('shop')->group(function () {
    Route::get('/categories', [ShopController::class, 'categories']);
    Route::get('/products', [ShopController::class, 'products']);
    Route::post('/prescription-products', [ShopController::class, 'prescriptionProducts']);
    Route::post('/orders', [ShopController::class, 'placeOrder']);
    Route::get('/orders/farmer/{farmer}', [ShopController::class, 'farmerOrders']);
});

Route::prefix('web-push')->group(function () {
    Route::post('/register-token', [WebPushTokenController::class, 'register']);
});
