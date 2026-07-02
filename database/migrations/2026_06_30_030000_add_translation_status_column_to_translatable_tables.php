<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'dev_profiles',
        'company_profiles',
        'client_profiles',
        'project_histories',
        'academic_backgrounds',
        'employment_histories',
        'questions',
        'question_responses',
        'company_projects',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('translation_status')->default('pending');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('translation_status');
            });
        }
    }
};
