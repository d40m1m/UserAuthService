<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * Contract for user repository operations.
 */
interface UserRepositoryInterface
{
    /**
     * Creates a new user with the provided data.
     *
     * @param array{name: string, email: string, password: string, verification_token: string} $data User data
     * @return User The created user
     */
    public function create(array $data): User;

    /**
     * Finds a user by their email address.
     *
     * @param string $email The email to search for
     * @return User|null The user if found, null otherwise
     */
    public function findByEmail(string $email): ?User;
}