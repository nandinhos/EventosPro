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
    protected function restoreMySql(string $filepath): void
    {
        $connection = config('database.connections.mysql');
        $host = $connection['host'];
        $port = $connection['port'] ?? 3306;
        $username = $connection['username'];
        $password = $connection['password'];
        $database = $connection['database'];

        // Usar variável de ambiente para senha (mais seguro e evita prompt)
        $envVars = 'MYSQL_PWD='.escapeshellarg($password);

        $command = sprintf(
            '%s mysql -h %s -P %s -u %s %s < %s',
            $envVars,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $output = [];
        $returnCode = 0;
        exec($command.' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception('Erro ao restaurar MySQL: '.implode("\n", $output));
        }
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
     * Cria dump do MySQL
     *
     * @throws Exception
     */
    protected function dumpMySql(string $filepath): void
    {
        $connection = config('database.connections.mysql');

        MySql::create()
            ->setDbName($connection['database'])
            ->setUserName($connection['username'])
            ->setPassword($connection['password'])
            ->setHost($connection['host'])
            ->setPort($connection['port'] ?? 3306)
            ->dumpToFile($filepath);
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
}
