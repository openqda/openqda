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
            $table->dropColumn('base_path');
            $table->dropColumn('plain_text_content');
            $table->dropColumn('path');
            $table->dropColumn('current_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sources', function (Blueprint $table) {
            //
        });
    }
};
