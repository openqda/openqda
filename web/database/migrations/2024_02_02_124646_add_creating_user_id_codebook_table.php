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
            $table->foreignId('creating_user_id')->constrained('users');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('codebooks', function (Blueprint $table) {
            $table->dropForeign(['creating_user_id']);
            $table->dropColumn(['creating_user_id']);
        });
    }
};
