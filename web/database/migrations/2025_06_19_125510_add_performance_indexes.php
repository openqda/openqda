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
        Schema::table('sources', function (Blueprint $table) {
            if (! $this->indexExists('sources', 'idx_sources_project_id')) {
                $table->index('project_id', 'idx_sources_project_id');
            }
            if (! $this->indexExists('sources', 'idx_sources_creating_user_id')) {
                $table->index('creating_user_id', 'idx_sources_creating_user_id');
            }
            if (! $this->indexExists('sources', 'idx_sources_project_type')) {
                $table->index(['project_id', 'type'], 'idx_sources_project_type');
            }
            if (! $this->indexExists('sources', 'idx_sources_created_at')) {
                $table->index('created_at', 'idx_sources_created_at');
            }
        });

        Schema::table('selections', function (Blueprint $table) {
            if (! $this->indexExists('selections', 'idx_selections_source_id')) {
                $table->index('source_id', 'idx_selections_source_id');
            }
            if (! $this->indexExists('selections', 'idx_selections_code_id')) {
                $table->index('code_id', 'idx_selections_code_id');
            }
            if (! $this->indexExists('selections', 'idx_selections_source_code')) {
                $table->index(['source_id', 'code_id'], 'idx_selections_source_code');
            }
            if (! $this->indexExists('selections', 'idx_selections_project_user')) {
                $table->index(['project_id', 'creating_user_id'], 'idx_selections_project_user');
            }
            if (! $this->indexExists('selections', 'idx_selections_position')) {
                $table->index(['source_id', 'start_position', 'end_position'], 'idx_selections_position');
            }
        });

        Schema::table('codes', function (Blueprint $table) {
            if (! $this->indexExists('codes', 'idx_codes_codebook_id')) {
                $table->index('codebook_id', 'idx_codes_codebook_id');
            }
            if (! $this->indexExists('codes', 'idx_codes_parent_id')) {
                $table->index('parent_id', 'idx_codes_parent_id');
            }
            if (! $this->indexExists('codes', 'idx_codes_codebook_parent')) {
                $table->index(['codebook_id', 'parent_id'], 'idx_codes_codebook_parent');
            }
        });

        Schema::table('audits', function (Blueprint $table) {
            if (! $this->indexExists('audits', 'idx_audits_type_id')) {
                $table->index(['auditable_type', 'auditable_id'], 'idx_audits_type_id');
            }
            if (! $this->indexExists('audits', 'idx_audits_user_created')) {
                $table->index(['user_id', 'created_at'], 'idx_audits_user_created');
            }
            if (! $this->indexExists('audits', 'idx_audits_type_created')) {
                $table->index(['auditable_type', 'created_at'], 'idx_audits_type_created');
            }
        });

        Schema::table('codebooks', function (Blueprint $table) {
            if (! $this->indexExists('codebooks', 'idx_codebooks_project_id')) {
                $table->index('project_id', 'idx_codebooks_project_id');
            }
            if (! $this->indexExists('codebooks', 'idx_codebooks_creating_user_id')) {
                $table->index('creating_user_id', 'idx_codebooks_creating_user_id');
            }
        });
    }

    private function indexExists($table, $indexName)
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = $connection->select('
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = ? 
            AND table_name = ? 
            AND index_name = ?
        ', [$databaseName, $table, $indexName]);

        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            $table->dropIndex('idx_sources_project_id');
            $table->dropIndex('idx_sources_creating_user_id');
            $table->dropIndex('idx_sources_project_type');
            $table->dropIndex('idx_sources_created_at');
        });

        Schema::table('selections', function (Blueprint $table) {
            $table->dropIndex('idx_selections_source_id');
            $table->dropIndex('idx_selections_code_id');
            $table->dropIndex('idx_selections_source_code');
            $table->dropIndex('idx_selections_project_user');
            $table->dropIndex('idx_selections_position');
        });

        Schema::table('codes', function (Blueprint $table) {
            $table->dropIndex('idx_codes_codebook_id');
            $table->dropIndex('idx_codes_parent_id');
            $table->dropIndex('idx_codes_codebook_parent');
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->dropIndex('idx_audits_type_id');
            $table->dropIndex('idx_audits_user_created');
            $table->dropIndex('idx_audits_type_created');
        });

        Schema::table('codebooks', function (Blueprint $table) {
            $table->dropIndex('idx_codebooks_project_id');
            $table->dropIndex('idx_codebooks_creating_user_id');
        });
    }
};
