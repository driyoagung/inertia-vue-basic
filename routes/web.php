<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/projects/multi-select', [ProjectController::class, 'index'])->name('projects.multi-select');
Route::post('/projects/multi-select', [ProjectController::class, 'store'])->name('projects.multi-select.store');
