<?php

return [
    // Email del usuario Super Admin. Debes configurarlo en .env
    // ej: TASKLAB_SUPERADMIN_EMAIL="ash3@tuempresa.com"
    'super_admin_email' => env('TASKLAB_SUPERADMIN_EMAIL'),

    // Nombre a usar cuando se cree automáticamente el Super Admin
    'super_admin_name' => env('TASKLAB_SUPERADMIN_NAME', 'Super Admin'),

    // (Opcional, pero útil en dev) Contraseña del Super Admin. Si se define,
    // el comando tasklab:ensure-super-admin la aplicará al usuario SA.
    'super_admin_password' => env('TASKLAB_SUPERADMIN_PASSWORD'),
];
