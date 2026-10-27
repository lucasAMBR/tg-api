<?php

use App\Enums\FreelanceProposalStatusEnum;
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
        Schema::create('freelance_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('freelance_job_vacancy_id')->constrained('freelance_job_vacancies')->cascadeOnDelete();
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->string('pricing_type');
            // Valor total do serviço. No orçamento por hora é o valor da hora vezes as horas
            // estimadas, gravado já calculado para o ranking comparar todos pelo mesmo número
            $table->decimal('total_price', 10, 2);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->unsignedInteger('estimated_hours')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default(FreelanceProposalStatusEnum::PENDING->value);
            $table->timestamps();

            // Um orçamento por dev em cada freela: para mudar o valor, o dev edita o que já enviou
            $table->unique(['freelance_job_vacancy_id', 'dev_profile_id']);
            $table->index('dev_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelance_proposals');
    }
};
