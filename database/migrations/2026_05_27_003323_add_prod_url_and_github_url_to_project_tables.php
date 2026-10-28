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
        Schema::table('project_histories', function (Blueprint $table) {
            $table->string('prod_url')->nullable();
            $table->string('github_url')->nullable();
        });

        Schema::table('company_projects', function (Blueprint $table) {
            $table->string('prod_url')->nullable();
            $table->string('github_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_histories', function (Blueprint $table) {
            $table->dropColumn(['prod_url', 'github_url']);
        });

        Schema::table('company_projects', function (Blueprint $table) {
            $table->dropColumn(['prod_url', 'github_url']);
        });
    }
};
