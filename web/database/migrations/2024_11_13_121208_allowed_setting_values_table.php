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
        Schema::create('allowed_setting_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // We'll store the JSON path like 'display.theme' or 'notifications.type'
            $table->string('setting_key');
            $table->string('value');      // The allowed value itself
            $table->string('caption');    // Human readable label
            $table->timestamps();

            // Ensure unique combinations of key and value
            $table->unique(['setting_key', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
