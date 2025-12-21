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
        Schema::table('debit_notes', function (Blueprint $table) {
            // Drop foreign key first (it uses the unique index)
            $table->dropForeign(['gig_id']);
            // Now we can drop the unique constraint
            $table->dropUnique('debit_notes_gig_id_unique');
            // Add back a regular index and foreign key
            $table->index('gig_id');
            $table->foreign('gig_id')->references('id')->on('gigs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debit_notes', function (Blueprint $table) {
            $table->dropForeign(['gig_id']);
            $table->dropIndex(['gig_id']);
            $table->unique('gig_id');
            $table->foreign('gig_id')->references('id')->on('gigs')->onDelete('cascade');
        });
    }
};
