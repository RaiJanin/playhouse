<?php

use Illuminate\Support\Facades\Route;

use App\Events\MonitoringUpdates;

Route::get('/test-broadcast', function () {
    broadcast(new MonitoringUpdates(['test' => true]));
});