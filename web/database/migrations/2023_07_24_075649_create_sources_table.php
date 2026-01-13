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
        Schema::create('sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('creating_user_id')->constrained('users');
            $table->foreignId('modifying_user_id')->nullable()->constrained('users');
            $table->foreignId('project_id')->constrained('projects');
            $table->enum('type', ['text', 'picture', 'pdf', 'audio', 'video']);
            $table->string('base_path')->default('');
            $table->text('plain_text_content')->nullable();
            $table->string('plain_text_path')->nullable();
            $table->string('rich_text_path')->nullable();
            $table->string('path')->nullable();
            $table->string('current_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
