<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Contracts\NotificationSender;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service for sending email notifications to users.
 *
 * This class implements the NotificationSender interface to handle email delivery
 * using Laravel's Mail facade. It supports CC/BCC recipients, logging for traceability,
 * and a preview method for debugging email content.
 *
 */
class EmailNotificationSender implements NotificationSender
{
    /**
     * Sends an email notification to the specified user.
     *
     * @param User $user The user to receive the email
     * @param mixed $message The email content, expected to be a Mailable instance
     * @param array<string> $cc Optional carbon copy recipients (email addresses)
     * @param array<string> $bcc Optional blind carbon copy recipients (email addresses)
     *
     * @return void
     *
     * @throws \InvalidArgumentException If the message is not an instance of Mailable
     */
    public function send(User $user, mixed $message, array $cc = [], array $bcc = []): void
    {
        if (!$message instanceof Mailable) {
            throw new \InvalidArgumentException('Message must be an instance of Mailable.');
        }

        try {
            $mail = Mail::to($user->email);

            if (!empty($cc)) {
                $mail->cc($cc);
            }

            if (!empty($bcc)) {
                $mail->bcc($bcc);
            }

            $mail->send($message);

            Log::info('Email notification sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'cc' => $cc,
                'bcc' => $bcc,
                'subject' => $message->subject ?? 'No subject',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to allow higher-level handling
        }
    }

    /**
     * Previews the email content without sending it.
     *
     * This method is useful for testing or debugging email templates by rendering
     * the Mailable content to a string.
     *
     * @param User $user The user for whom the email is intended
     * @param Mailable $message The email content to preview
     *
     * @return string The rendered email content
     *
     * @throws \InvalidArgumentException If the message is not an instance of Mailable
     */
    public function preview(User $user, Mailable $message): string
    {
        if (!$message instanceof Mailable) {
            throw new \InvalidArgumentException('Message must be an instance of Mailable.');
        }

        // Set the "to" address for rendering purposes
        $message->to($user->email);

        // Render the email content to a string
        return $message->render();
    }
}