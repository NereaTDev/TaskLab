<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Solo necesario en PostgreSQL: Laravel crea una CHECK constraint para enum().
        // SQLite no soporta DROP CONSTRAINT y tampoco la crea, así que lo saltamos.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_status_check");
            DB::statement("ALTER TABLE tasks ALTER COLUMN status TYPE varchar(255)");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE tasks ALTER COLUMN status TYPE varchar(255)");
            DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_status_check CHECK (status IN ('new', 'ready_for_dev', 'in_progress', 'done', 'blocked'))");
        }
    }
};
