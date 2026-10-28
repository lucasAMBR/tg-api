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
            $table->string("title_pt")->nullable()->after('title');
            $table->string("title_en")->nullable()->after('title_pt');
            $table->text("description_pt")->nullable()->after('description');
            $table->text("description_en")->nullable()->after('description_pt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_histories', function (Blueprint $table) {
            $table->dropColumn('title_pt');
            $table->dropColumn('title_en');
            $table->dropColumn('description_pt');
            $table->dropColumn('description_en');
        });
    }
};
