<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupDataService
{
    protected string $backupPath;

    protected array $tables = [
        'gigs',
        'artists',
        'bookers',
        'payments',
        'gig_costs',
        'service_takers',
        'cost_centers',
        'agency_costs',
        'settlements',
        'debit_notes',
        'contracts',
        'tags',
        'taggables',
    ];

    public function __construct()
    {
        $this->backupPath = config(
            'backup.path',
            storage_path('app/backups')
        );
    }

    public function createDataBackup(): array
    {
        try {
            $this->ensureBackupDirectoryExists();

            $filename = $this->generateBackupFilename();
            $filepath = $this->backupPath.'/'.$filename;

            $this->dumpFullBackup($filepath);

            Log::info("[BackupDataService] Backup completo criado: {$filename}");

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath,
                'message' => 'Backup criado com sucesso',
            ];
        } catch (Exception $e) {
            Log::error('[BackupDataService] Erro ao criar backup: '.$e->getMessage());

            return [
                'success' => false,
                'filename' => null,
                'path' => null,
                'message' => 'Erro ao criar backup: '.$e->getMessage(),
            ];
        }
    }

    protected function dumpFullBackup(string $filepath): void
    {
        $pdo = \DB::connection()->getPdo();
        $database = config('database.connections.mysql.database');

        $output = fopen($filepath, 'w');

        if ($output === false) {
            throw new Exception('Não foi possível criar o arquivo de backup');
        }

        fwrite($output, "-- Complete Backup (Structure + Data)\n");
        fwrite($output, "-- Database: {$database}\n");
        fwrite($output, '-- Generated: '.now()->toDateTimeString()."\n\n");
        fwrite($output, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($this->tables as $table) {
            $this->dumpTableWithStructure($output, $pdo, $table);
        }

        fwrite($output, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($output);
    }

    protected function dumpTableWithStructure($output, $pdo, string $table): void
    {
        try {
            $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        } catch (\Exception $e) {
            Log::warning("[BackupDataService] Tabela {$table} não existe, pulando");

            return;
        }

        fwrite($output, "-- Table: `{$table}`\n");

        try {
            $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            fwrite($output, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($output, $createTable['Create Table'].";\n\n");
        } catch (\Exception $e) {
            Log::warning("[BackupDataService] Não foi possível obter estrutura de {$table}");

            return;
        }

        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return;
        }

        fwrite($output, "-- Data from `{$table}`\n");

        $columns = array_keys($rows[0]);
        $columnList = implode('`, `', $columns);

        foreach (array_chunk($rows, 100) as $chunk) {
            $values = [];
            foreach ($chunk as $row) {
                $escaped = array_map(function ($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }

                    return $pdo->quote($value);
                }, array_values($row));

                $values[] = '('.implode(', ', $escaped).')';
            }

            fwrite($output, "INSERT INTO `{$table}` (`{$columnList}`) VALUES\n");
            fwrite($output, implode(",\n", $values).";\n\n");
        }
    }

    public function restoreDataBackup(string $filename): array
    {
        if (! $this->isValidFilename($filename)) {
            return ['success' => false, 'message' => 'Nome de arquivo inválido'];
        }

        $filepath = $this->backupPath.'/'.$filename;

        if (! File::exists($filepath)) {
            return ['success' => false, 'message' => 'Arquivo de backup não encontrado'];
        }

        try {
            $this->restoreFullBackup($filepath);

            Log::info("[BackupDataService] Backup restaurado: {$filename}");

            return [
                'success' => true,
                'message' => 'Backup restaurado com sucesso',
            ];
        } catch (Exception $e) {
            Log::error('[BackupDataService] Erro ao restaurar: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao restaurar: '.$e->getMessage(),
            ];
        }
    }

    protected function restoreFullBackup(string $filepath): void
    {
        $sql = file_get_contents($filepath);

        if ($sql === false) {
            throw new Exception('Não foi possível ler o arquivo de backup');
        }

        $pdo = \DB::connection()->getPdo();

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec('SET SQL_MODE=\'\'');
        $pdo->exec('SET unique_checks=0');

        $errors = [];
        $successCount = 0;
        $inTransaction = false;

        try {
            $pdo->beginTransaction();
            $inTransaction = true;

            foreach ($this->parseSqlStatements($sql) as $statement) {
                $trimmed = trim($statement);
                if (empty($trimmed)) {
                    continue;
                }

                try {
                    $pdo->exec($statement);
                    $successCount++;
                } catch (\Exception $e) {
                    $shortStmt = substr($trimmed, 0, 60);
                    $errors[] = "{$shortStmt}... -> {$e->getMessage()}";
                    Log::warning("[BackupDataService] Statement error: {$shortStmt}: {$e->getMessage()}");
                }
            }

            $pdo->commit();
            $inTransaction = false;
        } catch (\Exception $e) {
            if ($inTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Log::error('[BackupDataService] Transaction falhou: '.$e->getMessage());
            throw new Exception('Restauração falhou: '.$e->getMessage());
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $pdo->exec('SET SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\'');
            $pdo->exec('SET unique_checks=1');
        }

        Log::info("[BackupDataService] Restauração: {$successCount} statements OK, ".count($errors).' erros');
    }

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

    public function listBackups(): array
    {
        if (! File::isDirectory($this->backupPath)) {
            return [];
        }

        $files = File::files($this->backupPath);
        $backups = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'sql' && (str_starts_with($file->getFilename(), 'backup-') || str_starts_with($file->getFilename(), 'backup-db-'))) {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('d/m/Y H:i:s'),
                ];
            }
        }

        usort($backups, function ($a, $b) {
            return strcmp($b['filename'], $a['filename']);
        });

        return array_slice($backups, 0, 10);
    }

    public function uploadBackup($file): array
    {
        try {
            $this->ensureBackupDirectoryExists();

            $originalName = $file->getClientOriginalName();

            if (! $this->isValidFilename($originalName)) {
                return [
                    'success' => false,
                    'filename' => null,
                    'message' => 'Arquivo inválido',
                ];
            }

            $filename = 'backup-'.$originalName;

            if (File::exists($this->backupPath.'/'.$filename)) {
                $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                $filename = 'backup-'.$nameWithoutExt.'-'.time().'.sql';
            }

            $file->move($this->backupPath, $filename);

            Log::info("[BackupDataService] Upload: {$filename}");

            return [
                'success' => true,
                'filename' => $filename,
                'message' => 'Upload realizado',
            ];
        } catch (Exception $e) {
            Log::error('[BackupDataService] Upload erro: '.$e->getMessage());

            return [
                'success' => false,
                'filename' => null,
                'message' => 'Erro: '.$e->getMessage(),
            ];
        }
    }

    public function deleteBackup(string $filename): bool
    {
        if (! $this->isValidFilename($filename)) {
            return false;
        }

        $filepath = $this->backupPath.'/'.$filename;

        if (! File::exists($filepath)) {
            return false;
        }

        try {
            File::delete($filepath);

            return true;
        } catch (Exception $e) {
            Log::error('[BackupDataService] Delete erro: '.$e->getMessage());

            return false;
        }
    }

    public function getBackupFilePath(string $filename): ?string
    {
        if (! $this->isValidFilename($filename)) {
            return null;
        }

        $filepath = $this->backupPath.'/'.$filename;

        return File::exists($filepath) ? $filepath : null;
    }

    public function isValidFilename(string $filename): bool
    {
        if (str_contains($filename, '..') || str_contains($filename, '/')) {
            return false;
        }

        if (! str_ends_with($filename, '.sql')) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9._-]+\.sql$/', $filename) === 1;
    }

    protected function generateBackupFilename(): string
    {
        $timestamp = now()->format('Y-m-d-His');

        return "backup-db-{$timestamp}.sql";
    }

    protected function ensureBackupDirectoryExists(): void
    {
        if (! File::isDirectory($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }
}
