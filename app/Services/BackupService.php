<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\Databases\Sqlite;

class BackupService
{
    /**
     * Caminho base para armazenar os backups
     */
    protected string $backupPath;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->backupPath = config(
            'backup.path',
            env('BACKUP_PATH', storage_path('app/backups'))
        );
    }

    /**
     * Cria um backup do banco de dados
     *
     * @return array{success: bool, filename: string|null, path: string|null, message: string}
     */
    public function createBackup(): array
    {
        try {
            // Garantir que o diretório existe
            $this->ensureBackupDirectoryExists();

            // Gerar nome do arquivo
            $filename = $this->generateBackupFilename();
            $filepath = $this->backupPath.'/'.$filename;

            // Detectar driver e criar dump
            $driver = $this->getDatabaseDriver();

            match ($driver) {
                'mysql' => $this->dumpMySql($filepath),
                'sqlite' => $this->dumpSqlite($filepath),
                'pgsql' => $this->dumpPostgreSql($filepath),
                default => throw new Exception("Driver de banco não suportado: {$driver}"),
            };

            Log::info("[BackupService] Backup criado com sucesso: {$filename}");

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath,
                'message' => 'Backup criado com sucesso',
            ];
        } catch (Exception $e) {
            Log::error('[BackupService] Erro ao criar backup: '.$e->getMessage());

            return [
                'success' => false,
                'filename' => null,
                'path' => null,
                'message' => 'Erro ao criar backup: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Lista todos os backups disponíveis
     *
     * @return array<int, array{filename: string, size: int, created_at: string}>
     */
    public function listBackups(): array
    {
        if (! File::isDirectory($this->backupPath)) {
            return [];
        }

        $files = File::files($this->backupPath);
        $backups = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'sql') {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i:s'),
                ];
            }
        }

        // Ordenar por data de criação (mais recente primeiro)
        usort($backups, function ($a, $b) {
            return strcmp($b['filename'], $a['filename']);
        });

        // Retornar apenas os 10 mais recentes
        return array_slice($backups, 0, 10);
    }

    /**
     * Restaura um backup do banco de dados
     *
     * @return array{success: bool, message: string}
     */
    public function restoreBackup(string $filename): array
    {
        if (! $this->isValidFilename($filename)) {
            Log::warning("[BackupService] Tentativa de restaurar arquivo com nome inválido: {$filename}");

            return [
                'success' => false,
                'message' => 'Nome de arquivo inválido',
            ];
        }

        $filepath = $this->backupPath.'/'.$filename;

        if (! File::exists($filepath)) {
            return [
                'success' => false,
                'message' => 'Arquivo de backup não encontrado',
            ];
        }

        try {
            $driver = $this->getDatabaseDriver();

            match ($driver) {
                'mysql' => $this->restoreMySql($filepath),
                'sqlite' => $this->restoreSqlite($filepath),
                'pgsql' => $this->restorePostgreSql($filepath),
                default => throw new Exception("Driver de banco não suportado para restore: {$driver}"),
            };

            // Recriar usuários admin após restauração
            $this->recreateAdminUsers();

            Log::info("[BackupService] Backup restaurado com sucesso: {$filename}");

            return [
                'success' => true,
                'message' => 'Backup restaurado com sucesso',
            ];
        } catch (Exception $e) {
            Log::error('[BackupService] Erro ao restaurar backup: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao restaurar backup: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Faz upload de um arquivo de backup externo
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array{success: bool, filename: string|null, message: string}
     */
    public function uploadBackup($file): array
    {
        try {
            $this->ensureBackupDirectoryExists();

            $originalName = $file->getClientOriginalName();

            if (! $this->isValidFilename($originalName)) {
                return [
                    'success' => false,
                    'filename' => null,
                    'message' => 'O arquivo deve ter a extensão .sql e um nome válido contendo apenas letras, números, pontos, hífens ou sublinhados.',
                ];
            }

            // Para evitar sobrescrever, anexamos um timestamp se o arquivo já existir
            $filename = $originalName;
            if (File::exists($this->backupPath.'/'.$filename)) {
                $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                $filename = $nameWithoutExt.'-'.time().'.sql';
            }

            $file->move($this->backupPath, $filename);

            Log::info("[BackupService] Backup externo uploaded com sucesso: {$filename}");

            return [
                'success' => true,
                'filename' => $filename,
                'message' => 'Backup enviado com sucesso',
            ];
        } catch (Exception $e) {
            Log::error('[BackupService] Erro ao fazer upload de backup: '.$e->getMessage());

            return [
                'success' => false,
                'filename' => null,
                'message' => 'Erro ao salvar o arquivo: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Restaura backup do MySQL
     *
     * @throws Exception
     */
    /**
     * Restaura backup do MySQL usando comando nativo (streaming)
     *
     * @throws Exception
     */
    protected function restoreMySql(string $filepath): void
    {
        $connection = config('database.connections.mysql');
        
        // Comando mysql para restaurar (usa streaming, evita estouro de memória)
        $command = sprintf(
            'MYSQL_PWD=%s mysql -h %s -P %s -u %s %s < %s 2>&1',
            escapeshellarg($connection['password']),
            escapeshellarg($connection['host']),
            escapeshellarg($connection['port'] ?? 3306),
            escapeshellarg($connection['username']),
            escapeshellarg($connection['database']),
            escapeshellarg($filepath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $error = implode("\n", $output);
            Log::error("[BackupService] Falha no restore nativo: " . $error);
            
            // Fallback para PDO se o binário falhar (por exemplo, PATH não configurado)
            if (str_contains($error, 'not found')) {
                Log::warning("[BackupService] mysql não encontrado, tentando fallback PDO");
                $this->restoreMySqlFallback($filepath);
                return;
            }
            
            throw new Exception('Erro ao restaurar MySQL: ' . $error);
        }
    }

    /**
     * Fallback de restauração via PDO (mais lento, propenso a erros de memória)
     */
    protected function restoreMySqlFallback(string $filepath): void
    {
        $sql = file_get_contents($filepath);
        if ($sql === false) {
            throw new Exception('Não foi possível ler o arquivo de backup');
        }

        $pdo = \DB::connection()->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->parseSqlStatements($sql) as $statement) {
                if (trim($statement)) {
                    $pdo->exec($statement);
                }
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Divide o conteúdo SQL em statements individuais
     */
    protected function parseSqlStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if (! $inString && ($char === '#' || ($char === '-' && isset($sql[$i + 1]) && $sql[$i + 1] === '-'))) {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if (! $inString && $char === '/' && isset($sql[$i + 1]) && $sql[$i + 1] === '*') {
                $i += 2;
                while ($i < $length && ! ($sql[$i] === '*' && isset($sql[$i + 1]) && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }

            if (($char === "'" || $char === '"') && ! $inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) {
                $inString = false;
            }

            $current .= $char;

            if (! $inString && $char === ';') {
                $trimmed = trim($current);
                if ($trimmed !== '' && $trimmed !== ';') {
                    $statements[] = $trimmed;
                }
                $current = '';
            }
        }

        $trimmed = trim($current);
        if ($trimmed !== '' && $trimmed !== ';') {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    /**
     * Restaura backup do SQLite
     *
     * @throws Exception
     */
    protected function restoreSqlite(string $filepath): void
    {
        $connection = config('database.connections.sqlite');
        $database = $connection['database'];

        // Se for path relativo, converter para absoluto
        if (! str_starts_with($database, '/')) {
            $database = database_path($database);
        }

        // Copiar arquivo de backup para o local do banco
        if (! copy($filepath, $database)) {
            throw new Exception('Erro ao copiar arquivo SQLite');
        }
    }

    /**
     * Restaura backup do PostgreSQL
     *
     * @throws Exception
     */
    protected function restorePostgreSql(string $filepath): void
    {
        $connection = config('database.connections.pgsql');

        $command = sprintf(
            'PGPASSWORD=%s psql -h %s -p %s -U %s -d %s -f %s',
            escapeshellarg($connection['password']),
            escapeshellarg($connection['host']),
            escapeshellarg($connection['port'] ?? 5432),
            escapeshellarg($connection['username']),
            escapeshellarg($connection['database']),
            escapeshellarg($filepath)
        );

        $output = [];
        $returnCode = 0;
        exec($command.' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('Erro ao restaurar PostgreSQL: '.implode("\n", $output));
        }
    }

    /**
     * Deleta um arquivo de backup
     */
    public function deleteBackup(string $filename): bool
    {
        if (! $this->isValidFilename($filename)) {
            Log::warning("[BackupService] Tentativa de deletar arquivo com nome inválido: {$filename}");

            return false;
        }

        $filepath = $this->backupPath.'/'.$filename;

        if (! File::exists($filepath)) {
            return false;
        }

        try {
            File::delete($filepath);
            Log::info("[BackupService] Backup deletado: {$filename}");

            return true;
        } catch (Exception $e) {
            Log::error('[BackupService] Erro ao deletar backup: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Retorna o caminho completo de um backup
     */
    public function getBackupFilePath(string $filename): ?string
    {
        if (! $this->isValidFilename($filename)) {
            return null;
        }

        $filepath = $this->backupPath.'/'.$filename;

        return File::exists($filepath) ? $filepath : null;
    }

    /**
     * Retorna o driver do banco de dados atual
     */
    public function getDatabaseDriver(): string
    {
        return config('database.default', 'mysql');
    }

    /**
     * Retorna o caminho dos backups
     */
    public function getBackupPath(): string
    {
        return $this->backupPath;
    }

    /**
     * Gera o nome do arquivo de backup
     */
    public function generateBackupFilename(): string
    {
        $database = config('database.connections.'.$this->getDatabaseDriver().'.database', 'database');
        $timestamp = now()->format('Y-m-d-His');

        return "{$database}-{$timestamp}.sql";
    }

    /**
     * Valida o nome do arquivo para segurança
     */
    public function isValidFilename(string $filename): bool
    {
        // Previne path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/')) {
            return false;
        }

        // Apenas arquivos .sql são permitidos
        if (! str_ends_with($filename, '.sql')) {
            return false;
        }

        // Regex para caracteres permitidos
        return preg_match('/^[a-zA-Z0-9._-]+\.sql$/', $filename) === 1;
    }

    /**
     * Garante que o diretório de backup existe
     */
    protected function ensureBackupDirectoryExists(): void
    {
        if (! File::isDirectory($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * Cria dump do MySQL usando mysqldump (nativo)
     *
     * @throws Exception
     */
    protected function dumpMySql(string $filepath): void
    {
        $connection = config('database.connections.mysql');
        
        // mysqldump --opt garante triggers, views, drop table, etc.
        $command = sprintf(
            'MYSQL_PWD=%s mysqldump --opt --skip-lock-tables -h %s -P %s -u %s %s > %s 2>&1',
            escapeshellarg($connection['password']),
            escapeshellarg($connection['host']),
            escapeshellarg($connection['port'] ?? 3306),
            escapeshellarg($connection['username']),
            escapeshellarg($connection['database']),
            escapeshellarg($filepath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $error = implode("\n", $output);
            Log::error("[BackupService] Falha no mysqldump: " . $error);
            
            if (str_contains($error, 'not found')) {
                Log::warning("[BackupService] mysqldump não encontrado, tentando fallback PDO");
                $this->dumpMySqlFallback($filepath);
                return;
            }
            
            throw new Exception('Erro ao gerar backup: ' . $error);
        }
    }

    /**
     * Fallback de dump via PDO
     */
    protected function dumpMySqlFallback(string $filepath): void
    {
        $pdo = \DB::connection()->getPdo();
        $database = config('database.connections.mysql.database');
        $output = fopen($filepath, 'w');

        if ($output === false) {
            throw new Exception('Não foi possível criar o arquivo de backup');
        }

        fwrite($output, "-- MySQL Backup (PDO Fallback)\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            fwrite($output, "DROP TABLE IF EXISTS `{$table}`;\n" . $createTable['Create Table'] . ";\n\n");

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $columnList = implode('`, `', $columns);
                foreach (array_chunk($rows, 100) as $chunk) {
                    $values = [];
                    foreach ($chunk as $row) {
                        $values[] = "(" . implode(", ", array_map(fn($v) => $v === null ? "NULL" : $pdo->quote($v), array_values($row))) . ")";
                    }
                    fwrite($output, "INSERT INTO `{$table}` (`{$columnList}`) VALUES " . implode(", ", $values) . ";\n");
                }
            }
        }

        fwrite($output, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($output);
    }


    /**
     * Cria dump do SQLite
     *
     * @throws Exception
     */
    protected function dumpSqlite(string $filepath): void
    {
        $connection = config('database.connections.sqlite');
        $database = $connection['database'];

        // Se for path relativo, converter para absoluto
        if (! str_starts_with($database, '/')) {
            $database = database_path($database);
        }

        Sqlite::create()
            ->setDbName($database)
            ->dumpToFile($filepath);
    }

    /**
     * Cria dump do PostgreSQL
     *
     * @throws Exception
     */
    protected function dumpPostgreSql(string $filepath): void
    {
        $connection = config('database.connections.pgsql');

        PostgreSql::create()
            ->setDbName($connection['database'])
            ->setUserName($connection['username'])
            ->setPassword($connection['password'])
            ->setHost($connection['host'])
            ->setPort($connection['port'] ?? 5432)
            ->dumpToFile($filepath);
    }

    protected function recreateAdminUsers(): void
    {
        try {
            $adminUsers = [
                'angelica.domingos@hotmail.com' => [
                    'name' => 'Angélica Domingos',
                ],
                'nandinhos@gmail.com' => [
                    'name' => 'Nando Dev',
                ],
            ];

            foreach ($adminUsers as $email => $data) {
                $user = \App\Models\User::withTrashed()->where('email', $email)->first();

                if ($user) {
                    if ($user->trashed()) {
                        $user->restore();
                    }
                } else {
                    $user = \App\Models\User::create([
                        'email' => $email,
                        'name' => $data['name'],
                        'password' => \Illuminate\Support\Facades\Hash::make('password'),
                    ]);
                }

                // Atribuir role ADMIN
                if (! $user->hasRole('ADMIN')) {
                    $user->syncRoles(['ADMIN']);
                }
            }

            Log::info('[BackupService] Usuários admin garantidos após restauração (senhas preservadas)');
        } catch (Exception $e) {
            Log::warning('[BackupService] Erro ao garantir usuários admin: '.$e->getMessage());
        }
    }
}
