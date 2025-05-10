<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('settlements', function (Blueprint $table) {
            // Detalhes do pagamento ao artista
            $table->decimal('artist_payment_value', 12, 2)->nullable()->after('settlement_date');
            $table->date('artist_payment_paid_at')->nullable()->after('artist_payment_value');
            // Detalhes do pagamento da comissão ao booker
            $table->decimal('booker_commission_value_paid', 12, 2)->nullable()->after('artist_payment_proof');
            $table->date('booker_commission_paid_at')->nullable()->after('booker_commission_value_paid');
        });
    }
    public function down(): void {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropColumn([
                'artist_payment_value', 'artist_payment_paid_at',
                'booker_commission_value_paid', 'booker_commission_paid_at'
            ]);
        });
    }
};