<?php

use App\Http\Controllers\TodoController;
use App\Models\Employee;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\DependentDropdownController;
Route::get('/study-cases/dependent-dropdown', [DependentDropdownController::class, 'index'])->name('dependent-dropdown.index');
Route::get('/api/cities', [DependentDropdownController::class, 'getCities'])->name('api.cities');
Route::get('/api/districts', [DependentDropdownController::class, 'getDistricts'])->name('api.districts');
Route::post('/study-cases/dependent-dropdown', [DependentDropdownController::class, 'store'])->name('dependent-dropdown.store');


Route::get('/employee', function () {
    $employees = Employee::with(['province', 'city', 'district'])->get();
    return $employees;
});