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
        Schema::create('dev_questionnaires', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignUuid("dev_profile_id")->constrained("dev_profiles")->cascadeOnDelete();
            $table->integer("score");
            $table->dateTime("completed_at")->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_questionnaires');
    }
};
