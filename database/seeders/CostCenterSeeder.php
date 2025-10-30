<?php

namespace Database\Seeders;

use App\Models\CostCenter;
use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Criando centros de custo específicos para eventos de música eletrônica...');

        $costCenters = [
            // Centros de Custo Administrativos da Agência
            ['name' => 'Administrativo', 'description' => 'Despesas gerais de administração da agência.', 'is_active' => true, 'color' => null],
            ['name' => 'Operacional', 'description' => 'Custos operacionais da agência não ligados a um evento específico.', 'is_active' => true, 'color' => null],
            ['name' => 'Pessoal', 'description' => 'Salários, benefícios e encargos da equipe interna.', 'is_active' => true, 'color' => null],
            ['name' => 'Outros', 'description' => 'Custos diversos não categorizados.', 'is_active' => true, 'color' => null],

            // Centros de Custo Básicos
            ['name' => 'Catering', 'description' => 'Alimentação e bebidas para artistas e equipe', 'is_active' => true, 'color' => null],
            ['name' => 'Hospedagem', 'description' => 'Hotel e acomodação para artistas', 'is_active' => true, 'color' => null],
            ['name' => 'Transporte', 'description' => 'Logística e transporte de artistas', 'is_active' => true, 'color' => null],

            // Centros de Custo Específicos para Música Eletrônica
            ['name' => 'Equipamento de Som', 'description' => 'Sistema de som, mixers, CDJs, monitores', 'is_active' => true, 'color' => null],
            ['name' => 'Iluminação', 'description' => 'Equipamentos de luz, lasers, strobes, LED walls', 'is_active' => true, 'color' => null],
            ['name' => 'Estrutura de Palco', 'description' => 'Montagem e desmontagem de palco/booth', 'is_active' => true, 'color' => null],
            ['name' => 'Segurança', 'description' => 'Segurança do evento e dos artistas', 'is_active' => true, 'color' => null],
            ['name' => 'Produção Local', 'description' => 'Equipe de produção e técnicos locais', 'is_active' => true, 'color' => null],
            ['name' => 'Marketing', 'description' => 'Divulgação, flyers, redes sociais', 'is_active' => true, 'color' => null],
            ['name' => 'Rider Técnico', 'description' => 'Equipamentos específicos solicitados pelo artista', 'is_active' => true, 'color' => null],
            ['name' => 'Rider Hospitalidade', 'description' => 'Bebidas, comidas e amenidades do camarim', 'is_active' => true, 'color' => null],

            // Centros de Custo para Festivais
            ['name' => 'Infraestrutura', 'description' => 'Tendas, banheiros, área VIP, cercamento', 'is_active' => true, 'color' => null],
            ['name' => 'Gerador/Energia', 'description' => 'Fornecimento de energia elétrica', 'is_active' => true, 'color' => null],
            ['name' => 'Licenças', 'description' => 'Alvarás, bombeiros, polícia, ECAD', 'is_active' => true, 'color' => null],
            ['name' => 'Seguros', 'description' => 'Seguro do evento e responsabilidade civil', 'is_active' => true, 'color' => null],

            // Centros de Custo para Clubs
            ['name' => 'Taxa de Venue', 'description' => 'Taxa de uso do espaço/club', 'is_active' => true, 'color' => null],
            ['name' => 'Bar Commission', 'description' => 'Comissão sobre vendas de bebidas', 'is_active' => true, 'color' => null],
            ['name' => 'Door Staff', 'description' => 'Equipe de portaria e bilheteria', 'is_active' => true, 'color' => null],

            // Centros de Custo Internacionais
            ['name' => 'Visto/Documentação', 'description' => 'Vistos e documentação para artistas internacionais', 'is_active' => true, 'color' => null],
            ['name' => 'Câmbio', 'description' => 'Variação cambial e taxas de conversão', 'is_active' => true, 'color' => null],
            ['name' => 'Remessa Internacional', 'description' => 'Taxas bancárias para pagamentos internacionais', 'is_active' => true, 'color' => null],

            // Centros de Custo de Emergência
            ['name' => 'Contingência', 'description' => 'Reserva para imprevistos e emergências', 'is_active' => true, 'color' => null],
            ['name' => 'Cancelamento', 'description' => 'Custos relacionados a cancelamentos', 'is_active' => true, 'color' => null],
            ['name' => 'Multas', 'description' => 'Multas por descumprimento de contratos ou regulamentações', 'is_active' => true, 'color' => null],

            // Centros de Custo Digitais
            ['name' => 'Streaming', 'description' => 'Equipamentos e plataformas para transmissão online', 'is_active' => true, 'color' => null],
            ['name' => 'Gravação', 'description' => 'Equipamentos para gravação de sets', 'is_active' => true, 'color' => null],
            ['name' => 'Fotografia/Vídeo', 'description' => 'Cobertura fotográfica e audiovisual do evento', 'is_active' => true, 'color' => null],

            // Centros de Custo Especiais
            ['name' => 'Decoração Temática', 'description' => 'Decoração e ambientação específica do evento', 'is_active' => true, 'color' => null],
            ['name' => 'Efeitos Especiais', 'description' => 'Máquinas de fumaça, confete, CO2, pirotecnia', 'is_active' => true, 'color' => null],
            ['name' => 'VIP Services', 'description' => 'Serviços exclusivos para área VIP', 'is_active' => true, 'color' => null],
            ['name' => 'After Party', 'description' => 'Custos relacionados a after parties', 'is_active' => true, 'color' => null],

            // Centro de Custo para Estorno (Refund/Chargeback)
            ['name' => 'Estorno', 'description' => 'Devoluções parciais ou completas de valores pagos', 'is_active' => true, 'color' => '#ef4444'],
        ];

        $existingCostCenters = CostCenter::pluck('name')->toArray();
        $createdCount = 0;

        foreach ($costCenters as $costCenterData) {
            if (! in_array($costCenterData['name'], $existingCostCenters)) {
                CostCenter::create($costCenterData);
                $createdCount++;
                $existingCostCenters[] = $costCenterData['name'];
            }
        }

        $this->command->info("$createdCount novos centros de custo para música eletrônica criados com sucesso!");
        $this->command->info('Total de centros de custo no sistema: '.CostCenter::count());
    }
}
