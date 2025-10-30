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

    public $message;

    public function __construct(Chat $message)
    {
        $this->message = $message;
        Log::info('MessageSent event created', [
            'message_id' => $message->id,
            'message'  => $message->message,
            'image'  => $message->public_id?? null,
            'sender_id'  => $message->sender_id,
            'receiver_id'=> $message->receiver_id,
        ]);
    }

    // Broadcast to the receiver's private channel
    public function broadcastOn()
    {
        return new Channel('chat.' . $this->message->receiver_id);
    }

//    public function broadcastWith(): array
//    {
//        return [
//            'id' => $this->message->id,
//            'sender_id' => $this->message->sender_id,
//            'receiver_id' => $this->message->receiver_id,
//            'type' => $this->message->type,
//            'message' => $this->message->message,
//            'image_path' => $this->message->image_path,
//            'reply_to' => $this->message->reply_to,
//            'created_at' => $this->message->created_at->toDateTimeString(),
//        ];
//    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
