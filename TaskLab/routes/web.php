<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamsIntegrationController;
use App\Http\Controllers\DiscordIntegrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('tasks.index');
});

// Endpoint público para integraciones de Teams (protegido por token propio)
Route::post('/integrations/teams/messages', [TeamsIntegrationController::class, 'store'])
    ->name('integrations.teams.messages');

// Endpoint público para integraciones de Discord (protegido por el mismo token)
Route::post('/integrations/discord/messages', [DiscordIntegrationController::class, 'store'])
    ->name('integrations.discord.messages');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('tasks.index');
    })->name('dashboard');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Team management (admins only, enforced in controller for now)
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::post('/team/reassign-department', [TeamController::class, 'reassignDepartment'])->name('team.reassign-department');
    Route::post('/team/reassign-category', [TeamController::class, 'reassignCategory'])->name('team.reassign-category');
    Route::post('/team/update-role', [TeamController::class, 'updateUserRole'])->name('team.users.update-role');

    // Configuración (solo Super Admin, enforcement en controller)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');

    // Departamentos legacy
    Route::post('/settings/departments', [SettingsController::class, 'storeDepartment'])->name('settings.departments.store');
    Route::patch('/settings/departments/{department}', [SettingsController::class, 'updateDepartment'])->name('settings.departments.update');
    Route::delete('/settings/departments/{department}', [SettingsController::class, 'destroyDepartment'])->name('settings.departments.destroy');

    // Categorías genéricas
    Route::post('/settings/category-types', [SettingsController::class, 'storeCategoryType'])->name('settings.category-types.store');
    Route::delete('/settings/category-types/{categoryType}', [SettingsController::class, 'destroyCategoryType'])->name('settings.category-types.destroy');

    Route::post('/settings/category-types/{categoryType}/values', [SettingsController::class, 'storeCategoryValue'])->name('settings.category-values.store');
    Route::patch('/settings/category-values/{categoryValue}', [SettingsController::class, 'updateCategoryValue'])->name('settings.category-values.update');
    Route::delete('/settings/category-values/{categoryValue}', [SettingsController::class, 'destroyCategoryValue'])->name('settings.category-values.destroy');
});

require __DIR__.'/auth.php';
