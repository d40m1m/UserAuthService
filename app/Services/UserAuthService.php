<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\UserRepositoryInterface;
use App\Events\UserRegistered;
use App\Exceptions\RateLimitExceededException;
use App\Jobs\SendVerificationEmail;
use App\Models\User;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use OTPHP\TOTP;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Service for managing user authentication workflows with advanced features.
 *
 * @final
 */
final class UserAuthService
{
    private const CACHE_TTL = 3600;
    private const RATE_LIMIT_BASE_ATTEMPTS = 5;
    private const MFA_TOKEN_TTL = 300;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordBroker $passwordBroker
    ) {}

    /**
     * Registers a new user with email verification and caching.
     *
     * @param array{name: string, email: string, password: string} $data User registration data
     *
     * @return User The newly created user
     *
     * @throws HttpException If registration fails due to internal error
     * @throws RateLimitExceededException If rate limit is exceeded
     */
    public function register(array $data): User
    {
        $this->enforceAdaptiveRateLimiting(request()->ip(), 'register');

        try {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'verification_token' => Str::random(60),
            ]);

            Cache::store('redis')->put("user:auth:{$user->id}", $user->toArray(), self::CACHE_TTL);

            Event::dispatch(new UserRegistered($user));
            SendVerificationEmail::dispatch($user)->onQueue('emails');

            Log::info('User registered', ['user_id' => $user->id]);

            return $user;
        } catch (\Exception $e) {
            Bugsnag::notifyException($e);
            Log::error('User registration failed', ['error' => $e->getMessage()]);
            throw new HttpException(500, 'Registration failed. Please try again.');
        }
    }

    /**
     * Authenticates a user with optional MFA verification.
     *
     * @param string $email User email
     * @param string $password User password
     * @param string|null $mfaCode MFA code, if applicable
     *
     * @return array{access_token: string, token_type: string} Authentication token
     *
     * @throws HttpException If credentials are invalid or MFA fails
     * @throws RateLimitExceededException If rate limit is exceeded
     */
    public function authenticate(string $email, string $password, ?string $mfaCode = null): array
    {
        $this->enforceAdaptiveRateLimiting(request()->ip(), 'login');

        $cacheKey = "user:auth:email:{$email}";
        $user = Cache::store('redis')->remember($cacheKey, self::CACHE_TTL, fn() => $this->userRepository->findByEmail($email));

        if (!$user || !Hash::check($password, $user->password)) {
            throw new HttpException(401, 'Invalid credentials.');
        }

        if ($user->mfa_enabled) {
            $this->verifyMfa($user, $mfaCode);
        }

        return $this->issueToken($user);
    }

    /**
     * Verifies MFA code using cached TOTP.
     *
     * @param User $user The user to verify
     * @param string|null $mfaCode The MFA code provided by the user
     *
     * @throws HttpException If MFA code is missing or invalid
     */
    private function verifyMfa(User $user, ?string $mfaCode): void
    {
        if (!$mfaCode) {
            throw new HttpException(428, 'MFA code required.');
        }

        $cacheKey = "mfa:token:{$user->id}";
        $totp = Cache::store('redis')->remember(
            $cacheKey,
            self::MFA_TOKEN_TTL,
            fn() => TOTP::create($user->mfa_secret)
        );

        if (!$totp->verify($mfaCode)) {
            Bugsnag::notifyError('MFA Verification Failed', "Invalid MFA code for user {$user->id}");
            throw new HttpException(401, 'Invalid MFA code.');
        }

        Cache::store('redis')->forget($cacheKey);
    }

    /**
     * Issues an OAuth token for the authenticated user.
     *
     * @param User $user The authenticated user
     *
     * @return array{access_token: string, token_type: string} Token details
     */
    private function issueToken(User $user): array
    {
        $token = $user->createToken('auth_token')->accessToken;
        return ['access_token' => $token, 'token_type' => 'Bearer'];
    }

    /**
     * Enforces adaptive rate limiting based on IP and action.
     *
     * @param string $ip The client IP address
     * @param string $action The action being rate-limited ('login', 'register')
     *
     * @throws RateLimitExceededException If attempts exceed the dynamic threshold
     */
    private function enforceAdaptiveRateLimiting(string $ip, string $action): void
    {
        $key = "auth:{$action}:{$ip}";
        $maxAttempts = $this->calculateDynamicThreshold($ip);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw new RateLimitExceededException("Too many {$action} attempts. Retry in {$seconds} seconds.");
        }

        RateLimiter::hit($key, 60);
    }

    /**
     * Calculates a dynamic rate limit threshold based on IP attempt history.
     *
     * @param string $ip The client IP address
     *
     * @return int The calculated threshold (minimum 1)
     */
    private function calculateDynamicThreshold(string $ip): int
    {
        $attemptsHistory = Cache::store('redis')->get("rate:history:{$ip}", 0);
        $threshold = self::RATE_LIMIT_BASE_ATTEMPTS - min(floor($attemptsHistory / 10), 4);
        return max(1, $threshold);
    }
}