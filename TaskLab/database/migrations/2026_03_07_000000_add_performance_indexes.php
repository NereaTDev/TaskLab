<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('assignee_id');
            $table->index('reporter_id');
            $table->index('status');
            $table->index('priority');
            $table->index(['assignee_id', 'status']); // usado en cálculo de carga de devs
        });

        Schema::table('user_category_assignments', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('category_value_id');
        });

        Schema::table('task_category_values', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('category_value_id');
        });

        Schema::table('developer_profiles', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['assignee_id']);
            $table->dropIndex(['reporter_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['assignee_id', 'status']);
        });

        Schema::table('user_category_assignments', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['category_value_id']);
        });

        Schema::table('task_category_values', function (Blueprint $table) {
            $table->dropIndex(['task_id']);
            $table->dropIndex(['category_value_id']);
        });

        Schema::table('developer_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['active']);
        });
    }
};
