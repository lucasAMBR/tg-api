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
        Schema::create('project_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('language_project_history', function (Blueprint $table) {
            $table->foreignUuid('language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignUuid('project_history_id')->constrained('project_histories')->cascadeOnDelete();
            $table->primary(['language_id', 'project_history_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('language_project_history');
        Schema::dropIfExists('project_history_images');
        Schema::dropIfExists('project_histories');
    }
};
