<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\Notification\EmailNotificationSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to send a verification email asynchronously.
 *
 * This job handles sending a verification email to a newly registered user via
 * a queue, ensuring non-blocking execution. It includes priority levels and
 * retry tracking for enhanced queue management.
 *
 * - Offloads email sending to a background process, improving application responsiveness.
 * - Supports priority settings and retry tracking for robust queue handling.
 *
 * @final
 */
final class SendVerificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The user to send the verification email to.
     *
     * @var User
     */
    private readonly User $user;

    /**
     * The priority level of the job (1 = high, 2 = medium, 3 = low).
     *
     * @var int
     */
    private int $priority;

    /**
     * The number of attempts made to process this job.
     *
     * @var int
     */
    private int $attemptCount = 0;

    /**
     * The maximum number of attempts allowed before failing permanently.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to delay the job if it fails (linear backoff).
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Creates a new SendVerificationEmail job instance.
     *
     * @param User $user The user to send the email to
     * @param int $priority The priority level (1 = high, 2 = medium, 3 = low, default: 2)
     */
    public function __construct(User $user, int $priority = 2)
    {
        $this->user = $user;
        $this->priority = max(1, min(3, $priority)); // Ensure priority is between 1 and 3
        $this->onQueue('emails'); // Default queue
    }

    /**
     * Executes the job to send the verification email.
     *
     * @param EmailNotificationSender $emailSender The email sender service
     *
     * @return void
     */
    public function handle(EmailNotificationSender $emailSender): void
    {
        $this->attemptCount++;

        try {
            $emailSender->send($this->user, new \App\Mail\VerificationEmail($this->user));
            Log::info('Verification email sent', ['user_id' => $this->user->id]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $this->user->id,
                'attempt' => $this->attemptCount,
                'error' => $e->getMessage(),
            ]);
            $this->release($this->backoff * $this->attemptCount); // Linear backoff
        }
    }

    /**
     * Gets the priority level of the job.
     *
     * @return int The priority level (1 = high, 2 = medium, 3 = low)
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Gets the number of attempts made to process this job.
     *
     * @return int The attempt count
     */
    public function getAttemptCount(): int
    {
        return $this->attemptCount;
    }

    /**
     * Determines the queue name based on priority.
     *
     * @return string The queue name
     */
    public function queue(): string
    {
        return match ($this->priority) {
            1 => 'emails_high',
            2 => 'emails',
            3 => 'emails_low',
            default => 'emails', // Fallback
        };
    }

    /**
     * Handles the job failure after all retries are exhausted.
     *
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Verification email job failed permanently', [
            'user_id' => $this->user->id,
            'attempts' => $this->attemptCount,
            'error' => $exception->getMessage(),
        ]);
    }
}