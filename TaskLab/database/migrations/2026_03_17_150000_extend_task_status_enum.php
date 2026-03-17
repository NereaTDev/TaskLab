<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // En PostgreSQL, Laravel genera una CHECK constraint para enum().
        // La convertimos a varchar libre para soportar todos los estados actuales:
        // new, ready_for_dev, in_progress, done, blocked, archived, needs_review, processing
        DB::statement("ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_status_check");
        DB::statement("ALTER TABLE tasks ALTER COLUMN status TYPE varchar(255)");
    }

    public function down(): void
    {
        // Restaurar solo los valores originales (datos con otros estados quedarán inválidos)
        DB::statement("ALTER TABLE tasks ALTER COLUMN status TYPE varchar(255)");
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_status_check CHECK (status IN ('new', 'ready_for_dev', 'in_progress', 'done', 'blocked'))");
    }
};
