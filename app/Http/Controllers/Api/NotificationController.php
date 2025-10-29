<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationSent;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function sendNotification(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $notification = Notification::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ]);

        event(new NotificationSent($notification));

        return response()->json(['status' => 'Notification sent!', 'notification' => $notification]);
    }
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
}
