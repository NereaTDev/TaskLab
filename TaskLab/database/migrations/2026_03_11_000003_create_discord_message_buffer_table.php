<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discord_message_buffer', function (Blueprint $table) {
            $table->id();
            $table->string('discord_user_id');
            $table->string('channel_id');
            $table->string('message_id')->unique();
            $table->text('message_text');
            $table->string('message_url')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('team_name')->nullable();
            $table->string('channel_name')->nullable();
            $table->json('attachments')->nullable();
            $table->json('image_urls')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['discord_user_id', 'channel_id', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_message_buffer');
    }
};
