<?php


namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Chat $message;

    public function __construct(Chat $message)
    {
        // offer relation load kore nicchi jeno realtime e send hoy
        $message->load('offer');

        $this->message = $message;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('chat.' . $this->message->receiver_id);
    }

    public function broadcastAs(): string
    {
        Log::info("MessageSent broadcast fired", [
            'message_id' => $this->message->id,
            'sender' => $this->message->sender_id,
            'receiver' => $this->message->receiver_id,
        ]);
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        Log::info('MessageSent broadcastWith fired', [
            'message_id' => $this->message->id,
            'receiver' => $this->message->receiver_id,
        ]);
        // flutter e parse korar jonno ekta clean array pathacchi
        return [
            'id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'message' => $this->message->message,
            'image' => $this->message->image,
            'public_id' => $this->message->public_id,
            'is_read' => (bool)$this->message->is_read,
            'is_offer' => (bool)$this->message->is_offer,
            'offer' => $this->message->offer, // Offer model -> JSON
            'created_at' => optional($this->message->created_at)->toIso8601String(),
        ];
    }
}
