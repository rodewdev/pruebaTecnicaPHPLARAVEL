<?php

namespace Tests\Integration\Infrastructure\Repositories;

use App\Domain\User\ValueObjects\Balance;
use App\Domain\User\ValueObjects\Email;
use App\Infrastructure\Repositories\EloquentUserRepository;
use App\Models\User as EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentUserRepository();
    }

    public function test_can_create_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'balance' => 100.00,
        ];

        $user = $this->repository->create($userData);

        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals('john@example.com', $user->getEmail()->getValue());
        $this->assertEquals(100.00, $user->getBalance()->getValue());
        $this->assertNotNull($user->getId());

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'balance' => 100.00,
        ]);
    }

    public function test_can_find_user_by_id()
    {
        $eloquentUser = EloquentUser::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'balance' => 200.00,
        ]);

        $user = $this->repository->findById($eloquentUser->id);

        $this->assertNotNull($user);
        $this->assertEquals('Jane Doe', $user->getName());
        $this->assertEquals('jane@example.com', $user->getEmail()->getValue());
        $this->assertEquals(200.00, $user->getBalance()->getValue());
    }

    public function test_returns_null_when_user_not_found_by_id()
    {
        $user = $this->repository->findById(999);

        $this->assertNull($user);
    }

    public function test_can_find_user_by_email()
    {
        $eloquentUser = EloquentUser::factory()->create([
            'email' => 'test@example.com',
            'balance' => 150.00,
        ]);

        $email = new Email('test@example.com');
        $user = $this->repository->findByEmail($email);

        $this->assertNotNull($user);
        $this->assertEquals('test@example.com', $user->getEmail()->getValue());
        $this->assertEquals(150.00, $user->getBalance()->getValue());
    }

    public function test_returns_null_when_user_not_found_by_email()
    {
        $email = new Email('nonexistent@example.com');
        $user = $this->repository->findByEmail($email);

        $this->assertNull($user);
    }

    public function test_can_update_user()
    {
        $eloquentUser = EloquentUser::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'balance' => 100.00,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'balance' => 250.00,
        ];

        $updatedUser = $this->repository->update($eloquentUser->id, $updateData);

        $this->assertEquals('Updated Name', $updatedUser->getName());
        $this->assertEquals('original@example.com', $updatedUser->getEmail()->getValue());
        $this->assertEquals(250.00, $updatedUser->getBalance()->getValue());

        $this->assertDatabaseHas('users', [
            'id' => $eloquentUser->id,
            'name' => 'Updated Name',
            'balance' => 250.00,
        ]);
    }

    public function test_can_soft_delete_user()
    {
        $eloquentUser = EloquentUser::factory()->create();

        $result = $this->repository->delete($eloquentUser->id);

        $this->assertTrue($result);

        $this->assertSoftDeleted('users', ['id' => $eloquentUser->id]);
    }

    public function test_can_update_balance()
    {
        $eloquentUser = EloquentUser::factory()->create(['balance' => 100.00]);

        $result = $this->repository->updateBalance($eloquentUser->id, 300.00);

        $this->assertTrue($result);

        $this->assertDatabaseHas('users', [
            'id' => $eloquentUser->id,
            'balance' => 300.00,
        ]);
    }

    public function test_can_check_if_email_exists()
    {
        EloquentUser::factory()->create(['email' => 'existing@example.com']);

        $existingEmail = new Email('existing@example.com');
        $nonExistingEmail = new Email('nonexisting@example.com');

        $this->assertTrue($this->repository->existsByEmail($existingEmail));
        $this->assertFalse($this->repository->existsByEmail($nonExistingEmail));
    }

    public function test_can_get_paginated_users()
    {
        EloquentUser::factory()->count(25)->create();

        $result = $this->repository->getAllPaginated(10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertArrayHasKey('last_page', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('total', $result);

        $this->assertCount(10, $result['data']);
        $this->assertEquals(25, $result['total']);
        $this->assertEquals(3, $result['last_page']);
    }

    public function test_caches_user_after_creation()
    {
        Cache::flush();

        $userData = [
            'name' => 'Cached User',
            'email' => 'cached@example.com',
            'balance' => 100.00,
        ];

        $user = $this->repository->create($userData);

        $cachedUser = Cache::get('user:' . $user->getId());
        $this->assertNotNull($cachedUser);
        $this->assertEquals('Cached User', $cachedUser->getName());

        $cachedByEmail = Cache::get('user:email:cached@example.com');
        $this->assertNotNull($cachedByEmail);
        $this->assertEquals('Cached User', $cachedByEmail->getName());
    }

    public function test_clears_cache_after_update()
    {
        $eloquentUser = EloquentUser::factory()->create();

        $this->repository->findById($eloquentUser->id);
        $this->assertNotNull(Cache::get('user:' . $eloquentUser->id));

        $this->repository->update($eloquentUser->id, ['name' => 'Updated Name']);

        $cachedUser = Cache::get('user:' . $eloquentUser->id);
        $this->assertNotNull($cachedUser);
        $this->assertEquals('Updated Name', $cachedUser->getName());
    }

    public function test_clears_cache_after_delete()
    {
        $eloquentUser = EloquentUser::factory()->create();

        $this->repository->findById($eloquentUser->id);
        $this->assertNotNull(Cache::get('user:' . $eloquentUser->id));

        $this->repository->delete($eloquentUser->id);

        $this->assertNull(Cache::get('user:' . $eloquentUser->id));
    }
}
