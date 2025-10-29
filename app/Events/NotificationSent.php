<?php
namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    public function __construct($notification)
    {
        $this->notification = $notification;

        Log::info('NotificationSent event created', [
            'sender_id'   => $notification['sender_id'],
            'receiver_id' => $notification['receiver_id'],
            'message'     => $notification['message'] ?? null,
        ]);
    }

    // Broadcast to receiver's personal channel
    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('notification.' . $this->notification['receiver_id']);
    }

    public function broadcastWith(): array
    {
        return $this->notification;
    }

    public function broadcastAs(): string
    {
        return 'notification.sent';
    }
}
