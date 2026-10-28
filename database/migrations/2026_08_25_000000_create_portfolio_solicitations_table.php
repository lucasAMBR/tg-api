<?php

use App\Enums\PortfolioSolicitationStatusEnum;
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
        Schema::create('portfolio_solicitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dev_job_vacancy_id')->constrained('dev_job_vacancy')->cascadeOnDelete();
            $table->foreignUuid('dev_profile_id')->constrained('dev_profiles')->cascadeOnDelete();
            $table->string('portfolio_url')->nullable();
            // Preenchido junto com a url, de acordo com o endereço informado
            $table->string('type')->nullable();
            $table->string('status')->default(PortfolioSolicitationStatusEnum::PENDING->value);
            $table->date('due_date');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_solicitations');
    }
};
