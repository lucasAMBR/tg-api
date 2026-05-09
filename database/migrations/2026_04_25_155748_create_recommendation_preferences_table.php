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
        Schema::create('recommendation_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean("allow_clt")->default(true);
            $table->boolean('allow_contractor')->default(true);
            $table->boolean('allow_internship')->default(false);
            $table->boolean('allow_on_site')->default(true);
            $table->boolean('allow_hybrid')->default(true);
            $table->boolean('allow_remote')->default(true);
            $table->integer('on_site_job_radius')->default(20);
            $table->integer("hybrid_jobs_radius")->default(40);
            $table->boolean('allow_stack_flexibility')->default(true);
            $table->decimal('min_remuneration', 10, 2)->nullable();
            $table->foreignUuid('dev_profile_id')->unique()->constrained('dev_profiles')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('recommendation_preferences_black_list', function (Blueprint $table) {
            $table->foreignUuid('language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignUuid('recommendation_preference_id')->constrained('recommendation_preferences')->cascadeOnDelete();
            $table->primary(['language_id', 'recommendation_preference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendation_preferences_black_list');
        Schema::dropIfExists('recommendation_preferences');
    }
};
