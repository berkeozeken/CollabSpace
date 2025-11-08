<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingStopped implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $teamId,
        public int $userId
    ) {}

    public function broadcastOn(): array
    {
        return [ new PrivateChannel('team.'.$this->teamId) ];
    }

    public function broadcastAs(): string
    {
        return 'TypingStopped';
    }

    public function broadcastWith(): array
    {
        return [
            'team_id' => $this->teamId,
            'user_id' => $this->userId,
        ];
    }
}
