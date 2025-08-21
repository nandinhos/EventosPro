<?php

namespace Tests\Unit\Services;

use App\Models\Booker;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserManagementService $userManagementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userManagementService = new UserManagementService();
    }

    /** @test */
    public function it_can_create_user_without_booker()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_booker' => false,
        ];

        $user = $this->userManagementService->createUser($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNull($user->booker_id);
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function it_can_create_user_with_new_booker()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_booker' => true,
            'booker_creation_type' => 'new',
            'booker_name' => 'Test Booker',
            'default_commission_rate' => 15.0,
            'contact_info' => 'test@booker.com',
        ];

        $user = $this->userManagementService->createUser($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->booker_id);
        $this->assertDatabaseHas('bookers', [
            'name' => 'TEST BOOKER',
            'default_commission_rate' => 15.0,
            'contact_info' => 'test@booker.com',
        ]);
    }

    /** @test */
    public function it_can_create_user_with_existing_booker()
    {
        $existingBooker = Booker::factory()->create();

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_booker' => true,
            'booker_creation_type' => 'existing',
            'existing_booker_id' => $existingBooker->id,
        ];

        $user = $this->userManagementService->createUser($userData);

        $this->assertEquals($existingBooker->id, $user->booker_id);
    }

    /** @test */
    public function it_throws_exception_when_existing_booker_already_has_user()
    {
        $existingUser = User::factory()->create();
        $existingBooker = Booker::factory()->create();
        $existingUser->update(['booker_id' => $existingBooker->id]);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_booker' => true,
            'booker_creation_type' => 'existing',
            'existing_booker_id' => $existingBooker->id,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('O booker selecionado já está associado a outro usuário.');

        $this->userManagementService->createUser($userData);
    }

    /** @test */
    public function it_can_update_user_basic_data()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $userData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_booker' => false,
        ];

        $updatedUser = $this->userManagementService->updateUser($user, $userData);

        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertEquals('updated@example.com', $updatedUser->email);
    }

    /** @test */
    public function it_can_update_user_password()
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'newpassword123',
            'is_booker' => false,
        ];

        $updatedUser = $this->userManagementService->updateUser($user, $userData);

        $this->assertNotEquals($originalPassword, $updatedUser->password);
        $this->assertTrue(Hash::check('newpassword123', $updatedUser->password));
    }

    /** @test */
    public function it_can_update_existing_booker_data()
    {
        $booker = Booker::factory()->create([
            'default_commission_rate' => 10.0,
            'contact_info' => 'old@contact.com',
        ]);
        $user = User::factory()->create(['booker_id' => $booker->id]);

        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'is_booker' => true,
            'default_commission_rate' => 20.0,
            'contact_info' => 'new@contact.com',
        ];

        $this->userManagementService->updateUser($user, $userData);

        $booker->refresh();
        $this->assertEquals(20.0, $booker->default_commission_rate);
        $this->assertEquals('new@contact.com', $booker->contact_info);
    }

    /** @test */
    public function it_can_delete_user_without_booker()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $result = $this->userManagementService->deleteUser($user);

        $this->assertTrue($result);
        $this->assertSoftDeleted('users', ['id' => $userId]);
    }

    /** @test */
    public function it_can_delete_user_with_booker()
    {
        $booker = Booker::factory()->create();
        $user = User::factory()->create(['booker_id' => $booker->id]);
        $userId = $user->id;
        $bookerId = $booker->id;

        $result = $this->userManagementService->deleteUser($user);

        $this->assertTrue($result);
        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertSoftDeleted('bookers', ['id' => $bookerId]);
    }

    /** @test */
    public function it_rolls_back_transaction_on_create_user_failure()
    {
        // Simula falha forçando violação de constraint
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_booker' => true,
            'booker_creation_type' => 'existing',
            'existing_booker_id' => 99999, // ID inexistente
        ];

        $this->expectException(\Exception::class);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        $this->userManagementService->createUser($userData);
    }
}