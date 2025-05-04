<?php
// Código completo da migration create_bookers_table (Item 3 da resposta anterior)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('default_commission_rate', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookers');
    }
};