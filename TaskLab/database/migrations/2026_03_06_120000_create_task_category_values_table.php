<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_category_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();

            $table->foreignId('category_value_id')
                ->constrained('category_values')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['task_id', 'category_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_category_values');
    }
};
