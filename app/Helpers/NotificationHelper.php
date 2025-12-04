<?php

namespace App\Helpers;

use App\Events\NotificationSent;
use App\Events\NotificationsSent;
use App\Models\Notification;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class NotificationHelper
{
    public static function sendNotifications($senderId, array $receiverIds, $message=null, $name = null)
    {
        try {
            // create only ONE notification
            $notification = Notification::create([
                'sender_id' => $senderId,
                'receiver_id' => null, // NULL because it’s for many
                'message' => $message,
                'name' => $name
            ]);

            // fire event ONCE but broadcast to ALL receiver channels
            broadcast(new NotificationsSent($notification, $receiverIds));

            return ResponseHelper::Out('success', 'Notification sent to multiple users', $notification,200);
        }
        catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Error', $e->getMessage(), 500);
        }
    }


    public static function sendNotification($senderId, $receiverId=null, $message=null, $name=null):JsonResponse
    {
        try {

            $notification = Notification::create([
                'sender_id' => $senderId,
                'receiver_id'=> $receiverId,
                'message' => $message,
                'name'=> $name
            ]);
            // Broadcast real-time
            broadcast(new NotificationSent($notification))->toOthers();
            return ResponseHelper::Out('success', 'Notification sent successfully', $notification, 201);

        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation failed', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

}
