<?php

use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\KanbanTaskController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/kanban', [KanbanTaskController::class, 'index'])->name('kanban.index');
Route::post('/kanban/reorder', [KanbanTaskController::class, 'reorder'])->name('kanban.reorder');
