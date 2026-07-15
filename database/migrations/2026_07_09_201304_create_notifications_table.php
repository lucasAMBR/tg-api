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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('notifiable_type');
            $table->string('notifiable_id');
            $table->string('type');
            $table->string('title');
            $table->string('title_pt')->nullable();
            $table->string('title_en')->nullable();
            $table->string('message');
            $table->string('message_pt')->nullable();
            $table->string('message_en')->nullable();
            $table->string('translation_status')->default('pending');
            $table->dateTime('read_at')->nullable();
            $table->string('link')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
