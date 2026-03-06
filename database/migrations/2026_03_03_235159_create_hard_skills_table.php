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
        Schema::create('hard_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->foreignUuid('language_id')->constrained('languages')->restrictOnDelete();
            $table->string('skill_level');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hard_skills');
    }
};
