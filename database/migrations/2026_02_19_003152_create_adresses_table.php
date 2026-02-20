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
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('cep');
            $table->string('address_type');
            $table->string('street');
            $table->string('street_complete_name');
            $table->string('district');
            $table->string('city');
            $table->string('state', 2);
            $table->decimal('latitude', 10,8);
            $table->decimal('longitude', 11,8);

            $table->string('number');
            $table->string('complement')->nullable();

            $table->uuidMorphs('addressable');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
