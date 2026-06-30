<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slack_connections', function (Blueprint $table) {
            $table->id();
            $table->string('workspace_id')->nullable();
            $table->string('workspace_name')->nullable();
            $table->text('bot_token')->nullable();       // encrypted
            $table->text('signing_secret')->nullable();  // encrypted — verifica signatures de eventos
            $table->json('channel_ids')->nullable();     // IDs de canales a escuchar (vacío = todos)
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slack_connections');
    }
};
