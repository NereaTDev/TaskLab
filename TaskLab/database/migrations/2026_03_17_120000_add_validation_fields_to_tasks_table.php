<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->json('rejection_reasons')->nullable()->after('attachments');
            $table->json('co_requester_ids')->nullable()->after('rejection_reasons');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['rejection_reasons', 'co_requester_ids']);
        });
    }
};
