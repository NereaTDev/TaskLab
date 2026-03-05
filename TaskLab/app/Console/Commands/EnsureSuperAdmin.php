<?php

namespace App\\Console\\Commands;

use App\\Models\\User;
use Illuminate\\Console\\Command;
use Illuminate\\Support\\Str;

class EnsureSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasklab:ensure-super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure the configured Super Admin user exists and has full privileges.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = config('tasklab.super_admin_email');

        if (! $email) {
            $this->error('TASKLAB_SUPERADMIN_EMAIL is not set. Please configure it in your .env file.');
            return self::FAILURE;
        }

        $name = config('tasklab.super_admin_name', 'Super Admin');

        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if ($user) {
            // Refuerza flags de super admin
            $user->is_admin = true;
            $user->is_super_admin = true;
            if (! $user->user_type) {
                $user->user_type = 'developer';
            }
            $user->save();

            $this->info("Super Admin found and updated: {$email}");

            return self::SUCCESS;
        }

        // Si no existe, lo creamos con una contraseña aleatoria.
        $password = Str::random(32);

        $user = User::create([
            'name'      => $name,
            'email'     => $email,
            'password'  => $password,
            'user_type' => 'developer',
            'is_admin'  => true,
        ]);

        // Guardamos flags extra fuera de fillable
        $user->is_super_admin = true;
        $user->save();

        $this->info("Super Admin created: {$email}");
        $this->line('A random password was generated. Use the password reset flow to set a real password.');

        return self::SUCCESS;
    }
}
