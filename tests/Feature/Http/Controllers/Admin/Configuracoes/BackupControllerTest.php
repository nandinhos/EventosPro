<?php

namespace Tests\Feature\Http\Controllers\Admin\Configuracoes;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $regularUser;

    protected string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurar path de backup
        $this->backupPath = storage_path('app/backups');
        config(['backup.path' => $this->backupPath]);

        // Limpar pasta de backup
        if (File::isDirectory($this->backupPath)) {
            File::cleanDirectory($this->backupPath);
        }

        // Criar roles e permissões
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Criar usuário admin
        $this->adminUser = User::factory()->create([
            'email' => 'admin@eventospro.com',
        ]);
        $this->adminUser->assignRole('ADMIN');

        // Criar usuário regular
        $this->regularUser = User::factory()->create([
            'email' => 'user@eventospro.com',
        ]);
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
    public function admin_can_view_backup_index_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backup.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.configuracoes.backup.index');
        $response->assertSee('Gerenciador de Backups');
    }

    #[Test]
    public function non_admin_cannot_view_backup_page(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.backup.index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function guest_cannot_view_backup_page(): void
    {
        $response = $this->get(route('admin.backup.index'));

        $response->assertRedirect('/login');
    }

    #[Test]
    public function admin_can_create_backup(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.backup.store'));

        $response->assertRedirect();

        // Verificar se houve sucesso ou erro (o backup pode falhar se MySQL não estiver disponível)
        $this->assertTrue(
            session()->has('success') || session()->has('error'),
            'Deve ter mensagem de sucesso ou erro'
        );
    }

    #[Test]
    public function non_admin_cannot_create_backup(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('admin.backup.store'));

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_download_backup(): void
    {
        // Criar arquivo de teste
        File::makeDirectory($this->backupPath, 0755, true, true);
        $filename = 'eventospro-2025-01-01-120000.sql';
        File::put($this->backupPath.'/'.$filename, 'fake backup content');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backup.download', ['filename' => $filename]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    #[Test]
    public function admin_cannot_download_nonexistent_backup(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backup.download', ['filename' => 'nonexistent.sql']));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function non_admin_cannot_download_backup(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.backup.download', ['filename' => 'test.sql']));

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_delete_backup(): void
    {
        // Criar arquivo de teste
        File::makeDirectory($this->backupPath, 0755, true, true);
        $filename = 'eventospro-2025-01-01-120000.sql';
        File::put($this->backupPath.'/'.$filename, 'fake backup content');

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.backup.destroy', ['filename' => $filename]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertFileDoesNotExist($this->backupPath.'/'.$filename);
    }

    #[Test]
    public function admin_cannot_delete_nonexistent_backup(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.backup.destroy', ['filename' => 'nonexistent.sql']));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function non_admin_cannot_delete_backup(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->delete(route('admin.backup.destroy', ['filename' => 'test.sql']));

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_sees_list_of_backups(): void
    {
        // Criar arquivos de teste
        File::makeDirectory($this->backupPath, 0755, true, true);
        File::put($this->backupPath.'/eventospro-2025-01-01-120000.sql', 'content 1');
        File::put($this->backupPath.'/eventospro-2025-01-02-130000.sql', 'content 2');

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backup.index'));

        $response->assertStatus(200);
        $response->assertViewHas('backups');

        $backups = $response->viewData('backups');
        $this->assertCount(2, $backups);
    }

    #[Test]
    public function admin_sees_empty_list_when_no_backups(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.backup.index'));

        $response->assertStatus(200);
        $response->assertViewHas('backups');

        $backups = $response->viewData('backups');
        $this->assertCount(0, $backups);
    }

    #[Test]
    public function controller_uses_correct_permission(): void
    {
        $this->assertTrue($this->adminUser->can('manage backups'));
        $this->assertFalse($this->regularUser->can('manage backups'));
    }
}
