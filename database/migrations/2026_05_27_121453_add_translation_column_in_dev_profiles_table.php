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
        Schema::table('dev_profiles', function (Blueprint $table) {
            $table->text("bio_pt")->nullable()->after('bio');
            $table->text("bio_en")->nullable()->after('bio_pt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dev_profiles', function (Blueprint $table) {
            $table->dropColumn('bio_pt');
            $table->dropColumn('bio_en');
        });
    }
};
