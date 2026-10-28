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
        Schema::table('company_projects', function (Blueprint $table) {
            $table->string('title_pt')->nullable();
            $table->string('title_en')->nullable();
            $table->text('description_pt')->nullable();
            $table->text('description_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_projects', function (Blueprint $table) {
            $table->dropColumn('title_pt');
            $table->dropColumn('title_en');
            $table->dropColumn('description_pt');
            $table->dropColumn('description_en');
        });
    }
};
