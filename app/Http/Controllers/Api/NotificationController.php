<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationSent;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    //get notification
    public function myNotifications(Request $request): JsonResponse
    {
        try {
            //  Get user
            $userId = $request->header('id');
            $user = User::where('id', $userId)
                ->select(['id'])
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'user not found', null, 404);
            }
            $notifications = Notification::where('receiver_id', $user->id)
                ->with('sender:id,name,email')
                ->select('id', 'name','message','is_read', 'sender_id', 'receiver_id', 'created_at')
                ->latest()
                ->get();
            if ($notifications->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no notification', null, 200);
            }
            return ResponseHelper::Out('success', 'Notifications retrieved successfully', $notifications, 200);
        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', $e->getMessage(), null, 500);
        }
    }
    //read notification
    public function markAsRead(Request $request, $id): JsonResponse
    {
        try {
            $userId = $request->header('id');
            $notification = Notification::where('id', $id)
                ->where('receiver_id', $userId)
                ->first();
            if (!$notification) {
                return ResponseHelper::Out('failed', 'Notification not found', null, 404);
            }
            // update notification
            $notification->update(['is_read' => true]);
            return ResponseHelper::Out('success', 'Notification marked as read', $notification, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

}
