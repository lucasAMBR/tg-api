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
        Schema::create('proficiency_test_visualizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('proficiency_test_id')->constrained('proficiency_tests')->onDelete('cascade');
            $table->enum('type', ['entry', 'exit']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proficiency_test_visualizations');
    }
};
