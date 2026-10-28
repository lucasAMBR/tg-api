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
        Schema::create('follows', function (Blueprint $table) {
            // Quem segue.
            $table->foreignUuid('follower_id')->constrained('users')->cascadeOnDelete();
            // Quem é seguido.
            $table->foreignUuid('followed_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['follower_id', 'followed_id']);

            // A PK já cobre "quem o usuário segue"; este índice cobre "quem segue o usuário",
            // listado do seguidor mais recente para o mais antigo.
            $table->index(['followed_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
