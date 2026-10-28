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
        Schema::table('dev_screening_questionnaires', function (Blueprint $table) {
            // Prazo que a empresa dá para o dev responder o questionário, informado na
            // virada da etapa. Sempre preenchido pelo service, com um padrão quando a
            // empresa não informa uma data
            $table->date('due_date')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dev_screening_questionnaires', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
