<?php

namespace Tests\Unit\Models;

use App\Models\AgencyFixedCost;
use App\Models\CostCenter;
use App\Models\GigCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CostCenterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_gig_costs_relationship()
    {
        $costCenter = CostCenter::factory()->create();
        $gigCost = GigCost::factory()->create([
            'cost_center_id' => $costCenter->id,
        ]);

        $this->assertTrue($costCenter->gigCosts()->exists());
        $this->assertEquals(1, $costCenter->gigCosts()->count());
        $this->assertTrue($costCenter->gigCosts->contains($gigCost));
    }

    #[Test]
    public function it_has_agency_fixed_costs_relationship()
    {
        $costCenter = CostCenter::factory()->create();
        $agencyCost = AgencyFixedCost::factory()->create([
            'cost_center_id' => $costCenter->id,
        ]);

        $this->assertTrue($costCenter->agencyFixedCosts()->exists());
        $this->assertEquals(1, $costCenter->agencyFixedCosts()->count());
        $this->assertTrue($costCenter->agencyFixedCosts->contains($agencyCost));
    }

    #[Test]
    public function it_counts_both_gig_costs_and_agency_costs()
    {
        $costCenter = CostCenter::factory()->create();

        // Create 3 GigCosts
        GigCost::factory()->count(3)->create([
            'cost_center_id' => $costCenter->id,
        ]);

        // Create 2 AgencyFixedCosts
        AgencyFixedCost::factory()->count(2)->create([
            'cost_center_id' => $costCenter->id,
        ]);

        $costCenter->loadCount(['gigCosts', 'agencyFixedCosts']);

        $this->assertEquals(3, $costCenter->gig_costs_count);
        $this->assertEquals(2, $costCenter->agency_fixed_costs_count);
        $this->assertEquals(5, $costCenter->gig_costs_count + $costCenter->agency_fixed_costs_count);
    }

    #[Test]
    public function it_can_query_with_costs_count()
    {
        $costCenter1 = CostCenter::factory()->create(['name' => 'Center 1']);
        $costCenter2 = CostCenter::factory()->create(['name' => 'Center 2']);

        GigCost::factory()->create(['cost_center_id' => $costCenter1->id]);
        AgencyFixedCost::factory()->create(['cost_center_id' => $costCenter1->id]);
        AgencyFixedCost::factory()->create(['cost_center_id' => $costCenter2->id]);

        $centers = CostCenter::withCount(['gigCosts', 'agencyFixedCosts'])->get();

        $center1 = $centers->firstWhere('name', 'Center 1');
        $center2 = $centers->firstWhere('name', 'Center 2');

        $this->assertEquals(1, $center1->gig_costs_count);
        $this->assertEquals(1, $center1->agency_fixed_costs_count);
        $this->assertEquals(0, $center2->gig_costs_count);
        $this->assertEquals(1, $center2->agency_fixed_costs_count);
    }

    #[Test]
    public function active_scope_filters_active_cost_centers()
    {
        CostCenter::factory()->create(['is_active' => true]);
        CostCenter::factory()->create(['is_active' => false]);

        $activeCenters = CostCenter::active()->get();

        $this->assertCount(1, $activeCenters);
        $this->assertTrue($activeCenters->first()->is_active);
    }

    #[Test]
    public function inactive_scope_filters_inactive_cost_centers()
    {
        CostCenter::factory()->create(['is_active' => true]);
        CostCenter::factory()->create(['is_active' => false]);

        $inactiveCenters = CostCenter::inactive()->get();

        $this->assertCount(1, $inactiveCenters);
        $this->assertFalse($inactiveCenters->first()->is_active);
    }
}
