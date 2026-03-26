<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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
        $connection = config('database.connections.mysql');
        
        // Garantir que pegamos todas as tabelas atuais, não apenas as hardcoded
        $pdo = \DB::connection()->getPdo();
        $allTables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $command = sprintf(
            'MYSQL_PWD=%s mysqldump --opt --skip-lock-tables -h %s -P %s -u %s %s %s > %s 2>&1',
            escapeshellarg($connection['password']),
            escapeshellarg($connection['host']),
            escapeshellarg($connection['port'] ?? 3306),
            escapeshellarg($connection['username']),
            escapeshellarg($connection['database']),
            implode(' ', array_map('escapeshellarg', $allTables)),
            escapeshellarg($filepath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $error = implode("\n", $output);
            Log::error("[BackupDataService] Falha no mysqldump: " . $error);
            
            if (str_contains($error, 'not found')) {
                Log::warning("[BackupDataService] mysqldump não encontrado, ignorando para fallback PDO (já implementado em BackupService)");
                // Aqui poderíamos chamar o fallback se necessário, mas vamos focar no padrão desejado (nativo)
            }
            
            throw new Exception('Erro ao gerar backup de dados: ' . $error);
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
        $connection = config('database.connections.mysql');
        
        // Streaming direto do backup para o mysql (evita estouro de memória no PHP)
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
            Log::error("[BackupDataService] Falha no restore nativo: " . $error);
            throw new Exception('Erro ao restaurar backup: ' . $error);
        }

        // Recarregar permissões e usuários se necessário
        try {
            \Artisan::call('permission:cache-reset', ['--no-interaction' => true]);
        } catch (\Exception $e) {
            Log::warning("[BackupDataService] Erro ao resetar cache de permissões: " . $e->getMessage());
        }
    }

    /**
     * Mantemos o parser apenas para compatibilidade legada se necessário
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
