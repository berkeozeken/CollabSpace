<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionToggled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $teamId,
        public int    $messageId,
        public int    $userId,
        public string $emoji,
        public string $direction,   // "add" | "remove"
        public int    $count,
        public array  $users,       // plain names (NO "You")
        public array  $userIds      // parallel to $users
    ) {}

    public function broadcastOn(): array
    {
        return [ new PrivateChannel('team.' . $this->teamId) ];
    }

    public function broadcastAs(): string
    {
        return 'ReactionToggled';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'user_id'    => $this->userId,
            'emoji'      => $this->emoji,
            'direction'  => $this->direction,
            'count'      => $this->count,
            'users'      => $this->users,    // plain names
            'user_ids'   => $this->userIds,  // ids aligned with users
        ];
    }
}
