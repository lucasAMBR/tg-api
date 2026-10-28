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
        Schema::table('question_responses', function (Blueprint $table) {
            $table->string("response_en")->nullable()->after('response');
            $table->string("response_pt")->nullable()->after('response_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->dropColumn('response_en');
            $table->dropColumn('response_pt');
        });
    }
};
