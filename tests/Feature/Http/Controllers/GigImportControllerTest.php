<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GigImportControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected Artist $artist;

    protected Booker $booker;

    protected CostCenter $costCenter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles first
        Role::create(['name' => 'ADMIN']);
        Role::create(['name' => 'DIRETOR']);
        Role::create(['name' => 'BOOKER']);

        // Criar usuário autenticado
        $this->user = User::factory()->create();
        $this->user->assignRole('ADMIN');
        $this->actingAs($this->user);

        // Criar dados de teste
        $this->artist = Artist::factory()->create(['name' => 'Maria Bethânia']);
        $this->booker = Booker::factory()->create(['name' => 'João Produções']);
        $this->costCenter = CostCenter::factory()->create(['name' => 'Hospedagem']);

        // Mock logs para evitar problemas com observers
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
    }

    #[Test]
    public function import_form_is_accessible()
    {
        $response = $this->get(route('gigs.import.form'));

        $response->assertStatus(200);
        $response->assertViewIs('gigs.import');
        $response->assertViewHas('expectedColumns');
        $response->assertSee('Importar Gigs em Massa');
    }

    #[Test]
    public function template_download_works()
    {
        $response = $this->get(route('gigs.import.template'));

        $response->assertStatus(200);
        $response->assertDownload('template_importacao_gigs.xlsx');
    }

    #[Test]
    public function preview_requires_file()
    {
        $response = $this->post(route('gigs.import.preview'), []);

        $response->assertSessionHasErrors('file');
    }

    #[Test]
    public function preview_rejects_invalid_file_type()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->post(route('gigs.import.preview'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    #[Test]
    public function import_redirects_without_session_file()
    {
        $response = $this->post(route('gigs.import'));

        $response->assertRedirect(route('gigs.import.form'));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function gig_index_shows_import_button()
    {
        $response = $this->get(route('gigs.index'));

        $response->assertStatus(200);
        $response->assertSee('Importar');
        $response->assertSee(route('gigs.import.form'));
    }
}
