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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->string('title')->nullable();
            $table->text('description_raw');
            $table->text('description_ai')->nullable();
            $table->json('requirements')->nullable();
            $table->text('behavior')->nullable();
            $table->json('test_cases')->nullable();

            $table->enum('type', ['bug', 'feature', 'improvement', 'question'])->default('bug');
            $table->enum('status', ['new', 'in_refinement', 'ready_for_dev', 'in_progress', 'done', 'blocked'])->default('new');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');

            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('source')->default('web_form');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
