<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   // no arquivo de migration recém-criado
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('booker_id')->nullable()->after('id')->constrained('bookers')->onDelete('set null');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['booker_id']);
        $table->dropColumn('booker_id');
    });
}
};
