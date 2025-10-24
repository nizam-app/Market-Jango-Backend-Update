<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Chat;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    //Get user by type
    public function userGetByType(Request $request): JsonResponse
    {
        try {
            $user = User::where('id', $request->header('id'))->select('id', 'user_type')->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $usersQuery = null;
            switch ($user->user_type) {
                case 'buyer':
                    $usersQuery = User::where('user_type', 'vendor')->where('status', 'Approved');
                    break;
                case 'vendor':
                    $usersQuery = User::where('user_type', 'buyer');
                    break;
                case 'transport':
                    $usersQuery = User::where('user_type', 'driver')->where('status', 'Approved');
                    break;
                case 'driver':
                    $usersQuery = User::where('user_type', 'transport');
                    break;
                case 'admin':
                    $usersQuery = User::query();
                    break;
            }
            $users = $usersQuery->paginate(10);
            return ResponseHelper::Out('success', 'All user successfully fetched', $users, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', null, 500);
        }
    }
    // Send message
    public function sendMessage(Request $request, $id)
    {
        try {
            $request->validate([
                'type' => 'required',
            ]);
            $sender = User::find($request->header('id'));
            $receiver = User::find($id);

            if (!$sender || !$receiver) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $fileUrl = null;
            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('chat_media', 'public');
                $fileUrl = asset('storage/' . $path);
            }
            $chat = Chat::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'type' => $request->type ?? null,
                'message' => $request->message ?? null,
                'image_path' => $fileUrl ?? null,
                'reply_to' => $request->reply_to ?? null,
            ]);
        broadcast(new MessageSent($chat, $id))->toOthers();
            return ResponseHelper::Out('success', 'message send successfully', $chat, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    public function getMessages(Request $request)
    {
        $messages = Chat::where(function ($q) use ($request) {
            $q->where('sender_id', $request->sender_id)
                ->where('receiver_id', $request->receiver_id);
        })->orWhere(function ($q) use ($request) {
            $q->where('sender_id', $request->receiver_id)
                ->where('receiver_id', $request->sender_id);
        })->orderBy('created_at', 'asc')->get();
        return response()->json($messages);
    }
}
