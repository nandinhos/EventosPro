<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupExternalDatabase extends Command
{
    protected $signature = 'app:setup-external-db {--install-client : Instala o cliente MySQL no container}';

    protected $description = 'Configura o ambiente para usar banco de dados externo ao container Docker';

    public function handle(): int
    {
        $dbHost = config('database.connections.mysql.host', 'mysql');
        $dbLocation = env('DB_LOCATION', 'docker');

        $this->info("DB Host: {$dbHost}");
        $this->info("DB Location: {$dbLocation}");

        if ($dbLocation === 'external' || $dbHost === 'host.docker.internal' || $dbHost === '127.0.0.1') {
            $this->warn('Banco de dados externo detectado. Verificando cliente MySQL...');

            if (! $this->checkMysqlClient()) {
                if ($this->option('install-client')) {
                    $this->installMysqlClient();
                } else {
                    $this->warn('Cliente MySQL não encontrado.');
                    $this->info('Execute: php artisan app:setup-external-db --install-client');
                    $this->line('');
                    $this->line('OU adicione ao seu script de deploy:');
                    $this->code('   php artisan app:setup-external-db --install-client');

                    return self::SUCCESS;
                }
            } else {
                $this->info('Cliente MySQL já está disponível.');
            }

            $this->testConnection();
        } else {
            $this->info('Banco de dados dentro do Docker (configuração padrão).');
        }

        return self::SUCCESS;
    }

    protected function checkMysqlClient(): bool
    {
        $binaries = ['mariadb', 'mysql', '/usr/bin/mariadb', '/usr/bin/mysql'];

        foreach ($binaries as $binary) {
            $result = [];
            $returnCode = 0;
            exec("which {$binary} 2>/dev/null", $result, $returnCode);

            if ($returnCode === 0 && ! empty($result)) {
                $this->info("Cliente encontrado: {$binary}");

                return true;
            }
        }

        return false;
    }

    protected function installMysqlClient(): void
    {
        $this->info('Instalando cliente MySQL/MariaDB...');

        $output = [];
        $returnCode = 0;

        exec('apt-get update 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Não foi possível executar apt-get update');
            $this->line('Verifique se o container tem acesso aos repositórios.');

            return;
        }

        exec('apt-get install -y default-mysql-client 2>&1', $output, $returnCode);

        if ($returnCode === 0) {
            $this->info('✓ Cliente MySQL/MariaDB instalado com sucesso!');
        } else {
            $this->error('Falha ao instalar cliente: '.implode("\n", $output));
        }
    }

    protected function testConnection(): void
    {
        $this->info('Testando conexão com o banco...');

        $connection = config('database.connections.mysql');
        $command = sprintf(
            'mariadb -h %s -P %s -u %s -e "SELECT 1" 2>&1',
            escapeshellarg($connection['host']),
            escapeshellarg($connection['port'] ?? 3306),
            escapeshellarg($connection['username'])
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            $this->info('✓ Conexão estabelecida com sucesso!');
        } else {
            $this->error('✗ Falha na conexão: '.implode("\n", $output));
        }
    }
}
