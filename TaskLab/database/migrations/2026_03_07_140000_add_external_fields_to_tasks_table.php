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
            $table->string('external_message_id')->nullable()->after('estimated_effort');
            $table->string('external_channel')->nullable()->after('external_message_id');
            $table->string('external_user_id')->nullable()->after('external_channel');
            $table->json('external_payload')->nullable()->after('external_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'external_message_id',
                'external_channel',
                'external_user_id',
                'external_payload',
            ]);
        });
    }
};
