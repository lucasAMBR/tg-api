<?php

use App\Enums\FreelanceJobVacancyStatusEnum;
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
        Schema::table('freelance_job_vacancies', function (Blueprint $table) {
            // Quanto o cliente pretende pagar pelo serviço inteiro. Só ele vê, e é a
            // referência de preço do ranking de orçamentos
            $table->decimal('budget', 10, 2)->nullable();

            $table->string('status')->default(FreelanceJobVacancyStatusEnum::OPEN->value);
            $table->timestamp('contracted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->index('status');
        });

        // As vagas que já existem herdam o valor estimado como orçamento
        DB::table('freelance_job_vacancies')->update(['budget' => DB::raw('estimated_salary')]);

        Schema::table('freelance_job_vacancies', function (Blueprint $table) {
            $table->dropColumn(['salary_type', 'estimated_salary']);
        });

        Schema::table('freelance_job_vacancies', function (Blueprint $table) {
            $table->decimal('budget', 10, 2)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freelance_job_vacancies', function (Blueprint $table) {
            $table->string('salary_type')->default('per_month');
            $table->decimal('estimated_salary', 8, 2)->default(0);
        });

        DB::table('freelance_job_vacancies')->update(['estimated_salary' => DB::raw('LEAST(budget, 999999.99)')]);

        Schema::table('freelance_job_vacancies', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['budget', 'status', 'contracted_at', 'completed_at']);
        });
    }
};
