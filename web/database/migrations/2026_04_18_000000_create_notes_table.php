<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('project_id');
            $table->string('target')->nullable();
            $table->integer('visibility')->default(0);
            $table->string('type');
            $table->string('scope')->nullable();
            $table->foreignId('creating_user_id');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('creating_user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['scope', 'target']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
