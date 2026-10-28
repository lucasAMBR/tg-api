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
        Schema::table('users', function (Blueprint $table) {
            // Configuração do chat: desligada, quem mandou a mensagem não fica sabendo que o
            // usuário leu (e, como no WhatsApp, o usuário também deixa de ver a leitura dos outros)
            $table->boolean('read_receipts_enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('read_receipts_enabled');
        });
    }
};
