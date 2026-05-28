<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\PlayHouseController;

//WEB routes for page viewing only

Route::get('/', function () {
    return view('pages.playhouse-landing');
})->name('playhouse.start');

Route::get('/check-in-source', [PlayHouseController::class, 'checkInSource'])->name('playhouse.checkin.source');
Route::get('/registration', [PlayHouseController::class, 'registration'])->name('playhouse.registration');
Route::get('/registration/walk-in', [PlayHouseController::class, 'walkRegister'])->name('playhouse.walk-in');
Route::get('/order-info/{order_no}', [PlayHouseController::class, 'orderInfo'])->name('order.info');
Route::get('/checkout', [PlayHouseController::class, 'checkoutPage'])->name('playhouse.checkout');
Route::get('/admin', [PlayHouseController::class, 'viewBookingsOnlyNamesTimes'])->name('playhouse.bookings');
Route::get('/monitoring', function () { return view('pages.playhouse-monitoring'); })->name('playhouse.monitoring');


require __DIR__.'/http-reqs.php';
require __DIR__.'/admin-panel.php';
require __DIR__.'/auth.php';
require __DIR__.'/test-route.php';