<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingStarted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $teamId,
        public int $userId,
        public string $userName
    ) {}

    public function broadcastOn(): array
    {
        return [ new PrivateChannel('team.'.$this->teamId) ];
    }

    public function broadcastAs(): string
    {
        return 'TypingStarted';
    }

    public function broadcastWith(): array
    {
        return [
            'team_id'   => $this->teamId,
            'user_id'   => $this->userId,
            'user_name' => $this->userName,
        ];
    }
}
