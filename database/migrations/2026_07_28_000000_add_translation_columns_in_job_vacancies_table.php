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
        Schema::table('job_vacancies', function (Blueprint $table) {
            $table->string('title_pt')->nullable()->after('title');
            $table->string('title_en')->nullable()->after('title_pt');
            $table->text('description_pt')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_pt');
            $table->json('benefits_pt')->nullable()->after('benefits');
            $table->json('benefits_en')->nullable()->after('benefits_pt');
            $table->string('translation_status')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_vacancies', function (Blueprint $table) {
            $table->dropColumn([
                'title_pt',
                'title_en',
                'description_pt',
                'description_en',
                'benefits_pt',
                'benefits_en',
                'translation_status',
            ]);
        });
    }
};
