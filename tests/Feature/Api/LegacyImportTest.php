<?php

namespace Tests\Feature\Api;

use App\Models\Artist;
use App\Models\Gig;
use App\Models\LegalEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\LegalEntitySeeder::class);
        $this->seed(\Database\Seeders\CostCenterSeeder::class);
    }

    /**
     * Test contract import.
     */
    public function test_can_import_legacy_contracts(): void
    {
        $artist = Artist::factory()->create(['name' => 'Artista Exemplo']);

        $payload = [
            "contracts" => [
                [
                    "numero_contrato" => "COR-2024-001",
                    "artista" => "Artista Exemplo",
                    "data_evento" => "2024-12-31",
                    "valor_bruto" => 50000.00,
                    "moeda" => "BRL",
                    "status" => "Confirmado",
                    "venue" => "Clube X",
                    "city" => "São Paulo",
                    "state" => "SP"
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/import/legacy/contracts', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('imported_count', 1);

        $this->assertDatabaseHas('gigs', [
            'contract_number' => 'COR-2024-001',
            'artist_id' => $artist->id,
            'contract_data_status' => 'Legacy',
            'legal_entity_id' => 4,
            'cache_value' => 50000.00,
            'location_event_details' => 'Clube X São Paulo SP',
        ]);
    }

    /**
     * Test receivable import.
     */
    public function test_can_import_legacy_receivables(): void
    {
        $artist = Artist::factory()->create(['name' => 'Artista Exemplo']);
        $gig = Gig::create([
            'contract_number' => 'COR-2024-001',
            'artist_id' => $artist->id,
            'gig_date' => '2024-12-31',
            'cache_value' => 50000.00,
            'currency' => 'BRL',
            'contract_data_status' => 'Legacy',
            'legal_entity_id' => 4,
            'location_event_details' => 'Local Teste',
        ]);

        $payload = [
            "receivables" => [
                [
                    "contrato_ref" => "COR-2024-001",
                    "parcela" => "1/1",
                    "valor" => 50000.00,
                    "data_vencimento" => "2024-12-15",
                    "status_pagamento" => "pago",
                    "data_pagamento" => "2024-12-14"
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/import/legacy/receivables', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('imported_count', 1);

        $this->assertDatabaseHas('payments', [
            'gig_id' => $gig->id,
            'description' => 'Parcela 1/1',
            'due_value' => 50000.00,
            'received_value_actual' => 50000.00,
            // 'received_date_actual' => '2024-12-14', // Ignorar hora na comparação se possível ou usar date_format
        ]);
    }

    /**
     * Test payable import.
     */
    public function test_can_import_legacy_payables(): void
    {
        $artist = Artist::factory()->create(['name' => 'Artista Exemplo']);
        $gig = Gig::create([
            'contract_number' => 'COR-2024-001',
            'artist_id' => $artist->id,
            'gig_date' => '2024-12-31',
            'cache_value' => 50000.00,
            'currency' => 'BRL',
            'contract_data_status' => 'Legacy',
            'legal_entity_id' => 4,
            'location_event_details' => 'Local Teste',
        ]);

        $payload = [
            "payables" => [
                [
                    "ctr_ref" => "COR-2024-001",
                    "descricao" => "Cachê Artista",
                    "contraparte" => "Empresa Artista Ltda",
                    "valor_devido" => 30000.00,
                    "data_devida" => "2025-01-05",
                    "status" => "paid"
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/import/legacy/payables', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('imported_count', 1);

        $this->assertDatabaseHas('gig_costs', [
            'gig_id' => $gig->id,
            'description' => 'Cachê Artista',
            'value' => 30000.00,
            'is_confirmed' => 1,
        ]);
    }
}
