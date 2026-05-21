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
            $table->string('i18n_name_key')->nullable()->after('name');
            $table->string('i18n_description_key')->nullable()->after('description');
        });

        Schema::table('soft_skill_level_responses', function (Blueprint $table) {
            $table->string('i18n_title_key')->nullable()->after('title');
            $table->string('i18n_description_key')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('soft_skill_level_responses', function (Blueprint $table) {
            $table->dropColumn(['i18n_title_key', 'i18n_description_key']);
        });

        Schema::table('soft_skills', function (Blueprint $table) {
            $table->dropColumn(['i18n_name_key', 'i18n_description_key']);
        });
    }
};
