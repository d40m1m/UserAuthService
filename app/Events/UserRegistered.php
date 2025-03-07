<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Event triggered when a new user registers.
 *
 * This event carries information about the registered user, including a timestamp
 * and optional metadata, and supports broadcasting for real-time updates.
 *
 * - Metadata allows tracking registration sources or campaigns without altering the core event structure.
 * - Broadcasting enables integration wit Vue.js via Laravel Echo for live updates.
 * - Encapsulation - getters provide a clean API for accessing event data adhering to OOP principles.
 */
class UserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The newly registered user.
     *
     * @var User
     */
    public User $user;

    /**
     * The timestamp of when the user registered.
     *
     * @var Carbon
     */
    private Carbon $registeredAt;

    /**
     * Optional metadata about the registration (source, campaign).
     *
     * @var array<string, mixed>
     */
    private array $metadata;

    /**
     * Creates a new UserRegistered event instance.
     *
     * @param User $user The newly registered user
     * @param array<string, mixed> $metadata Optional metadata about the registration
     */
    public function __construct(User $user, array $metadata = [])
    {
        $this->user = $user;
        $this->registeredAt = Carbon::now();
        $this->metadata = $metadata;
    }

    /**
     * Gets the timestamp of when the user registered.
     *
     * @return Carbon The registration timestamp
     */
    public function getRegisteredAt(): Carbon
    {
        return $this->registeredAt;
    }

    /**
     * Gets the metadata associated with the registration.
     *
     * @return array<string, mixed> The metadata array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Gets a specific metadata value by key, with an optional default.
     *
     * @param string $key The metadata key to retrieve
     * @param mixed $default The default value if the key is not found
     *
     * @return mixed The metadata value or default
     */
    public function getMetadataValue(string $key, $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel|PrivateChannel|PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id), // For user-specific notifications
            new Channel('registrations'),                  // For general registration updates
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string The name used for broadcasting
     */
    public function broadcastAs(): string
    {
        return 'user.registered';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed> The data payload for broadcasting
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'registered_at' => $this->registeredAt->toIso8601String(),
            'metadata' => $this->metadata,
        ];
    }
}