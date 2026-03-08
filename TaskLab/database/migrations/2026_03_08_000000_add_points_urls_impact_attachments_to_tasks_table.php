<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // URL principal donde ocurre el problema o vive la feature
            $table->string('primary_url')->nullable()->after('source');

            // Otras URLs relevantes mencionadas (json array de strings)
            $table->json('additional_urls')->nullable()->after('primary_url');

            // Impacto en negocio/usuarios (texto corto)
            $table->text('impact')->nullable()->after('description_ai');

            // Puntos de esfuerzo (horas), valores discretos como 0.5, 1, 2, 4, 6, 8, 10, 12, 16
            $table->decimal('points', 5, 1)->nullable()->after('priority');

            // Adjuntos (json array con metadatos de imágenes, vídeos, etc.)
            $table->json('attachments')->nullable()->after('external_payload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'primary_url',
                'additional_urls',
                'impact',
                'points',
                'attachments',
            ]);
        });
    }
};
