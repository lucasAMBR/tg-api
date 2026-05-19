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
        Schema::create('company_profile_language', function (Blueprint $table) {
            $table->foreignUuid('language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignUuid('company_profile_id')->constrained('company_profiles')->cascadeOnDelete();

            $table->primary(['language_id', 'company_profile_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_profile_language');
    }
};
