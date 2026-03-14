<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Superadmin desde variables de entorno
        $email = env('TASKLAB_SUPERADMIN_EMAIL', 'nerea@founderz.com');
        $name  = env('TASKLAB_SUPERADMIN_NAME', 'Nerea');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make(env('TASKLAB_SUPERADMIN_PASSWORD', 'password')),
                'is_super_admin'    => true,
                'is_admin'          => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
