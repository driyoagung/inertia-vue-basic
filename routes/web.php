<?php

use App\Http\Controllers\LargeOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/large-orders', [LargeOrderController::class, 'index'])->name('large-orders.index');
