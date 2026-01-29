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
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            // Drop old unique(user_id)
            $table->dropUnique('user_preferences_user_id_unique');

            $table->foreignId('project_id')
                ->after('user_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->json('sources')->nullable()->after('project_id');
            $table->json('zoom')->nullable()->after('sources');
            $table->json('codebooks')->nullable()->after('zoom');
            $table->json('analysis')->nullable()->after('codebooks');

            $table->foreignId('user_id')
                ->change()
                ->constrained('users')
                ->cascadeOnDelete();

            // New unique key
            $table->unique(['user_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {

            // Drop new unique constraint
            $table->dropUnique(['user_id', 'project_id']);

            // Drop columns (and FK)
            $table->dropForeign(['project_id']);
            $table->dropColumn(['project_id', 'sources', 'zoom', 'codebooks', 'analysis']);

            // Restore old unique(user_id)
            $table->unique('user_id');
        });
    }
};
