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
        Schema::create('freelance_feed_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('freelance_job_vacancy_id')->constrained('freelance_job_vacancies')->cascadeOnDelete();
            // Conta excluída não apaga o histórico do freela: a postagem só fica sem autor
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->text('content')->nullable();
            // Na review, a entrega revisada
            $table->uuid('parent_id')->nullable();
            $table->string('review_status')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // O feed é listado do mais recente para o mais antigo dentro de cada freela
            $table->index(['freelance_job_vacancy_id', 'created_at']);
            $table->index('parent_id');
        });

        // Em separado: no Postgres, a chave estrangeira para a própria tabela precisa da
        // primary key, que o create só adiciona depois das chaves estrangeiras
        Schema::table('freelance_feed_posts', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('freelance_feed_posts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelance_feed_posts');
    }
};
