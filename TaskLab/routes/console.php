<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

// Limpieza diaria de imágenes de tareas completadas hace más de 30 días
Schedule::command('tasklab:cleanup-task-images')->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tasklab:ensure-super-admin', function () {
    $email = config('tasklab.super_admin_email');

    if (! $email) {
        $this->error('TASKLAB_SUPERADMIN_EMAIL is not set. Please configure it in your .env file.');
        return self::FAILURE;
    }

    $name = config('tasklab.super_admin_name', 'Super Admin');

    /** @var User|null $user */
    $user = User::where('email', $email)->first();

    // Si hay contraseña configurada en .env, la usamos
    $rawPassword = config('tasklab.super_admin_password');
    $hashedPassword = $rawPassword ? bcrypt($rawPassword) : null;

    if ($user) {
        $user->is_admin = true;
        $user->is_super_admin = true;
        if (! $user->user_type) {
            $user->user_type = 'developer';
        }

        if ($hashedPassword) {
            $user->password = $hashedPassword;
        }

        $user->save();

        $this->info("Super Admin found and updated: {$email}");
        if ($hashedPassword) {
            $this->line('Super Admin password updated from TASKLAB_SUPERADMIN_PASSWORD.');
        }

        return self::SUCCESS;
    }

    // Si no existe, lo creamos. Si tenemos password en .env, la usamos; si no, generamos una aleatoria.
    if (! $hashedPassword) {
        $generated = Str::random(32);
        $hashedPassword = bcrypt($generated);
        $this->line('A random password was generated. Use the password reset flow or set TASKLAB_SUPERADMIN_PASSWORD to control it.');
    }

    $user = User::create([
        'name'      => $name,
        'email'     => $email,
        'password'  => $hashedPassword,
        'user_type' => 'developer',
        'is_admin'  => true,
    ]);

    $user->is_super_admin = true;
    $user->save();

    $this->info("Super Admin created: {$email}");

    return self::SUCCESS;
})->purpose('Ensure the configured Super Admin user exists and has full privileges.');
