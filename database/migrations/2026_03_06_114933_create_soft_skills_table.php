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
        Schema::create('soft_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('description');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('soft_skill_level_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('soft_skill_id')->constrained('soft_skills')->cascadeOnDelete();
            $table->string('title');
            $table->string('description');
            $table->tinyInteger('evaluation_weight')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('dev_soft_skill', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('soft_skill_id')->constrained('soft_skills');
            $table->foreignUuid('soft_skill_level_response_id')->constrained('soft_skill_level_responses');
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soft_skills');
    }
};
