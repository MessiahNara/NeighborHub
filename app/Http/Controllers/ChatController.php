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
                    'post_owner_id' => $post->user_id
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
            'post_owner_id' => $post->user_id
        ]);
    }

    public function sendMessage(Request $request, Post $post)
    {
        // 👇 Make body optional if there is an image, and validate the image 👇
        $request->validate([
            'body' => 'nullable|string',
            'image' => 'nullable|image|max:5120' // Max 5MB
        ]);

        if (!$request->body && !$request->hasFile('image')) {
            return response()->json(['error' => 'Message or image is required'], 422);
        }

        if ($post->user_id === Auth::id()) {
            $conversation = Conversation::where('post_id', $post->id)
                ->where('receiver_id', Auth::id())
                ->latest()
                ->first();
        } else {
            $conversation = Conversation::firstOrCreate([
                'post_id' => $post->id,
                'sender_id' => Auth::id(),
                'receiver_id' => $post->user_id,
            ]);
        }

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // 👇 Handle Image Upload 👇
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/messages'), $filename);
            $imagePath = $filename;
        }

        $message = $conversation->messages()->create([
            'user_id' => Auth::id(),
            'body' => $request->body,
            'image' => $imagePath, // Save the image path
        ]);

        return response()->json([
            'message' => $message->load('user:id,name'),
            'post_owner_id' => $post->user_id
        ]);
    }

    // 👇 NEW: Delete Conversation Logic 👇
    public function deleteConversation(Post $post)
    {
        $userId = Auth::id();
        
        $conversation = Conversation::where('post_id', $post->id)
            ->where(function($q) use ($userId) {
                $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
            })->first();

        if ($conversation) {
            $conversation->delete(); // This deletes the conversation and cascades to delete all messages inside it
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }
}