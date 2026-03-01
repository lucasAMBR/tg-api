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
        Schema::create('company_projects', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->timestamps();
            $table->string('title');
            $table->text('description');
            $table->softDeletes();
            $table->foreignUuid('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();

        });

        Schema::create('language_company_project', function(Blueprint $table) {

            $table->foreignUuid('company_project_id')->constrained('company_projects')->cascadeOnDelete();
            $table->foreignUuid('language_id')->constrained('languages')->cascadeOnDelete();
            $table->primary(['language_id', 'company_project_id']);

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_projects');
        Schema::dropIfExists('language_company_project');
    }
};
