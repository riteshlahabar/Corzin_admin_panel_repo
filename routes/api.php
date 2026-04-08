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
use App\Http\Controllers\Api\DoctorApp\DoctorAppointmentController as DoctorAppAppointmentController;
use App\Http\Controllers\Api\DoctorApp\DoctorAppController;
use App\Http\Controllers\Api\DoctorApp\DoctorSettingController as DoctorAppSettingController;
use App\Http\Controllers\Api\Shop\ShopController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/check-user', [AuthController::class, 'checkUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('farmer')->group(function () {
    Route::post('/store', [FarmerController::class, 'store']);
    Route::get('/profile/{mobile}', [FarmerController::class, 'getProfileByMobile']);
    Route::post('/update/{id}', [FarmerController::class, 'update']);
});

Route::prefix('animal')->group(function () {
    Route::post('/store', [AnimalController::class, 'store']);
    Route::get('/types', [AnimalTypeController::class, 'index']);
    Route::get('/list/{farmer_id}', [AnimalController::class, 'listByFarmer']);
    Route::post('/lifecycle/{animal_id}', [AnimalController::class, 'updateLifecycle']);
    Route::get('/history/{farmer_id}', [AnimalController::class, 'history']);
});

Route::prefix('milk')->group(function () {
    Route::post('/', [MilkProductionController::class, 'store']);
    Route::get('/animal/{animal_id}', [MilkProductionController::class, 'index']);
});

Route::prefix('feeding')->group(function () {
    Route::post('/', [FeedingController::class, 'store']);
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
    Route::get('/list', [DoctorAppController::class, 'index']);
    Route::post('/register', [DoctorAppController::class, 'register']);
    Route::post('/login', [DoctorAppController::class, 'login']);
    Route::post('/forgot-password', [DoctorAppController::class, 'forgotPassword']);
    Route::get('/profile/{doctor}', [DoctorAppController::class, 'profile']);
    Route::post('/profile/{doctor}/update', [DoctorAppController::class, 'updateProfile']);
    Route::post('/fcm-token/{doctor}', [DoctorAppController::class, 'updateFcmToken']);
    Route::post('/appointments', [DoctorAppAppointmentController::class, 'store']);
    Route::get('/appointments/{doctor}', [DoctorAppAppointmentController::class, 'indexByDoctor']);
    Route::post('/appointments/{appointment}/propose', [DoctorAppAppointmentController::class, 'propose']);
    Route::post('/appointments/{appointment}/complete', [DoctorAppAppointmentController::class, 'complete']);
    Route::post('/appointments/{appointment}/doctor-decision', [DoctorAppAppointmentController::class, 'doctorDecision']);
    Route::post('/appointments/{appointment}/farmer-approval', [DoctorAppAppointmentController::class, 'farmerApproval']);
    Route::get('/settings', [DoctorAppSettingController::class, 'show']);
});

Route::prefix('shop')->group(function () {
    Route::get('/categories', [ShopController::class, 'categories']);
    Route::get('/products', [ShopController::class, 'products']);
});

