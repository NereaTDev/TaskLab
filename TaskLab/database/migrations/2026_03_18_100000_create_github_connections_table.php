<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_connections', function (Blueprint $table) {
            $table->id();
            $table->string('owner');                    // GitHub username or org
            $table->string('repo');                     // Repository name
            $table->text('token');                      // Encrypted Personal Access Token
            $table->string('branch')->default('main');  // Default branch to read from
            $table->boolean('active')->default(true);
            $table->json('file_tree')->nullable();       // Cached flat array of file paths
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_connections');
    }
};
