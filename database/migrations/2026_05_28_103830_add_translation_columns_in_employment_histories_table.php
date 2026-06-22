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
        Schema::table('employment_histories', function (Blueprint $table) {
            $table->string("position_name_pt")->nullable()->after('position_name');
            $table->string("position_name_en")->nullable()->after('position_name_pt');
            $table->text("actuation_details_pt")->nullable()->after('actuation_details');
            $table->text("actuation_details_en")->nullable()->after('actuation_details_pt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employment_histories', function (Blueprint $table) {
            $table->dropColumn('position_name_pt');
            $table->dropColumn('position_name_en');
            $table->dropColumn('actuation_details_pt');
            $table->dropColumn('actuation_details_en');
        });
    }
};
