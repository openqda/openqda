<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (
            Schema::hasTable('user_preferences')
            && ! Schema::hasTable('user_project_preferences')
        ) {
            Schema::rename('user_preferences', 'user_project_preferences');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            Schema::hasTable('user_project_preferences')
            && ! Schema::hasTable('user_preferences')
        ) {
            Schema::rename('user_project_preferences', 'user_preferences');
        }
    }
};
