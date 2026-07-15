<?php

use App\Http\Controllers\InformationsController;
use App\Http\Controllers\PlayHouseController;
use App\Http\Controllers\TurnstileController;
use App\Http\Controllers\MimoAdminController;
use App\Http\Controllers\PaymentsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API routes for json requests, get/submit requests only

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/submit/whole-form', [PlayHouseController::class, 'store']);
Route::post('/submit/make-otp', [PlayHouseController::class, 'makeOtp']);
Route::get('/check-phone/{phoneNum}', [PlayHouseController::class, 'checkPhone']);
Route::patch('/verify-otp/{phoneNum}', [PlayHouseController::class, 'verifyOTP']);
Route::delete('/delete-otp/{otpId}', [PlayHouseController::class, 'deleteOtp']);
Route::get('/search-returnee/{phoneNumber}', [PlayHouseController::class, 'searchReturnee'])->name('returnee.search');
Route::get('/get-orders', [PlayHouseController::class, 'getOrders']);
Route::get('/order-items/{id}', [PlayHouseController::class, 'getOrderItem']);
Route::patch('/order-items/{id}', [PlayHouseController::class, 'updateOrderItem']);
Route::patch('/check-out/{orderNum}', [PlayHouseController::class, 'checkOut']);
Route::get('/order-items/{id}/payment-details', [PaymentsController::class, 'details']);
Route::patch('/order-items/{id}/pay', [PaymentsController::class, 'pay']);
Route::patch('/order-items/{id}/cancel-checkout', [PaymentsController::class, 'cancelCheckout']);
Route::post('/turnstile-srch', [TurnstileController::class, 'turnstileSrchPOST']);
Route::get('/get-contact', [InformationsController::class, 'getContact']);
Route::get('/get-inhouse', [MimoAdminController::class, 'monitoring']);
