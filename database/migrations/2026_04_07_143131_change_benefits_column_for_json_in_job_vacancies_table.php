<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Para mudar de string para JSON preciso passar o comando SQL na mão
        Schema::table('job_vacancies', function (Blueprint $table) {
            DB::statement("
                ALTER TABLE job_vacancies
                ALTER COLUMN benefits TYPE JSON
                USING benefits::json
            ");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_vacancies', function (Blueprint $table) {
            DB::statement("
                ALTER TABLE job_Vacancies
                ALTER COLUMN benefits TYPE VARCHAR
                USING benefits::varchar
            ");
        });
    }
};
