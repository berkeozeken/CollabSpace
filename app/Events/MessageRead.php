<?php

namespace App\Events;

use App\Models\MessageRead as MR;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MR $read, public int $teamId) {}

    public function broadcastOn(): array
    {
        return [ new PrivateChannel('team.' . $this->teamId) ];
    }

    public function broadcastAs(): string
    {
        return 'MessageRead';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->read->message_id,
            'user_id'    => $this->read->user_id,
            'read_at'    => $this->read->read_at?->toJSON(),
        ];
    }
}
