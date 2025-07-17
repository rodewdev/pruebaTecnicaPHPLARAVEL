<?php

namespace App\Infrastructure\Repositories;

use App\Domain\User\Entities\User as UserEntity;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\ValueObjects\Balance;
use App\Domain\User\ValueObjects\Email;
use App\Models\User as EloquentUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EloquentUserRepository implements UserRepositoryInterface
{
    private const CACHE_TTL = 3600;
    private const CACHE_PREFIX = 'user:';

    public function create(array $data): UserEntity
    {
        return DB::transaction(function () use ($data) {
            $eloquentUser = EloquentUser::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'] ?? bcrypt('default'),
                'balance' => $data['balance'] ?? 0.00,
            ]);

            $userEntity = $this->mapToEntity($eloquentUser);
            
            $this->cacheUser($userEntity);
            
            return $userEntity;
        });
    }

    public function findById(int $id): ?UserEntity
    {
        $cacheKey = self::CACHE_PREFIX . $id;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            $eloquentUser = EloquentUser::find($id);
            
            return $eloquentUser ? $this->mapToEntity($eloquentUser) : null;
        });
    }

    public function findByEmail(Email $email): ?UserEntity
    {
        $cacheKey = self::CACHE_PREFIX . 'email:' . $email->getValue();
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($email) {
            $eloquentUser = EloquentUser::where('email', $email->getValue())->first();
            
            return $eloquentUser ? $this->mapToEntity($eloquentUser) : null;
        });
    }

    public function update(int $id, array $data): UserEntity
    {
        return DB::transaction(function () use ($id, $data) {
            $eloquentUser = EloquentUser::findOrFail($id);
            
            $eloquentUser->update(array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'balance' => $data['balance'] ?? null,
            ]));

            $userEntity = $this->mapToEntity($eloquentUser->fresh());
            
            $this->cacheUser($userEntity);
            $this->clearEmailCache($eloquentUser->email);
            
            return $userEntity;
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $eloquentUser = EloquentUser::findOrFail($id);
            $email = $eloquentUser->email;
            
            $result = $eloquentUser->delete();
            
            if ($result) {
                $this->clearUserCache($id);
                $this->clearEmailCache($email);
            }
            
            return $result;
        });
    }

    public function updateBalance(int $userId, float $amount): bool
    {
        return DB::transaction(function () use ($userId, $amount) {
            $result = EloquentUser::where('id', $userId)
                ->update(['balance' => $amount, 'updated_at' => now()]);
            
            if ($result) {
                $this->clearUserCache($userId);
            }
            
            return $result > 0;
        });
    }

    public function existsByEmail(Email $email): bool
    {
        return EloquentUser::where('email', $email->getValue())->exists();
    }

    public function getAllPaginated(int $perPage = 15): array
    {
        $paginator = EloquentUser::orderBy('created_at', 'desc')->paginate($perPage);
        
        return [
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private function mapToEntity(EloquentUser $eloquentUser): UserEntity
    {
        return new UserEntity(
            name: $eloquentUser->name,
            email: new Email($eloquentUser->email),
            balance: new Balance($eloquentUser->balance),
            id: $eloquentUser->id,
            emailVerifiedAt: $eloquentUser->email_verified_at ? Carbon::parse($eloquentUser->email_verified_at) : null,
            createdAt: $eloquentUser->created_at ? Carbon::parse($eloquentUser->created_at) : null,
            updatedAt: $eloquentUser->updated_at ? Carbon::parse($eloquentUser->updated_at) : null,
            deletedAt: $eloquentUser->deleted_at ? Carbon::parse($eloquentUser->deleted_at) : null
        );
    }

    private function cacheUser(UserEntity $user): void
    {
        if ($user->getId()) {
            $cacheKey = self::CACHE_PREFIX . $user->getId();
            Cache::put($cacheKey, $user, self::CACHE_TTL);
            
            $emailCacheKey = self::CACHE_PREFIX . 'email:' . $user->getEmail()->getValue();
            Cache::put($emailCacheKey, $user, self::CACHE_TTL);
        }
    }

    private function clearUserCache(int $userId): void
    {
        $cacheKey = self::CACHE_PREFIX . $userId;
        Cache::forget($cacheKey);
    }

    private function clearEmailCache(string $email): void
    {
        $cacheKey = self::CACHE_PREFIX . 'email:' . $email;
        Cache::forget($cacheKey);
    }
}