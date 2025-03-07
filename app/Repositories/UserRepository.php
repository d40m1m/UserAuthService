<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Repository for user-related database operations.
 *
 * This class encapsulates database interactions for the User model, adhering to
 * the repository pattern for separation of concerns. It provides methods for
 * creating, retrieving, and updating users, with added error handling and logging.
 *
 * - Centralizes user-related persistence logic, making it reusable and testable.
 *
 * @final
 */
final class UserRepository implements UserRepositoryInterface
{
    /**
     * Creates a new user in the database.
     *
     * @param array{name: string, email: string, password: string, verification_token: string} $data User data
     *
     * @return User The created user
     *
     * @throws RuntimeException If the user creation fails
     */
    public function create(array $data): User
    {
        try {
            $user = User::create($data);
            Log::info('User created in database', ['user_id' => $user->id, 'email' => $data['email']]);
            return $user;
        } catch (\Exception $e) {
            Log::error('Failed to create user', ['error' => $e->getMessage(), 'data' => $data]);
            throw new RuntimeException('Unable to create user: ' . $e->getMessage());
        }
    }

    /**
     * Retrieves a user by their email address.
     *
     * @param string $email The email to search for
     *
     * @return User|null The user if found, null otherwise
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Retrieves a user by their ID.
     *
     * @param int $id The user ID to search for
     * @return User|null The user if found, null otherwise
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Updates an existing user with the provided data.
     *
     * @param User $user The user to update
     * @param array<string, mixed> $data The data to update (e.g., ['name' => 'New Name'])
     *
     * @return User The updated user
     *
     * @throws RuntimeException If the update fails
     */
    public function update(User $user, array $data): User
    {
        try {
            $user->update($data);
            Log::info('User updated in database', ['user_id' => $user->id, 'updated_fields' => array_keys($data)]);
            return $user->refresh(); // Ensure we return the latest state
        } catch (\Exception $e) {
            Log::error('Failed to update user', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw new RuntimeException('Unable to update user: ' . $e->getMessage());
        }
    }

    /**
     * Checks if an email address already exists in the database.
     *
     * @param string $email The email to check
     *
     * @return bool True if the email exists, false otherwise
     */
    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }
}