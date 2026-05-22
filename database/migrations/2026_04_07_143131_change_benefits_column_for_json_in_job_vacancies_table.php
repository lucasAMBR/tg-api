<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Para mudar de string para JSON preciso passar o comando SQL na mão
        DB::statement("
            ALTER TABLE job_vacancies
            ALTER COLUMN benefits TYPE JSON
            USING benefits::json
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            ALTER TABLE job_vacancies
            ALTER COLUMN benefits TYPE VARCHAR
            USING benefits::varchar
        ");
    }
};
