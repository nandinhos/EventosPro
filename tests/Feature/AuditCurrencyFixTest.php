<?php

namespace Tests\Feature;

use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditCurrencyFixTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_fix_cost_currency_mismatch_via_http_request()
    {
        // 1. Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $costCenter = CostCenter::factory()->create();
        $gig = Gig::factory()->create(['currency' => 'USD']);
        $cost = GigCost::factory()->create([
            'gig_id' => $gig->id,
            'currency' => 'BRL',
            'cost_center_id' => $costCenter->id,
        ]);

        // 2. Run audit to generate report
        Artisan::call('gig:audit-currency', ['--scan-only' => true]);

        $files = glob(storage_path('logs/audit_currency_*.json'));
        $this->assertNotEmpty($files, 'No audit report file found.');
        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));
        $latestFile = $files[0];
        $report = json_decode(file_get_contents($latestFile), true);

        $issue = null;
        foreach ($report['issues'] as $gigIssue) {
            if ($gigIssue['gig_id'] === $gig->id) {
                foreach ($gigIssue['issues'] as $i) {
                    if ($i['type'] === 'cost_currency_mismatch') {
                        $issue = $i;
                        break 2;
                    }
                }
            }
        }

        $this->assertNotNull($issue, 'Cost currency mismatch issue not found in report.');

        // 3. Act
        $response = $this->post(route('audit.apply-fix'), [
            'gig_id' => $gig->id,
            'field' => $issue['field'],
            'new_value' => $issue['suggested_value'],
            'issue_type' => $issue['type'],
            'relation_id' => $issue['cost_id'],
        ]);

        // 4. Assert
        $response->assertSuccessful();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('gig_costs', [
            'id' => $cost->id,
            'currency' => 'USD',
        ]);
    }
}
