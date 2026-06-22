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
        Schema::table('academic_backgrounds', function (Blueprint $table) {
            $table->string("degree_pt")->nullable()->after('degree');
            $table->string("degree_en")->nullable()->after('degree_pt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_backgrounds', function (Blueprint $table) {
            $table->dropColumn('degree_pt');
            $table->dropColumn('degree_en');
        });
    }
};
