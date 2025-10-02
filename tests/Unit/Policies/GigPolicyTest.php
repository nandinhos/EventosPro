<?php

namespace Tests\Unit\Policies;

use App\Models\Gig;
use App\Models\User;
use App\Policies\GigPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GigPolicyTest extends TestCase
{
    use RefreshDatabase;

    private GigPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new GigPolicy();
        
        // Create roles
        Role::create(['name' => 'ADMIN']);
        Role::create(['name' => 'DIRETOR']);
        Role::create(['name' => 'BOOKER']);
    }

    #[Test]
    public function admin_can_view_any_gigs()
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function diretor_can_view_any_gigs()
    {
        $user = User::factory()->create();
        $user->assignRole('DIRETOR');

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function booker_can_view_any_gigs()
    {
        $user = User::factory()->create();
        $user->assignRole('BOOKER');

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function user_without_role_cannot_view_any_gigs()
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
    }

    #[Test]
    public function admin_can_view_any_gig()
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');
        $gig = Gig::factory()->create();

        $this->assertTrue($this->policy->view($user, $gig));
    }

    #[Test]
    public function diretor_can_view_any_gig()
    {
        $user = User::factory()->create();
        $user->assignRole('DIRETOR');
        $gig = Gig::factory()->create();

        $this->assertTrue($this->policy->view($user, $gig));
    }

    #[Test]
    public function booker_can_view_own_gig()
    {
        $user = User::factory()->create();
        $user->assignRole('BOOKER');
        $gig = Gig::factory()->create(['booker_id' => $user->booker_id]);

        $this->assertTrue($this->policy->view($user, $gig));
    }

    #[Test]
    public function booker_cannot_view_other_booker_gig()
    {
        $user = User::factory()->create();
        $user->assignRole('BOOKER');
        
        // Create another booker and gig
        $otherBooker = \App\Models\Booker::factory()->create();
        $gig = Gig::factory()->create(['booker_id' => $otherBooker->id]);

        $this->assertFalse($this->policy->view($user, $gig));
    }

    #[Test]
    public function user_without_role_cannot_view_gig()
    {
        $user = User::factory()->create();
        $gig = Gig::factory()->create();

        $this->assertFalse($this->policy->view($user, $gig));
    }

    #[Test]
    public function no_user_can_create_gig()
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');

        $this->assertFalse($this->policy->create($user));
    }

    #[Test]
    public function no_user_can_update_gig()
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');
        $gig = Gig::factory()->create();

        $this->assertFalse($this->policy->update($user, $gig));
    }

    #[Test]
    public function no_user_can_delete_gig()
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');
        $gig = Gig::factory()->create();

        $this->assertFalse($this->policy->delete($user, $gig));
    }

    #[Test]
    public function no_user_can_restore_gig()
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');
        $gig = Gig::factory()->create();

        $this->assertFalse($this->policy->restore($user, $gig));
    }

    #[Test]
    public function no_user_can_force_delete_gig()
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');
        $gig = Gig::factory()->create();

        $this->assertFalse($this->policy->forceDelete($user, $gig));
    }
}