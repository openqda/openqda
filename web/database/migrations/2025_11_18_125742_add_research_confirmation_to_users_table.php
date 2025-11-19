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
        Schema::table('users', function (Blueprint $table) {
            $table->string('research_token', 256)->nullable()->unique()->after('remember_token');
            $table->timestamp('research_requested')->nullable()->after('research_token');
            $table->timestamp('research_consent')->nullable()->after('research_requested');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('research_token');
            $table->dropColumn('research_requested');
            $table->dropColumn('research_consent');
        });
    }
};
