<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TaskController;

Route::get('/', function () {
    return redirect()->route('tasks.index');
});

Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
