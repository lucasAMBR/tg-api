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
        Schema::create('employment_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('company_name');
            $table->string('company_location');
            $table->string('position_name');
            $table->string('employment_type');
            $table->string('contract_type');
            $table->string('seniority_level');
            $table->text('actuation_details');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current');
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employment_histories');
    }
};
