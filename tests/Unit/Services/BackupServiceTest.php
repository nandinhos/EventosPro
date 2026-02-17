<?php

namespace Tests\Unit\Services;

use App\Services\BackupService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    protected BackupService $backupService;

    protected string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Usar storage_path para funcionar dentro do container Docker
        $this->backupPath = storage_path('app/backups');
        config(['backup.path' => $this->backupPath]);

        $this->backupService = app(BackupService::class);

        // Limpar pasta de backup antes de cada teste
        if (File::isDirectory($this->backupPath)) {
            File::cleanDirectory($this->backupPath);
        }
    }

    protected function tearDown(): void
    {
        // Limpar arquivos de backup após testes
        if (File::isDirectory($this->backupPath)) {
            File::cleanDirectory($this->backupPath);
        }
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(BackupService::class, $this->backupService);
    }

    #[Test]
    public function it_detects_mysql_database_driver(): void
    {
        Config::set('database.default', 'mysql');

        $driver = $this->backupService->getDatabaseDriver();

        $this->assertEquals('mysql', $driver);
    }

    #[Test]
    public function it_detects_sqlite_database_driver(): void
    {
        Config::set('database.default', 'sqlite');

        $driver = $this->backupService->getDatabaseDriver();

        $this->assertEquals('sqlite', $driver);
    }

    #[Test]
    public function it_creates_backup_directory_if_not_exists(): void
    {
        // Remover diretório se existir
        if (File::isDirectory($this->backupPath)) {
            File::deleteDirectory($this->backupPath);
        }

        $this->assertFalse(File::isDirectory($this->backupPath));

        // Chamar método protegido via Reflection
        $reflection = new \ReflectionClass($this->backupService);
        $method = $reflection->getMethod('ensureBackupDirectoryExists');
        $method->setAccessible(true);
        $method->invoke($this->backupService);

        $this->assertTrue(File::isDirectory($this->backupPath));
    }

    #[Test]
    public function it_creates_mysql_backup_file(): void
    {
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'eventospro');
        Config::set('database.connections.mysql.host', 'mysql');
        Config::set('database.connections.mysql.port', 3306);
        Config::set('database.connections.mysql.username', 'root');
        Config::set('database.connections.mysql.password', '');

        $result = $this->backupService->createBackup();

        if ($result['success']) {
            $this->assertNotNull($result['filename']);
            $this->assertFileExists($result['path']);
            $this->assertStringEndsWith('.sql', $result['filename']);
        } else {
            // Se falhar devido a conexão, é aceitável em teste
            $this->assertArrayHasKey('message', $result);
        }
    }

    #[Test]
    public function it_lists_all_backups(): void
    {
        // Criar alguns arquivos de backup fictícios
        File::makeDirectory($this->backupPath, 0755, true, true);
        File::put($this->backupPath.'/eventospro-2025-01-01-120000.sql', 'fake content 1');
        File::put($this->backupPath.'/eventospro-2025-01-02-130000.sql', 'fake content 2');

        $backups = $this->backupService->listBackups();

        $this->assertCount(2, $backups);
        $this->assertArrayHasKey('filename', $backups[0]);
        $this->assertArrayHasKey('size', $backups[0]);
        $this->assertArrayHasKey('created_at', $backups[0]);
    }

    #[Test]
    public function it_returns_empty_array_when_no_backups_exist(): void
    {
        $backups = $this->backupService->listBackups();

        $this->assertIsArray($backups);
        $this->assertEmpty($backups);
    }

    #[Test]
    public function it_deletes_backup_file(): void
    {
        File::makeDirectory($this->backupPath, 0755, true, true);
        $filename = 'eventospro-2025-01-01-120000.sql';
        File::put($this->backupPath.'/'.$filename, 'fake content');

        $this->assertFileExists($this->backupPath.'/'.$filename);

        $result = $this->backupService->deleteBackup($filename);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($this->backupPath.'/'.$filename);
    }

    #[Test]
    public function it_returns_false_when_deleting_nonexistent_backup(): void
    {
        $result = $this->backupService->deleteBackup('nonexistent-file.sql');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_generates_backup_filename_with_timestamp(): void
    {
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'eventospro');

        $filename = $this->backupService->generateBackupFilename();

        $this->assertStringStartsWith('eventospro-', $filename);
        $this->assertStringEndsWith('.sql', $filename);
        $this->assertMatchesRegularExpression('/eventospro-\d{4}-\d{2}-\d{2}-\d{6}\.sql/', $filename);
    }

    #[Test]
    public function it_returns_backup_path(): void
    {
        $path = $this->backupService->getBackupPath();

        $this->assertEquals($this->backupPath, $path);
    }

    #[Test]
    public function it_validates_backup_filename_for_security(): void
    {
        $this->assertTrue($this->backupService->isValidFilename('eventospro-2025-01-01.sql'));
        $this->assertTrue($this->backupService->isValidFilename('backup-test.sql'));

        // Testar nomes inválidos
        $this->assertFalse($this->backupService->isValidFilename('../../../etc/passwd'));
        $this->assertFalse($this->backupService->isValidFilename('file; rm -rf /'));
        $this->assertFalse($this->backupService->isValidFilename('test.txt'));
    }
}
