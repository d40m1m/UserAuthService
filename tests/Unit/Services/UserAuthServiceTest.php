<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\UserRepositoryInterface;
use App\Events\UserRegistered;
use App\Jobs\SendVerificationEmail;
use App\Services\UserAuthService;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for UserAuthService.
 */
class UserAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserAuthService $service;
    private UserRepositoryInterface $repository;

    /**
     * Sets up the test environment before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(UserRepositoryInterface::class);
        $this->service = new UserAuthService($this->repository, app('auth.password'));
    }

    /**
     * Tests that register creates a user and triggers events/queues.
     *
     * @return void
     */
    public function test_register_creates_user_and_caches(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@doe.com', 'password' => 'password123'];
        $user = new \App\Models\User(['id' => 1, 'email' => $data['email']]);
        $this->repository->shouldReceive('create')->once()->andReturn($user);

        Event::fake();
        Queue::fake();
        Cache::shouldReceive('store->put')->once();

        $result = $this->service->register($data);

        $this->assertEquals($user, $result);
        Event::assertDispatched(UserRegistered::class);
        Queue::assertPushed(SendVerificationEmail::class);
    }

    /**
     * Tests that authenticate fails when rate limit is exceeded.
     *
     * @return void
     */
    public function test_authenticate_with_rate_limit_exceeded(): void
    {
        RateLimiter::shouldReceive('tooManyAttempts')->andReturn(true);
        RateLimiter::shouldReceive('availableIn')->andReturn(60);

        $this->expectException(RateLimitExceededException::class);
        $this->service->authenticate('john@example.com', 'password123');
    }

    /**
     * Cleans up after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}