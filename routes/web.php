<?php

use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/study-cases/tanstack-table', [App\Http\Controllers\EmployeeController::class, 'index'])->name('study-cases.tanstack-table');
