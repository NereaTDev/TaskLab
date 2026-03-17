<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['area', 'estimated_effort', 'external_payload']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('area')->nullable();
            $table->string('estimated_effort')->default('medium');
            $table->json('external_payload')->nullable();
        });
    }
};
