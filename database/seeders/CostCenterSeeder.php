<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CostCenter;

class CostCenterSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Criando centros de custo específicos para eventos de música eletrônica...');

        $costCenters = [
            // Centros de Custo Básicos
            ['name' => 'Catering', 'description' => 'Alimentação e bebidas para artistas e equipe'],
            ['name' => 'Hospedagem', 'description' => 'Hotel e acomodação para artistas'],
            ['name' => 'Transporte', 'description' => 'Logística e transporte de artistas'],
            
            // Centros de Custo Específicos para Música Eletrônica
            ['name' => 'Equipamento de Som', 'description' => 'Sistema de som, mixers, CDJs, monitores'],
            ['name' => 'Iluminação', 'description' => 'Equipamentos de luz, lasers, strobes, LED walls'],
            ['name' => 'Estrutura de Palco', 'description' => 'Montagem e desmontagem de palco/booth'],
            ['name' => 'Segurança', 'description' => 'Segurança do evento e dos artistas'],
            ['name' => 'Produção Local', 'description' => 'Equipe de produção e técnicos locais'],
            ['name' => 'Marketing', 'description' => 'Divulgação, flyers, redes sociais'],
            ['name' => 'Rider Técnico', 'description' => 'Equipamentos específicos solicitados pelo artista'],
            ['name' => 'Rider Hospitalidade', 'description' => 'Bebidas, comidas e amenidades do camarim'],
            
            // Centros de Custo para Festivais
            ['name' => 'Infraestrutura', 'description' => 'Tendas, banheiros, área VIP, cercamento'],
            ['name' => 'Gerador/Energia', 'description' => 'Fornecimento de energia elétrica'],
            ['name' => 'Licenças', 'description' => 'Alvarás, bombeiros, polícia, ECAD'],
            ['name' => 'Seguros', 'description' => 'Seguro do evento e responsabilidade civil'],
            
            // Centros de Custo para Clubs
            ['name' => 'Taxa de Venue', 'description' => 'Taxa de uso do espaço/club'],
            ['name' => 'Bar Commission', 'description' => 'Comissão sobre vendas de bebidas'],
            ['name' => 'Door Staff', 'description' => 'Equipe de portaria e bilheteria'],
            
            // Centros de Custo Internacionais
            ['name' => 'Visto/Documentação', 'description' => 'Vistos e documentação para artistas internacionais'],
            ['name' => 'Câmbio', 'description' => 'Variação cambial e taxas de conversão'],
            ['name' => 'Remessa Internacional', 'description' => 'Taxas bancárias para pagamentos internacionais'],
            
            // Centros de Custo de Emergência
            ['name' => 'Contingência', 'description' => 'Reserva para imprevistos e emergências'],
            ['name' => 'Cancelamento', 'description' => 'Custos relacionados a cancelamentos'],
            ['name' => 'Multas', 'description' => 'Multas por descumprimento de contratos ou regulamentações'],
            
            // Centros de Custo Digitais
            ['name' => 'Streaming', 'description' => 'Equipamentos e plataformas para transmissão online'],
            ['name' => 'Gravação', 'description' => 'Equipamentos para gravação de sets'],
            ['name' => 'Fotografia/Vídeo', 'description' => 'Cobertura fotográfica e audiovisual do evento'],
            
            // Centros de Custo Especiais
            ['name' => 'Decoração Temática', 'description' => 'Decoração e ambientação específica do evento'],
            ['name' => 'Efeitos Especiais', 'description' => 'Máquinas de fumaça, confete, CO2, pirotecnia'],
            ['name' => 'VIP Services', 'description' => 'Serviços exclusivos para área VIP'],
            ['name' => 'After Party', 'description' => 'Custos relacionados a after parties'],
        ];

        $existingCostCenters = CostCenter::pluck('name')->toArray();
        $createdCount = 0;

        foreach ($costCenters as $costCenterData) {
            if (!in_array($costCenterData['name'], $existingCostCenters)) {
                CostCenter::create($costCenterData);
                $createdCount++;
                $existingCostCenters[] = $costCenterData['name'];
            }
        }

        $this->command->info("$createdCount novos centros de custo para música eletrônica criados com sucesso!");
        $this->command->info('Total de centros de custo no sistema: ' . CostCenter::count());
    }
}