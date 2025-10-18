<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'sender_id' => 'required',
            'receiver_id' => 'required',
            'sender_role' => 'required',
            'receiver_role' => 'required',
            'type' => 'required',
        ]);
        if (!canChat($request->sender_role, $request->receiver_role)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $fileUrl = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('chat_media', 'public');
            $fileUrl = asset('storage/' . $path);
        }
        $chat = Chat::create([
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'sender_role' => $request->sender_role,
            'receiver_role' => $request->receiver_role,
            'type' => $request->type,
            'message' => $request->message ?? null,
            'media_url' => $fileUrl,
            'reply_to' => $request->reply_to ?? null,
        ]);
        broadcast(new MessageSent($chat, $request->receiver_id))->toOthers();
        return response()->json(['success' => true, 'chat' => $chat]);
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
