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
        Schema::create('proficiency_tests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->string('seniority_level');
            $table->string('specialty');
            $table->string('backend_category')->nullable();
            $table->string('frontend_category')->nullable();
            $table->string('status')->default('pending');
            $table->date('solicitation_date');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proficiency_tests');
    }
};
