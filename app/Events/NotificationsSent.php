<?php
namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class NotificationsSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $receivers;
    public function __construct($notification, $receivers)
    {
        $this->notification = $notification;
        $this->receivers = $receivers;
        Log::info('NotificationSent event fired', [
            'notification_id' => $notification->id,
            'message' => $notification->message,
            'sender_id' => $notification->sender_id,
            'receivers' => $receivers,
        ]);
    }

    public function broadcastOn(): array
    {
        // multiple private channels return
        return collect($this->receivers)
            ->map(fn($id) => new PrivateChannel('notification.' . $id))
            ->toArray();
    }

    public function broadcastAs(): string
    {
        Log::info("MessageSent broadcast fired", [
            'notification_id' => $this->notification->id,
            'sender' => $this->notification->sender_id
        ]);
        return 'notification.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->notification->id,
            'message'     => $this->notification->message,
            'sender_id'   => $this->notification->sender_id,
            'name'        => $this->notification->name,
        ];
    }
}
