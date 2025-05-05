<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained('gigs')->cascadeOnDelete();

            // Colunas sem ->after()
            $table->string('description')->nullable();
            $table->decimal('due_value', 12, 2);
            $table->date('due_date')->index();
            $table->string('currency', 10)->default('BRL');
            $table->decimal('exchange_rate', 10, 6)->nullable();
            $table->decimal('received_value_actual', 12, 2)->nullable();
            $table->date('received_date_actual')->nullable();
            $table->timestamp('confirmed_at')->nullable()->index();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};