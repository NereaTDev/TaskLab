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
        Schema::table('github_connections', function (Blueprint $table) {
            $table->longText('project_memory')->nullable()->after('site_url');
        });
    }

    public function down(): void
    {
        Schema::table('github_connections', function (Blueprint $table) {
            $table->dropColumn('project_memory');
        });
    }
};
