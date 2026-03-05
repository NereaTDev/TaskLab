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
            // Area where this task belongs: web, plataforma, frontierz, dashboard_empresas
            $table->string('area')->nullable()->after('source');

            // Rough effort estimate for assignment: low, medium, high
            $table->string('estimated_effort')->default('medium')->after('area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('area');
            $table->dropColumn('estimated_effort');
        });
    }
};
