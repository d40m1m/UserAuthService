<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Exception thrown when the rate limit is exceeded.
 *
 * This exception extends HttpException with a 429 status code (Too Many Requests)
 * and provides additional details like retry time and context for better error
 * handling and debugging.
 *
 * @final
 */
final class RateLimitExceededException extends HttpException
{
    /**
     * The number of seconds after which the client can retry the request.
     *
     * @var int
     */
    private int $retryAfter;

    /**
     * Optional context data for debugging or logging (e.g., IP, action).
     *
     * @var array<string, mixed>
     */
    private array $context;

    /**
     * Constructs a new RateLimitExceededException instance.
     *
     * @param string $message The error message detailing why the limit was exceeded
     * @param int $retryAfter The number of seconds until the client can retry (default: 60)
     *
     * @param array<string, mixed> $context Additional context data (default: empty array)
     */
    public function __construct(string $message, int $retryAfter = 60, array $context = [])
    {
        parent::__construct(429, $message);
        $this->retryAfter = $retryAfter;
        $this->context = $context;

        // Set the Retry-After header for HTTP compliance
        $this->headers['Retry-After'] = (string) $retryAfter;
    }

    /**
     * Gets the number of seconds after which the client can retry the request.
     *
     * @return int The retry delay in seconds
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Gets the context data associated with the exception.
     *
     * @return array<string, mixed> The context array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Gets a specific context value by key, with an optional default.
     *
     * @param string $key The context key to retrieve
     * @param mixed $default The default value if the key is not found
     *
     * @return mixed The context value or default
     */
    public function getContextValue(string $key, $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }
}