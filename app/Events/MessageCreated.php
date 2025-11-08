<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        $this->message->loadMissing('user:id,name');

        // 🚫 Gönderene yayınlama (X-Socket-Id ile eşleşecek)
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): array
    {
        return [ new PrivateChannel('team.' . $this->message->team_id) ];
    }

    public function broadcastAs(): string
    {
        return 'MessageCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'team_id'    => $this->message->team_id,
            'user'       => [
                'id'   => $this->message->user?->id,
                'name' => $this->message->user?->name,
            ],
            'body'       => $this->message->body,
            'created_at' => $this->message->created_at?->toJSON(),
            'updated_at' => $this->message->updated_at?->toJSON(),
        ];
    }
}
