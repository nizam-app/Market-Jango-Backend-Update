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
                default:
                    return ResponseHelper::Out('failed', 'Invalid user type', null, 400);
            }
            $users = $usersQuery->paginate(30);
            return ResponseHelper::Out('success', 'All user successfully fetched', $users, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', null, 500);
        }
    }
    // Send message
    public function sendMessage(Request $request, $receiverId)
    {
        try {
            $request->validate([
                'message' => 'nullable|string',
                'image' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:10240',
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
