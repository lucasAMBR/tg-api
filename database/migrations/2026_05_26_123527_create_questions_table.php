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
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("question");
            $table->integer("difficulty_level");
            $table->foreignUuid("language_id")->nullable()->constrained('languages')->cascadeOnDelete();
            $table->string("category");
            $table->integer("ideal_time_to_solve")->default(0); // in seconds
            $table->jsonb("code_snippet")->nullable();
            $table->boolean("is_multiple_choice");
            $table->string("seniority_level");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
