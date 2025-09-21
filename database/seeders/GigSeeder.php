<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class GigSeeder extends Seeder
{
    public function run()
    {
        echo "Criando gigs realistas de música eletrônica em locais brasileiros...\n";

        $faker = Faker::create('pt_BR');

        // Obter IDs dos artistas e bookers existentes
        $artistIds = Artist::pluck('id')->toArray();
        $bookerIds = Booker::pluck('id')->toArray();

        if (empty($artistIds) || empty($bookerIds)) {
            echo "Erro: É necessário ter artistas e bookers cadastrados antes de criar gigs.\n";

            return;
        }

        $venues = [
            'Rock in Rio - Rio de Janeiro/RJ',
            'Lollapalooza Brasil - São Paulo/SP',
            'Tomorrowland Brasil - Itu/SP',
            'Ultra Music Festival Brasil - Rio de Janeiro/RJ',
            'Festa da Uva - Caxias do Sul/RS',
            'Festival de Inverno de Campos do Jordão - SP',
            'Aniversário de São Paulo - SP',
            'Reveillon de Copacabana - Rio de Janeiro/RJ',
            'Carnaval de Salvador - BA',
            'Festival de Verão de Salvador - BA',
            'Green Nation Fest - São Paulo/SP',
            'Planeta Atlântida - RS',
            'Villa Mix Festival - Goiânia/GO',
            'Music Valley - Camboriú/SC',
            'Xxxperience - São Paulo/SP',
            'Warung Day Festival - Itajaí/SC',
            'Universo Paralello - Bahia/BA',
            'Tribe Festival - São Paulo/SP',
            'Atmosphere Festival - Florianópolis/SC',
            'Dekmantel São Paulo - SP',
        ];

        // Status baseados nas regras de negócio do sistema
        $contractStatuses = ['assinado', 'para_assinatura', 'expirado', 'n/a', 'cancelado', 'concluido'];
        $paymentStatuses = ['a_vencer', 'vencido', 'pago', 'parcial'];
        $internalPaymentStatuses = ['pendente', 'pago']; // Para artist_payment_status e booker_payment_status
        $currencies = ['BRL', 'USD', 'EUR', 'GBP'];

        $gigsCreated = 0;
        $totalGigs = 50;

        for ($i = 0; $i < $totalGigs; $i++) {
            $contractDate = $faker->dateTimeBetween('-6 months', '+3 months');
            $gigDate = $faker->dateTimeBetween($contractDate, '+6 months');
            $currency = $faker->randomElement($currencies);
            $cacheValue = $faker->randomFloat(2, 5000, 150000);

            // Determinar status de pagamento baseado na data do evento
            $isEventPast = $gigDate < now();
            
            // Para eventos futuros, não permitir status 'pago'
            $artistPaymentStatus = $isEventPast ? $faker->randomElement($internalPaymentStatuses) : 'pendente';
            $bookerPaymentStatus = $isEventPast ? $faker->randomElement($internalPaymentStatuses) : 'pendente';

            $gig = Gig::create([
                'artist_id' => $faker->randomElement($artistIds),
                'booker_id' => $faker->randomElement($bookerIds),
                'contract_number' => 'CTR-'.$faker->unique()->numberBetween(100000, 999999),
                'contract_date' => $contractDate,
                'gig_date' => $gigDate,
                'location_event_details' => $faker->randomElement($venues),
                'cache_value' => $cacheValue,
                'currency' => $currency,
                'agency_commission_type' => 'percent',
                'agency_commission_value' => $faker->randomFloat(2, 15.00, 25.00), // Taxa percentual para agência
                'booker_commission_type' => 'percent',
                'booker_commission_value' => $faker->randomFloat(2, 3.00, 8.00), // Taxa percentual para booker
                'contract_status' => $faker->randomElement($contractStatuses),
                'payment_status' => $faker->randomElement($paymentStatuses),
                'artist_payment_status' => $artistPaymentStatus,
                'booker_payment_status' => $bookerPaymentStatus,
                'notes' => $faker->optional(0.3)->sentence(),
            ]);

            $gigsCreated++;
        }

        echo "$gigsCreated gigs criados com sucesso!\n";
    }
}
