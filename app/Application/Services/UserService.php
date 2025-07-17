<?php

namespace App\Application\Services;

use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\DuplicateEmailException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\ValueObjects\Balance;
use App\Domain\User\ValueObjects\Email;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function createUser(array $data): User
    {
        try {
            $email = new Email($data['email']);
            $balance = new Balance($data['balance'] ?? 0.00);

            if ($this->userRepository->existsByEmail($email)) {
                throw new DuplicateEmailException();
            }

            $userData = [
                'name' => $data['name'],
                'email' => $email->getValue(),
                'password' => isset($data['password']) ? Hash::make($data['password']) : Hash::make('default'),
                'balance' => $balance->getValue(),
            ];

            $user = $this->userRepository->create($userData);

            Log::info('Usuario creado exitosamente', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()->getValue(),
                'initial_balance' => $user->getBalance()->getValue(),
            ]);

            return $user;

        } catch (DuplicateEmailException $e) {
            Log::warning('Intento de crear usuario con email duplicado', [
                'email' => $data['email'] ?? 'unknown',
            ]);
            throw $e;
        } catch (\InvalidArgumentException $e) {
            Log::error('Datos inválidos proporcionados para la creación de usuario', [
                'error' => $e->getMessage(),
                'data' => array_diff_key($data, ['password' => '']),
            ]);
            throw $e;
        }
    }

    public function getUserById(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            Log::warning('Usuario no encontrado', ['user_id' => $id]);
            throw new UserNotFoundException();
        }

        if ($user->isDeleted()) {
            Log::warning('Intento de acceder a un usuario eliminado', ['user_id' => $id]);
            throw new UserNotFoundException();
        }

        return $user;
    }

    public function getUserByEmail(string $email): User
    {
        try {
            $emailVO = new Email($email);
            $user = $this->userRepository->findByEmail($emailVO);

            if (!$user) {
                Log::warning('Usuario no encontrado por email', ['email' => $email]);
                throw new UserNotFoundException();
            }

            if ($user->isDeleted()) {
                Log::warning('Intento de acceder a un usuario eliminado por email', ['email' => $email]);
                throw new UserNotFoundException();
            }

            return $user;

        } catch (\InvalidArgumentException $e) {
            Log::error('Formato de email proporcionado inválido', ['email' => $email]);
            throw $e;
        }
    }

    public function updateUser(int $id, array $data): User
    {
        try {
            $user = $this->getUserById($id);

            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                $newEmail = new Email($data['email']);

                if (!$user->getEmail()->equals($newEmail)) {
                    if ($this->userRepository->existsByEmail($newEmail)) {
                        throw new DuplicateEmailException();
                    }
                    $updateData['email'] = $newEmail->getValue();
                }
            }

            if (isset($data['balance'])) {
                $newBalance = new Balance($data['balance']);
                $updateData['balance'] = $newBalance->getValue();
            }

            if (empty($updateData)) {
                return $user;
            }

            $updatedUser = $this->userRepository->update($id, $updateData);

            Log::info('Usuario actualizado exitosamente', [
                'user_id' => $id,
                'updated_fields' => array_keys($updateData),
            ]);

            return $updatedUser;

        } catch (DuplicateEmailException $e) {
            Log::warning('Attempt to update user with duplicate email', [
                'user_id' => $id,
                'email' => $data['email'] ?? 'unknown',
            ]);
            throw $e;
        } catch (\InvalidArgumentException $e) {
            Log::error('Invalid data provided for user update', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function deleteUser(int $id): bool
    {
        try {
            $user = $this->getUserById($id);

            $result = $this->userRepository->delete($id);

            if ($result) {
                Log::info('User soft deleted successfully', [
                    'user_id' => $id,
                    'email' => $user->getEmail()->getValue(),
                ]);
            }

            return $result;

        } catch (UserNotFoundException $e) {
            Log::warning('Attempt to delete non-existent user', ['user_id' => $id]);
            throw $e;
        }
    }

    public function updateUserBalance(int $userId, float $newBalance): bool
    {
        try {
            $user = $this->getUserById($userId);
            $balance = new Balance($newBalance);

            $result = $this->userRepository->updateBalance($userId, $balance->getValue());

            if ($result) {
                Log::info('User balance updated', [
                    'user_id' => $userId,
                    'old_balance' => $user->getBalance()->getValue(),
                    'new_balance' => $newBalance,
                ]);
            }

            return $result;

        } catch (\InvalidArgumentException $e) {
            Log::error('Invalid balance provided for user', [
                'user_id' => $userId,
                'balance' => $newBalance,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getAllUsers(int $perPage = 15): array
    {
        return $this->userRepository->getAllPaginated($perPage);
    }

    public function verifyUserCredentials(string $email, string $password): ?User
    {
        try {
            $user = $this->getUserByEmail($email);

            return $user;

        } catch (UserNotFoundException $e) {
            Log::warning('Failed login attempt', ['email' => $email]);
            return null;
        }
    }
}
