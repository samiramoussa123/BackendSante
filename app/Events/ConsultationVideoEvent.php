<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;       

class ConsultationVideoEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $roomId;
    public string $type;   // 'offer' | 'answer' | 'ice-candidate' | 'join' | 'leave' | 'start' | 'end'
    public mixed  $data;
    public int    $senderId;

    public function __construct(string $roomId, string $type, mixed $data, int $senderId)
    {
        $this->roomId   = $roomId;
        $this->type     = $type;
        $this->data     = $data;
        $this->senderId = $senderId;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("consultation.{$this->roomId}");
    }

    public function broadcastAs(): string
    {
        return 'video.signal';
    }

    public function broadcastWith(): array
    {
        return [
            'type'      => $this->type,
            'data'      => $this->data,
            'senderId'  => $this->senderId,
            'roomId'    => $this->roomId,
        ];
    }
}