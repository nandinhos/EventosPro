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
        'performance_reports',
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

            $this->dumpDataOnly($filepath);

            Log::info("[BackupDataService] Backup de dados criado: {$filename}");

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath,
                'message' => 'Backup de dados criado com sucesso',
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

    protected function dumpDataOnly(string $filepath): void
    {
        $pdo = \DB::connection()->getPdo();
        $database = config('database.connections.mysql.database');

        $output = fopen($filepath, 'w');

        if ($output === false) {
            throw new Exception('Não foi possível criar o arquivo de backup');
        }

        fwrite($output, "-- Data Only Backup (with DELETE before INSERT)\n");
        fwrite($output, "-- Database: {$database}\n");
        fwrite($output, '-- Generated: '.now()->toDateTimeString()."\n\n");
        fwrite($output, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($this->tables as $table) {
            $this->dumpTableData($output, $pdo, $table);
        }

        fwrite($output, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($output);
    }

    protected function dumpTableData($output, $pdo, string $table): void
    {
        try {
            $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        } catch (\Exception $e) {
            Log::warning("[BackupDataService] Tabela {$table} não existe, pulando");

            return;
        }

        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);

        fwrite($output, "-- Data from `{$table}`\n");
        fwrite($output, "DELETE FROM `{$table}`;\n");

        if (empty($rows)) {
            fwrite($output, "\n");

            return;
        }

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
            $this->restoreDataOnly($filepath);

            Log::info("[BackupDataService] Backup restaurado: {$filename}");

            return [
                'success' => true,
                'message' => 'Backup restaurado com sucesso (dados apenas)',
            ];
        } catch (Exception $e) {
            Log::error('[BackupDataService] Erro ao restaurar: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao restaurar: '.$e->getMessage(),
            ];
        }
    }

    protected function restoreDataOnly(string $filepath): void
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

        try {
            $pdo->beginTransaction();

            foreach ($this->parseSqlStatements($sql) as $statement) {
                try {
                    $pdo->exec($statement);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = 'Erro: '.substr($statement, 0, 80).' - '.$e->getMessage();
                    Log::warning('[BackupDataService] Erro statement: '.$e->getMessage());
                }
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            Log::error('[BackupDataService] Transaction falhou: '.$e->getMessage());
            throw $e;
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $pdo->exec('SET SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\'');
            $pdo->exec('SET unique_checks=1');
        }

        Log::info("[BackupDataService] Restauração: {$successCount} statementsOK");

        if (! empty($errors)) {
            Log::warning('[BackupDataService] Alguns erros: '.implode('; ', array_slice($errors, 0, 3)));
        }

        $pdo = \DB::connection()->getPdo();

        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec('SET SQL_MODE=\'\'');

        $errors = [];
        $successCount = 0;

        try {
            foreach ($this->parseSqlStatements($sql) as $statement) {
                try {
                    $pdo->exec($statement);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = 'Erro na语句: '.substr($statement, 0, 100).' - '.$e->getMessage();
                    Log::warning('[BackupDataService] Erro ao executar statement: '.$e->getMessage());
                }
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $pdo->exec('SET SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\'');
        }

        Log::info("[BackupDataService] Restauração concluída: {$successCount} statements executados");

        if (! empty($errors)) {
            Log::warning('[BackupDataService] Erros durante restauração: '.implode('; ', array_slice($errors, 0, 5)));
        }
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
            if ($file->getExtension() === 'sql' && str_starts_with($file->getFilename(), 'data-')) {
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
                    'message' => 'O arquivo deve ter extensão .sql e nome válido',
                ];
            }

            $filename = 'data-'.$originalName;

            if (File::exists($this->backupPath.'/'.$filename)) {
                $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                $filename = 'data-'.$nameWithoutExt.'-'.time().'.sql';
            }

            $file->move($this->backupPath, $filename);

            Log::info("[BackupDataService] Upload realizado: {$filename}");

            return [
                'success' => true,
                'filename' => $filename,
                'message' => 'Upload realizado com sucesso',
            ];
        } catch (Exception $e) {
            Log::error('[BackupDataService] Erro no upload: '.$e->getMessage());

            return [
                'success' => false,
                'filename' => null,
                'message' => 'Erro ao salvar arquivo: '.$e->getMessage(),
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
            Log::error('[BackupDataService] Erro ao deletar: '.$e->getMessage());

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
        $database = config('database.connections.mysql.database', 'database');
        $timestamp = now()->format('Y-m-d-His');

        return "data-{$database}-{$timestamp}.sql";
    }

    protected function ensureBackupDirectoryExists(): void
    {
        if (! File::isDirectory($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }
}
