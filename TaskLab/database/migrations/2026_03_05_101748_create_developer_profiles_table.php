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
        Schema::create('developer_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Developer type: frontend, backend, fullstack
            $table->string('type');

            // Areas where this dev can work: web, plataforma, frontierz, dashboard_empresas, etc.
            $table->json('areas')->nullable();

            // Max tasks this dev can have in parallel (nullable = no hard limit)
            $table->unsignedInteger('max_parallel_tasks')->nullable();

            // Whether this dev is currently available for auto-assignment
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developer_profiles');
    }
};
