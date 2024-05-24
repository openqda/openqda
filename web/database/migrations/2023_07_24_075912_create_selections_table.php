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
        Schema::create('selections', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('(UUID())'));
            $table->primary('id');
            $table->text('text');
            $table->text('description')->nullable();
            $table->string('start_position')->nullable();
            $table->string('end_position')->nullable();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('creating_user_id')->constrained('users');
            $table->foreignId('modifying_user_id')->nullable()->constrained('users');
            $table->foreignUuid('code_id')->constrained('codes');
            $table->foreignUuid('source_id')->constrained('sources');
            $table->timestamps();

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selections');
    }
};
