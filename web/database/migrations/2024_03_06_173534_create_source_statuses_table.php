<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('source_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('source_id')->constrained('sources');
            $table->text('status');
            $table->string('path')->nullable(); // Allow optional paths
            $table->timestamps(); // Add standard created_at and updated_at fields
        });
    }

    public function down()
    {
        Schema::dropIfExists('source_statuses');
    }
};
