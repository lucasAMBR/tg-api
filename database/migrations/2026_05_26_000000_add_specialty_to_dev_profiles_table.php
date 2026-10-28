<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dev_profiles', function (Blueprint $table) {
            $table->string('specialty')->default('fullstack')->after('seniority_level');
        });
    }

    public function down(): void
    {
        Schema::table('dev_profiles', function (Blueprint $table) {
            $table->dropColumn('specialty');
        });
    }
};
