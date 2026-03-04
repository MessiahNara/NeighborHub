<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function fetchInbox(Request $request)
    {
        $user = Auth::user();
        $category = $request->query('category');

        $query = Conversation::where(function($q) use ($user) {
            $q->where('sender_id', $user->id)
              ->orWhere('receiver_id', $user->id);
        })->has('messages'); 

        if ($category) {
            $query->whereHas('post', function($q) use ($category) {
                $q->where('category', $category);
            });
        }

        $conversations = $query->with(['post', 'sender', 'receiver', 'messages' => function($q) {
            $q->latest(); 
        }])->get()->sortByDesc(function($conv) {
            return $conv->messages->first() ? $conv->messages->first()->created_at : $conv->created_at;
        })->values();

        return response()->json($conversations);
    }

    public function fetchMessages(Post $post)
    {
        if ($post->user_id === Auth::id()) {
            $conversation = Conversation::where('post_id', $post->id)
                ->where('receiver_id', Auth::id())
                ->latest()
                ->first();

            if (!$conversation) {
                return response()->json([
                    'messages' => [], 
                    'current_user_id' => Auth::id(),
                    'post_owner_id' => $post->user_id // <--- ADDED
                ]);
            }
        } else {
            $conversation = Conversation::firstOrCreate([
                'post_id' => $post->id,
                'sender_id' => Auth::id(),
                'receiver_id' => $post->user_id,
            ]);
        }

        $conversation->messages()->where('user_id', '!=', Auth::id())->update(['is_read' => true]);
        $messages = $conversation->messages()->with('user:id,name')->get();

        return response()->json([
            'messages' => $messages,
            'current_user_id' => Auth::id(),
            'post_owner_id' => $post->user_id // <--- ADDED
        ]);
    }

    public function sendMessage(Request $request, Post $post)
    {
        $request->validate(['body' => 'required|string']);

        if ($post->user_id === Auth::id()) {
            $conversation = Conversation::where('post_id', $post->id)
                ->where('receiver_id', Auth::id())
                ->latest()
                ->first();
        } else {
            $conversation = Conversation::where('post_id', $post->id)
                ->where('sender_id', Auth::id())
                ->where('receiver_id', $post->user_id)
                ->first();
        }

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $message = $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
        ]);

        return response()->json([
            'message' => $message->load('user:id,name'),
            'post_owner_id' => $post->user_id // <--- ADDED
        ]);
    }
}