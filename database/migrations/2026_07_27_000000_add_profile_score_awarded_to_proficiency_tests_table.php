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
        Schema::table('proficiency_tests', function (Blueprint $table) {
            $table->integer('profile_score_awarded')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proficiency_tests', function (Blueprint $table) {
            $table->dropColumn('profile_score_awarded');
        });
    }
};
