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

        Schema::table('codebooks', function (Blueprint $table) {
            $table->json('properties')->nullable(); // Add a JSON column for properties
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('codebooks', function (Blueprint $table) {
            $table->dropColumn('properties'); // Remove the properties column
        });
    }
};
