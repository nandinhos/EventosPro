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
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gig_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_taker_id')->nullable()->constrained()->nullOnDelete();

            // Auto-numbering
            $table->integer('year');
            $table->integer('sequential');
            $table->string('number', 20);                   // "001/2025"

            // Financial snapshot at generation time
            $table->decimal('honorarios', 12, 2)->default(0);
            $table->decimal('despesas', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Metadata
            $table->timestamp('issued_at');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['gig_id']);                     // One note per gig
            $table->unique(['year', 'sequential']);         // Unique numbering
            $table->index('number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debit_notes');
    }
};
