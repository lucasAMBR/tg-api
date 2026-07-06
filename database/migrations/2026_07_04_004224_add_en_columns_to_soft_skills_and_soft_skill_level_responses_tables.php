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
        Schema::table('soft_skills', function (Blueprint $table) {
            $table->string('name_pt')->nullable()->after('name');
            $table->string('description_pt')->nullable()->after('description');
        });

        Schema::table('soft_skill_level_responses', function (Blueprint $table) {
            $table->string('title_pt')->nullable()->after('title');
            $table->string('description_pt')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soft_skill_level_responses', function (Blueprint $table) {
            $table->dropColumn(['title_pt', 'description_pt']);
        });

        Schema::table('soft_skills', function (Blueprint $table) {
            $table->dropColumn(['name_pt', 'description_pt']);
        });
    }
};
