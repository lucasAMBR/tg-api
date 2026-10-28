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
        Schema::create('proficiency_test_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('proficiency_test_id')->constrained('proficiency_tests')->onDelete('cascade');
            $table->foreignUuid('question_id')->constrained('questions')->onDelete('cascade');
            $table->foreignUuid('question_response_id')->nullable()->constrained('question_responses')->onDelete('cascade');
            $table->integer('time_taken')->nullable();
            $table->integer('alt_tabs_used')->nullable();
            $table->string('status')->default('awaiting_response');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proficiency_test_responses');
    }
};
