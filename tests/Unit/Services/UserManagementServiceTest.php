<?php

namespace Tests\Unit\Services;

use App\Models\Booker;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserManagementService $userManagementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userManagementService = new UserManagementService;
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_can_delete_user_without_booker()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $result = $this->userManagementService->deleteUser($user);

        $this->assertTrue($result);
        $this->assertSoftDeleted('users', ['id' => $userId]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_can_update_user_to_become_booker_with_new_booker()
    {
        $user = User::factory()->create([
            'booker_id' => null,
        ]);

        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'is_booker' => true,
            'booker_creation_type' => 'new',
            'booker_name' => 'New Booker Name',
            'default_commission_rate' => 12.5,
            'contact_info' => 'newbooker@example.com',
        ];

        $updatedUser = $this->userManagementService->updateUser($user, $userData);

        $this->assertNotNull($updatedUser->booker_id);
        $this->assertDatabaseHas('bookers', [
            'name' => 'NEW BOOKER NAME',
            'default_commission_rate' => 12.5,
            'contact_info' => 'newbooker@example.com',
        ]);
    }

    #[Test]
    public function it_can_update_user_to_become_booker_with_existing_booker()
    {
        $user = User::factory()->create([
            'booker_id' => null,
        ]);
        $existingBooker = Booker::factory()->create();

        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'is_booker' => true,
            'booker_creation_type' => 'existing',
            'existing_booker_id' => $existingBooker->id,
        ];

        $updatedUser = $this->userManagementService->updateUser($user, $userData);

        $this->assertEquals($existingBooker->id, $updatedUser->booker_id);
    }

    #[Test]
    public function it_can_remove_booker_association_from_user()
    {
        $booker = Booker::factory()->create();
        $user = User::factory()->create(['booker_id' => $booker->id]);

        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'is_booker' => false,
        ];

        $updatedUser = $this->userManagementService->updateUser($user, $userData);

        $this->assertNull($updatedUser->booker_id);
        // Booker should still exist (not deleted)
        $this->assertDatabaseHas('bookers', ['id' => $booker->id]);
    }

    #[Test]
    public function it_throws_exception_when_updating_to_existing_booker_already_associated()
    {
        $existingUser = User::factory()->create();
        $existingBooker = Booker::factory()->create();
        $existingUser->update(['booker_id' => $existingBooker->id]);

        $user = User::factory()->create(['booker_id' => null]);

        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'is_booker' => true,
            'booker_creation_type' => 'existing',
            'existing_booker_id' => $existingBooker->id,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('O booker selecionado já está associado a outro usuário.');

        $this->userManagementService->updateUser($user, $userData);
    }

    #[Test]
    public function it_allows_user_to_keep_same_booker_when_updating()
    {
        $booker = Booker::factory()->create();
        $user = User::factory()->create(['booker_id' => $booker->id]);

        $userData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'is_booker' => true,
            'booker_creation_type' => 'existing',
            'existing_booker_id' => $booker->id,
        ];

        $updatedUser = $this->userManagementService->updateUser($user, $userData);

        $this->assertEquals($booker->id, $updatedUser->booker_id);
        $this->assertEquals('Updated Name', $updatedUser->name);
    }

    #[Test]
    public function it_does_not_update_password_when_not_provided()
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $userData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'is_booker' => false,
        ];

        $updatedUser = $this->userManagementService->updateUser($user, $userData);

        $this->assertEquals($originalPassword, $updatedUser->password);
    }

    #[Test]
    public function it_does_not_update_password_when_empty_string_provided()
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $userData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'password' => '',
            'is_booker' => false,
        ];

        $updatedUser = $this->userManagementService->updateUser($user, $userData);

        $this->assertEquals($originalPassword, $updatedUser->password);
    }

    #[Test]
    public function it_creates_booker_name_in_uppercase()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_booker' => true,
            'booker_creation_type' => 'new',
            'booker_name' => 'lowercase booker name',
            'default_commission_rate' => 15.0,
        ];

        $user = $this->userManagementService->createUser($userData);

        $this->assertDatabaseHas('bookers', [
            'name' => 'LOWERCASE BOOKER NAME',
        ]);
    }

    #[Test]
    public function it_handles_booker_creation_without_contact_info()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_booker' => true,
            'booker_creation_type' => 'new',
            'booker_name' => 'Test Booker',
            'default_commission_rate' => 15.0,
            // contact_info not provided
        ];

        $user = $this->userManagementService->createUser($userData);

        $this->assertDatabaseHas('bookers', [
            'name' => 'TEST BOOKER',
            'contact_info' => null,
        ]);
    }

    #[Test]
    public function it_rolls_back_transaction_on_update_user_failure()
    {
        $user = User::factory()->create();

        // Mock DB to simulate transaction failure
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        // Force an exception by trying to update with invalid booker ID
        $userData = [
            'name' => $user->name,
            'email' => $user->email,
            'is_booker' => true,
            'booker_creation_type' => 'existing',
            'existing_booker_id' => 99999, // Non-existent ID
        ];

        $this->expectException(\Exception::class);
        $this->userManagementService->updateUser($user, $userData);
    }

    #[Test]
    public function it_rolls_back_transaction_on_delete_user_failure()
    {
        $user = User::factory()->create();

        // Mock DB to simulate transaction failure
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();

        // Mock the user delete to throw an exception
        $user = $this->createMock(User::class);
        $user->method('delete')->willThrowException(new \Exception('Delete failed'));

        $this->expectException(\Exception::class);
        $this->userManagementService->deleteUser($user);
    }
}
