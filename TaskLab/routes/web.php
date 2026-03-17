<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskImageController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamsIntegrationController;
use App\Http\Controllers\DiscordIntegrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('tasks.index');
});

// Endpoints públicos de integraciones (protegidos por token propio)
Route::post('/integrations/teams/messages', [TeamsIntegrationController::class, 'store'])
    ->name('integrations.teams.messages');
Route::post('/integrations/discord/messages', [DiscordIntegrationController::class, 'store'])
    ->name('integrations.discord.messages');
Route::post('/integrations/discord/inspect', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'headers'     => $request->headers->all(),
        'body'        => $request->all(),
        'raw_keys'    => array_keys($request->all()),
        'attachments' => $request->input('attachments', 'KEY_NOT_PRESENT'),
        'embeds'      => $request->input('embeds', 'KEY_NOT_PRESENT'),
        'message'     => $request->input('message', 'KEY_NOT_PRESENT'),
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', fn () => redirect()->route('tasks.index', ['view' => 'dashboard']))->name('dashboard');

    // Página de espera de asignación de equipo (accesible sin equipo)
    Route::get('/pending-team', fn () => view('pending-team'))->name('pending-team');

    // ─── Rutas que requieren equipo ───────────────────────────────────────────
    Route::middleware('team.required')->group(function () {

        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
        Route::post('/tasks/{task}/images', [TaskImageController::class, 'store'])->name('tasks.images.store');
        Route::delete('/tasks/{task}/images/{image}', [TaskImageController::class, 'destroy'])->name('tasks.images.destroy');

        // Equipo legacy (admins)
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::post('/team/reassign-department', [TeamController::class, 'reassignDepartment'])->name('team.reassign-department');
        Route::post('/team/reassign-category', [TeamController::class, 'reassignCategory'])->name('team.reassign-category');
        Route::post('/team/update-role', [TeamController::class, 'updateUserRole'])->name('team.users.update-role');

        // Configuración (solo SuperAdmin, enforcement en controller)
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/departments', [SettingsController::class, 'storeDepartment'])->name('settings.departments.store');
        Route::patch('/settings/departments/{department}', [SettingsController::class, 'updateDepartment'])->name('settings.departments.update');
        Route::delete('/settings/departments/{department}', [SettingsController::class, 'destroyDepartment'])->name('settings.departments.destroy');
        Route::post('/settings/category-types', [SettingsController::class, 'storeCategoryType'])->name('settings.category-types.store');
        Route::delete('/settings/category-types/{categoryType}', [SettingsController::class, 'destroyCategoryType'])->name('settings.category-types.destroy');
        Route::post('/settings/category-types/{categoryType}/values', [SettingsController::class, 'storeCategoryValue'])->name('settings.category-values.store');
        Route::patch('/settings/category-values/{categoryValue}', [SettingsController::class, 'updateCategoryValue'])->name('settings.category-values.update');
        Route::delete('/settings/category-values/{categoryValue}', [SettingsController::class, 'destroyCategoryValue'])->name('settings.category-values.destroy');


    });

    // Notificaciones (accesibles sin equipo para que el user vea la notificación de pending)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Perfil (accesible sin equipo)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';
