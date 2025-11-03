<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Helpers\FileHelper;
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
    //search by user typ to message
    public function userSearch(Request $request): JsonResponse
    {
        try {
            $query = trim($request->input('query'));
            $user = User::where('id', $request->header('id'))
                ->select('id', 'user_type')
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            if (empty($query)) {
                return ResponseHelper::Out('failed', 'Search keyword is required', [], 400);
            }
            // build base query based on user type
            switch ($user->user_type) {
                case 'buyer':
                    $usersQuery = User::where('user_type', 'vendor')
                        ->where('status', 'Approved');
                    break;

                case 'vendor':
                    $usersQuery = User::where('user_type', 'buyer');
                    break;

                case 'transport':
                    $usersQuery = User::where('user_type', 'driver')
                        ->where('status', 'Approved');
                    break;

                case 'driver':
                    $usersQuery = User::where('user_type', 'transport');
                    break;

                case 'admin':
                    $usersQuery = User::query();
                    break;

                default:
                    return ResponseHelper::Out('failed', 'Invalid user type', null, 400);
            }
            // search data
            $usersQuery->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%")
                    ->orWhere('phone', 'LIKE', "%{$query}%");
            });
            $users = $usersQuery
                ->select('id', 'name', 'email', 'phone', 'user_type', 'status')
                ->paginate(30);

            if ($users->isEmpty()) {
                return ResponseHelper::Out('success', 'No matching users found', [], 200);
            }
            return ResponseHelper::Out('success', 'Matching users fetched successfully', $users, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', null, 500);
        }
    }
    //Get user by type
//    public function userGetByType(Request $request): JsonResponse
//    {
//        try {
//            $user = User::where('id', $request->header('id'))->select('id', 'user_type')->first();
//            if (!$user) {
//                return ResponseHelper::Out('failed', 'User not found', null, 404);
//            }
//            $usersQuery = null;
//            switch ($user->user_type) {
//                case 'buyer':
//                    $usersQuery = User::where('user_type', 'vendor')->where('status', 'Approved');
//                    break;
//                case 'vendor':
//                    $usersQuery = User::where('user_type', 'buyer');
//                    break;
//                case 'transport':
//                    $usersQuery = User::where('user_type', 'driver')->where('status', 'Approved');
//                    break;
//                case 'driver':
//                    $usersQuery = User::where('user_type', 'transport');
//                    break;
//                case 'admin':
//                    $usersQuery = User::query();
//                    break;
//                default:
//                    return ResponseHelper::Out('failed', 'Invalid user type', null, 400);
//            }
//            $users = $usersQuery->paginate(30);
//            return ResponseHelper::Out('success', 'All user successfully fetched', $users, 200);
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', null, 500);
//        }
//    }

    //get chat list
//    public function userInbox(Request $request): JsonResponse
//    {
//        try {
//            //  Get user
//            $userId = $request->header('id');
//            $user = User::where('id', $userId)
//                ->select(['id'])
//                ->first();
//            if (!$user) {
//                return ResponseHelper::Out('failed', 'user not found', null, 404);
//            }
//            $messages = Chat::where('receiver_id', $user->id)
//                ->with('sender')
//                ->select('id', 'image','public_id','message','is_read', 'sender_id', 'receiver_id', 'created_at')
//                ->latest()
//                ->get();
//            if ($messages->isEmpty()) {
//                return ResponseHelper::Out('success', 'You have no message', null, 200);
//            }
//            return ResponseHelper::Out('success', 'message retrieved successfully', $messages, 200);
//        } catch (\Exception $e) {
//            return ResponseHelper::Out('failed', $e->getMessage(), null, 500);
//        }
//    }

    public function userInbox(Request $request): JsonResponse
    {
        try {
            // Get logged-in user ID from header
            $userId = $request->header('id');

            // Check if user exists
            $user = User::select('id')->find($userId);
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }

            // Fetch all chats where user is sender or receiver
            $messages = Chat::where(function ($query) use ($user) {
                $query->where('receiver_id', $user->id)
                    ->orWhere('sender_id', $user->id);
            })
                ->with([
                    'sender:id,name,image',
                    'receiver:id,name,image'
                ])
                ->select('id', 'sender_id', 'receiver_id', 'message', 'image', 'public_id', 'is_read', 'created_at')
                ->latest()
                ->get();

            // If no messages found
            if ($messages->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no messages', [], 200);
            }

            // Build unique chat list (one per conversation partner)
            $chatList = $messages->unique(function ($chat) use ($user) {
                // Return the partner ID (not current user)
                return $chat->sender_id == $user->id ? $chat->receiver_id : $chat->sender_id;
            })
                ->values()
                ->map(function ($chat) use ($user) {
                    // Identify the chat partner (opposite user)
                    $partner = $chat->sender_id == $user->id ? $chat->receiver : $chat->sender;

                    return [
                        'chat_id' => $chat->id,
                        'partner_id' => $partner->id,
                        'partner_name' => $partner->name,
                        'partner_image' => $partner->image ?? null,
                        'last_message' => $chat->message,
                        'last_message_time' => $chat->created_at->diffForHumans(),
                        'is_read' => $chat->is_read,
                    ];
                });

            return ResponseHelper::Out('success', 'Chat list retrieved successfully', $chatList, 200);

        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // Send message
    public function sendMessage(Request $request, $receiverId)
    {
        try {
            $request->validate([
                'message' => 'nullable|string',
                'image.*' => 'nullable|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:10240',
                'reply_to' => 'nullable|exists:chats,id',
            ]);

            $sender = User::find($request->header('id'));
            $receiver = User::find($receiverId);

            if (!$sender || !$receiver) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }

            // Handle image/file upload
            $imagePath = null;
            $publicId = null;

            if ($request->hasFile('image')) {
                $uploadedFiles = FileHelper::upload($request->file('image'), 'chat');
                $firstFile = $uploadedFiles[0] ?? null;

                $imagePath = $firstFile['url'] ?? null;
                $publicId  = $firstFile['public_id'] ?? null;
            }

            $message = Chat::create([
                'sender_id'  => $sender->id,
                'receiver_id'=> $receiver->id,
                'message'    => $request->message ?? null,
                'image' => $imagePath,
                'public_id'  => $publicId,
                'reply_to'   => $request->reply_to ?? null,
            ]);

            // Broadcast real-time
            broadcast(new MessageSent($message))->toOthers();

            return ResponseHelper::Out('success', 'Message sent successfully', $message, 200);

        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation failed', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }


    public function getMessages(Request $request, $receiverId)
    {
        $userId = $request->header('id');

        $messages = Chat::where(function ($q) use ($userId, $receiverId) {
            $q->where('sender_id', $userId)
                ->where('receiver_id', $receiverId);
        })
            ->orWhere(function ($q) use ($userId, $receiverId) {
                $q->where('sender_id', $receiverId)
                    ->where('receiver_id', $userId);
            })
            ->orderBy('created_at')
            ->get();
        if ($messages->isEmpty()) {
            return ResponseHelper::Out('success', 'You have no message', [], 200);
        }
        return ResponseHelper::Out('success', 'Messages fetched', $messages, 200);
    }
}
