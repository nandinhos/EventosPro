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
        Schema::create('service_takers', function (Blueprint $table) {
            $table->id();
            $table->string('organization')->nullable();       // Razão Social
            $table->string('document')->nullable();           // CNPJ/CPF/Outro
            $table->string('street')->nullable();             // Endereço
            $table->string('postal_code')->nullable();        // CEP
            $table->string('city')->nullable();               // Cidade
            $table->string('country')->nullable();            // País
            $table->string('company_phone')->nullable();      // Tel. empresa
            $table->string('contact')->nullable();            // Nome contato
            $table->string('email')->nullable();              // Email
            $table->string('phone')->nullable();              // Tel. contato
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_takers');
    }
};
