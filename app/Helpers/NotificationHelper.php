<?php

namespace App\Helpers;

use App\Events\NotificationSent;
use App\Models\Notification;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class NotificationHelper
{
    public static function sendNotification($senderId, $receiverId=null, $message=null):JsonResponse
    {
        try {

            $notification = Notification::create([
                'sender_id'  => $senderId,
                'receiver_id'=> $receiverId,
                'message'    => $message?? null,
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
