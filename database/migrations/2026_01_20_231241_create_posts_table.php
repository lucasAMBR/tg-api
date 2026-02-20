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
        Schema::create('dev_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->text('bio');
            $table->string('cpf');
            $table->date('birthdate');
            $table->string('seniority_level');
            $table->boolean('open_to_relocation')->default(false);
            $table->boolean('open_to_work')->default(true);
            $table->integer('score')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('company_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->text('bio');
            $table->string('cnpj');
            $table->date('founding_date');
            $table->integer('score')->default(0);
            $table->string('operational_segment');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('client_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->text('bio');
            $table->string('cpf');
            $table->date('birthdate');
            $table->integer('score')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dev_profile');
    }
};
