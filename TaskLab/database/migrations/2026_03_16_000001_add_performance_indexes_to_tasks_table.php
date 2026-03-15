<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Filtros más usados en el tablero y dashboard
            $table->index('status');
            $table->index('archived_at');
            $table->index('source');
            // Compuesto: la query más común es status + archived_at juntos
            $table->index(['archived_at', 'status']);
            $table->index(['assignee_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['archived_at']);
            $table->dropIndex(['source']);
            $table->dropIndex(['archived_at', 'status']);
            $table->dropIndex(['assignee_id', 'archived_at']);
        });
    }
};
