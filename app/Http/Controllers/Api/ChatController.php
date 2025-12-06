<?php

namespace App\Http\Controllers\Api;
use App\Models\Offer;
use App\Events\MessageSent;
use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;


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

        $messages = Chat::with('offer')   // <--- ei line add
        ->where(function ($q) use ($userId, $receiverId) {
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

    public function createOffer(Request $request, $receiverId): JsonResponse
    {
        try {
            $sender = User::find($request->header('id'));
            $receiver = User::find($receiverId);
            if (!$sender || !$receiver) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }

            // Validation only for checking (no data extraction)
            $request->validate([
                'product_id'      => 'required|integer',
                'product_name'    => 'nullable|string|max:255',
                'quantity'        => 'required|integer|min:1',
                'sale_price'      => 'required|numeric|min:0',
                'delivery_charge' => 'required|numeric|min:0',
                'note'            => 'nullable|string',
            ]);

            // All data will come from request->input()
            $quantity       = $request->input('quantity', 1);
            $deliveryCharge = $request->input('delivery_charge', 0);
            $salePrice      = $request->input('sale_price', 0);

            $totalAmount = ($salePrice * $quantity) + $deliveryCharge;

            // Offer create
            $offer = Offer::create([
                'sender_id'       => $sender->id,
                'receiver_id'     => $receiver->id,
                'product_id'      => $request->input('product_id'),
                'product_name'    => $request->input('product_name'),
                'quantity'        => $quantity,
                'sale_price'      => $salePrice,
                'delivery_charge' => $deliveryCharge,
                'color' => $request->input('color'),
                'size' => $request->input('size'),
                'total_amount'    => $totalAmount,
                'status'          => 'pending',
                'note'            => $request->input('note'),
            ]);

            // Chat create
            $message = Chat::create([
                'sender_id'   => $sender->id,
                'receiver_id' => $receiver->id,
                'message'     => null,
                'image'       => null,
                'public_id'   => null,
                'reply_to'    => $request->input('reply_to'),
                'is_read'     => false,
                'is_offer'    => true,
                'offer_id'    => $offer->id,
            ]);

            $message->load(['offer', 'sender', 'receiver']);

            broadcast(new MessageSent($message))->toOthers();

            return ResponseHelper::Out('success', 'Offer created successfully', $message, 200);

        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation failed', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
