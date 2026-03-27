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
        Schema::create('company_soft_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('soft_skill_id')->constrained('soft_skills');
            $table->foreignUuid('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_soft_skills');
    }
};
