<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GigControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected Artist $artist;

    protected Booker $booker;

    protected CostCenter $costCenter;

    protected Tag $tag;

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
        $this->artist = Artist::factory()->create();
        $this->booker = Booker::factory()->create();
        $this->costCenter = CostCenter::factory()->create();
        $this->tag = Tag::factory()->create();

        // Mock logs para evitar problemas com observers
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
    }

    #[Test]
    public function index_displays_gigs_list()
    {
        $gigs = Gig::factory()->count(3)->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
        ]);

        $response = $this->get(route('gigs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('gigs.index');
        $response->assertViewHas('gigs');
        $response->assertSee($this->artist->name);
    }

    #[Test]
    public function index_filters_by_search_term()
    {
        $gig1 = Gig::factory()->create([
            'contract_number' => 'SEARCH123',
            'artist_id' => $this->artist->id,
        ]);

        $gig2 = Gig::factory()->create([
            'contract_number' => 'OTHER456',
            'artist_id' => $this->artist->id,
        ]);

        $response = $this->get(route('gigs.index', ['search' => 'SEARCH123']));

        $response->assertStatus(200);
        $response->assertSee('SEARCH123');
        $response->assertDontSee('OTHER456');
    }

    #[Test]
    public function index_filters_by_payment_status()
    {
        $gigPago = Gig::factory()->create([
            'payment_status' => 'pago',
            'artist_id' => $this->artist->id,
            'location_event_details' => 'Evento Pago Test',
        ]);

        $gigVencido = Gig::factory()->create([
            'payment_status' => 'vencido',
            'artist_id' => $this->artist->id,
            'location_event_details' => 'Evento Vencido Test',
        ]);

        $response = $this->get(route('gigs.index', ['payment_status' => 'pago']));

        $response->assertStatus(200);
        $response->assertSee('Evento Pago Test');
        $response->assertDontSee('Evento Vencido Test');
    }

    #[Test]
    public function index_sorts_by_different_columns()
    {
        $gig1 = Gig::factory()->create([
            'gig_date' => '2024-01-01',
            'artist_id' => $this->artist->id,
        ]);

        $gig2 = Gig::factory()->create([
            'gig_date' => '2024-02-01',
            'artist_id' => $this->artist->id,
        ]);

        $response = $this->get(route('gigs.index', [
            'sort_by' => 'gig_date',
            'sort_direction' => 'asc',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('sortBy', 'gig_date');
        $response->assertViewHas('sortDirection', 'asc');
    }

    #[Test]
    public function show_displays_gig_details()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
        ]);

        $response = $this->get(route('gigs.show', $gig));

        $response->assertStatus(200);
        $response->assertViewIs('gigs.show');
        $response->assertViewHas('gig');
        $response->assertViewHas('financialData');
        $response->assertSee($gig->contract_number);
    }

    #[Test]
    public function show_loads_financial_data()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'cache_value' => 1000,
            'currency' => 'BRL',
        ]);

        $response = $this->get(route('gigs.show', $gig));

        $response->assertStatus(200);
        $response->assertViewHas('financialData');

        $financialData = $response->viewData('financialData');
        $this->assertArrayHasKey('totalReceivedInOriginalCurrency', $financialData);
        $this->assertArrayHasKey('pendingBalanceInOriginalCurrency', $financialData);
        $this->assertArrayHasKey('calculatedGrossCashBrl', $financialData);
    }

    #[Test]
    public function create_displays_form()
    {
        $response = $this->get(route('gigs.create'));

        $response->assertStatus(200);
        $response->assertViewIs('gigs.create');
        $response->assertViewHas(['artists', 'bookers', 'tags', 'costCenters']);
    }

    #[Test]
    public function store_creates_new_gig()
    {
        $gigData = [
            'contract_number' => 'TEST123',
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'gig_date' => '2024-12-31',
            'cache_value' => 1000,
            'currency' => 'BRL',
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => 20,
            'booker_commission_type' => 'percent',
            'booker_commission_rate' => 5,
            'location_event_details' => 'Test Event',
            'contract_status' => 'assinado',
            'payment_status' => 'a_vencer',
        ];

        $response = $this->post(route('gigs.store'), $gigData);

        $response->assertRedirect();
        $this->assertDatabaseHas('gigs', [
            'contract_number' => 'TEST123',
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
        ]);
    }

    #[Test]
    public function store_creates_gig_with_expenses()
    {
        $gigData = [
            'contract_number' => 'TEST123',
            'artist_id' => $this->artist->id,
            'gig_date' => '2024-12-31',
            'cache_value' => 1000,
            'currency' => 'BRL',
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => 20,
            'location_event_details' => 'Test Event',
            'contract_status' => 'assinado',
            'payment_status' => 'a_vencer',
            'expenses' => [
                [
                    'cost_center_id' => $this->costCenter->id,
                    'description' => 'Test Expense',
                    'value' => 100,
                    'currency' => 'BRL',
                    'expense_date' => '2024-12-31',
                    'is_confirmed' => true,
                ],
            ],
        ];

        $response = $this->post(route('gigs.store'), $gigData);

        $response->assertRedirect();
        $this->assertDatabaseHas('gigs', ['contract_number' => 'TEST123']);
        $this->assertDatabaseHas('gig_costs', [
            'description' => 'Test Expense',
            'value' => 100,
        ]);
    }

    #[Test]
    public function store_creates_gig_with_tags()
    {
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $gigData = [
            'contract_number' => 'TEST123',
            'artist_id' => $this->artist->id,
            'gig_date' => '2024-12-31',
            'cache_value' => 1000,
            'currency' => 'BRL',
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => 20,
            'location_event_details' => 'Test Event',
            'contract_status' => 'assinado',
            'payment_status' => 'a_vencer',
            'tags' => [$tag1->id, $tag2->id],
        ];

        $response = $this->post(route('gigs.store'), $gigData);

        $response->assertRedirect();
        $gig = Gig::where('contract_number', 'TEST123')->first();
        $this->assertCount(2, $gig->tags);
        $this->assertTrue($gig->tags->contains($tag1));
        $this->assertTrue($gig->tags->contains($tag2));
    }

    #[Test]
    public function store_validates_required_fields()
    {
        $response = $this->post(route('gigs.store'), []);

        $response->assertSessionHasErrors([
            'artist_id',
            'gig_date',
            'location_event_details',
            'cache_value',
            'currency',
        ]);
    }

    #[Test]
    public function edit_displays_form_with_gig_data()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
        ]);

        $response = $this->get(route('gigs.edit', $gig));

        $response->assertStatus(200);
        $response->assertViewIs('gigs.edit');
        $response->assertViewHas('gig', $gig);
        $response->assertViewHas(['artists', 'bookers', 'tags', 'costCenters']);
    }

    #[Test]
    public function update_modifies_existing_gig()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'contract_number' => 'OLD123',
        ]);

        $updateData = [
            'contract_number' => 'NEW123',
            'artist_id' => $this->artist->id,
            'gig_date' => $gig->gig_date->format('Y-m-d'),
            'cache_value' => 2000,
            'currency' => 'BRL',
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => 25,
            'location_event_details' => 'Updated Event',
            'contract_status' => 'assinado',
            'payment_status' => 'a_vencer',
        ];

        $response = $this->put(route('gigs.update', $gig), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('gigs', [
            'id' => $gig->id,
            'contract_number' => 'NEW123',
            'cache_value' => 2000,
        ]);
    }

    #[Test]
    public function update_handles_expenses()
    {
        $gig = Gig::factory()->create(['artist_id' => $this->artist->id]);
        $existingCost = GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $this->costCenter->id,
        ]);

        $updateData = [
            'contract_number' => $gig->contract_number,
            'artist_id' => $this->artist->id,
            'gig_date' => $gig->gig_date->format('Y-m-d'),
            'cache_value' => $gig->cache_value,
            'currency' => $gig->currency,
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => 20,
            'location_event_details' => $gig->location_event_details,
            'contract_status' => $gig->contract_status,
            'payment_status' => $gig->payment_status,
            'expenses' => [
                [
                    'id' => $existingCost->id,
                    'cost_center_id' => $this->costCenter->id,
                    'description' => 'Updated Expense',
                    'value' => 200,
                    'currency' => 'BRL',
                    'expense_date' => '2024-12-31',
                    'is_confirmed' => true,
                ],
            ],
        ];

        $response = $this->put(route('gigs.update', $gig), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('gig_costs', [
            'id' => $existingCost->id,
            'description' => 'Updated Expense',
            'value' => 200,
        ]);
    }

    #[Test]
    public function destroy_deletes_gig()
    {
        $gig = Gig::factory()->create(['artist_id' => $this->artist->id]);

        $response = $this->delete(route('gigs.destroy', $gig));

        $response->assertRedirect(route('gigs.index'));
        $this->assertSoftDeleted('gigs', ['id' => $gig->id]);
    }

    #[Test]
    public function show_request_nf_form_displays_correctly()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'cache_value' => 1000,
            'currency' => 'BRL',
        ]);

        $response = $this->get(route('gigs.request-nf', $gig));

        $response->assertStatus(200);
        $response->assertViewIs('gigs.request-nf');
        $response->assertViewHas('gig', $gig);
        $response->assertViewHas([
            'gigCacheValueBrl',
            'totalConfirmedExpensesBrl',
            'calculatedGrossCashBrl',
            'finalArtistInvoiceValueBrl',
        ]);
    }

    #[Test]
    public function debug_financials_displays_calculations()
    {
        $gig = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'cache_value' => 1000,
            'currency' => 'BRL',
        ]);

        $response = $this->get(route('gigs.debugFinancials', $gig));

        $response->assertStatus(200);
        $response->assertViewIs('gigs.debug.financials');
        $response->assertViewHas('gig', $gig);
        $response->assertViewHas('calculations');
        $response->assertViewHas('cacheBrlDetails');
    }

    #[Test]
    public function store_handles_transaction_rollback_on_error()
    {
        // Simular erro forçando dados inválidos
        $gigData = [
            'contract_number' => 'TEST123',
            'artist_id' => 99999, // ID inexistente
            'gig_date' => '2024-12-31',
            'cache_value' => 1000,
            'currency' => 'BRL',
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => 20,
            'location_event_details' => 'Test Event',
            'contract_status' => 'assinado',
            'payment_status' => 'a_vencer',
        ];

        $response = $this->post(route('gigs.store'), $gigData);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('gigs', ['contract_number' => 'TEST123']);
    }

    #[Test]
    public function index_handles_booker_filter_without_booker()
    {
        $gigWithBooker = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => $this->booker->id,
            'contract_number' => 'WITH-BOOKER-123',
        ]);

        $gigWithoutBooker = Gig::factory()->create([
            'artist_id' => $this->artist->id,
            'booker_id' => null,
            'contract_number' => 'NO-BOOKER-456',
        ]);

        $response = $this->get(route('gigs.index', ['booker_id' => 'sem_booker']));

        $response->assertStatus(200);

        // Check that the gig without booker is in the results
        $response->assertViewHas('gigs', function ($gigs) use ($gigWithoutBooker) {
            return $gigs->contains('id', $gigWithoutBooker->id);
        });

        // Check that the gig with booker is NOT in the results
        $response->assertViewHas('gigs', function ($gigs) use ($gigWithBooker) {
            return ! $gigs->contains('id', $gigWithBooker->id);
        });
    }

    #[Test]
    public function show_saves_url_params_in_session()
    {
        $gig = Gig::factory()->create(['artist_id' => $this->artist->id]);

        $params = [
            'search' => 'test',
            'payment_status' => 'pago',
            'page' => 2,
        ];

        $response = $this->get(route('gigs.show', array_merge(['gig' => $gig], $params)));

        $response->assertStatus(200);
        $response->assertSessionHas('gig_index_url_params', $params);
    }

    #[Test]
    public function update_removes_deleted_expenses()
    {
        $gig = Gig::factory()->create(['artist_id' => $this->artist->id]);
        $costToDelete = GigCost::factory()->create([
            'gig_id' => $gig->id,
            'cost_center_id' => $this->costCenter->id,
        ]);

        $updateData = [
            'contract_number' => $gig->contract_number,
            'artist_id' => $this->artist->id,
            'gig_date' => $gig->gig_date->format('Y-m-d'),
            'cache_value' => $gig->cache_value,
            'currency' => $gig->currency,
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => 20,
            'location_event_details' => $gig->location_event_details,
            'contract_status' => $gig->contract_status,
            'payment_status' => $gig->payment_status,
            'expenses' => [
                [
                    'id' => $costToDelete->id,
                    'cost_center_id' => $this->costCenter->id,
                    'description' => 'To be deleted',
                    'value' => 100,
                    'currency' => 'BRL',
                    'expense_date' => '2024-12-31',
                    'is_confirmed' => false,
                    '_deleted' => true,
                ],
            ],
        ];

        $response = $this->put(route('gigs.update', $gig), $updateData);

        $response->assertRedirect();
        $this->assertSoftDeleted('gig_costs', ['id' => $costToDelete->id]);
    }
}
