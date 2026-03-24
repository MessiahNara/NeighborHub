<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Like;
use App\Models\Report; // <--- ADDED REPORT MODEL
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    // --- FETCH POST DETAILS FOR THE POPUP MODAL ---
    public function show($id)
    {
        // Find the post and load the user and likes
        $post = Post::with(['user:id,name', 'likes'])->findOrFail($id);

        // Check if currently logged in user has liked this post
        $isLiked = false;
        if (Auth::check()) {
            $isLiked = $post->likes->contains('user_id', Auth::id());
        }

        // Add the calculated like data to the JSON response
        $post->likes_count = $post->likes->count();
        $post->is_liked_by_user = $isLiked;

        return response()->json($post);
    }

    // --- TOGGLE LIKE ON A POST ---
    public function toggleLike(Post $post)
    {
        $user = Auth::user();
        
        // Check if the user already liked this post
        $like = $post->likes()->where('user_id', $user->id)->first();

        if ($like) {
            // Already liked, so unlike it (delete the record)
            $like->delete();
            $liked = false;
        } else {
            // Not liked yet, create a new like record
            $post->likes()->create(['user_id' => $user->id]);
            $liked = true;
        }

        // Return the new like count to update the frontend instantly
        return response()->json([
            'liked' => $liked,
            'count' => $post->likes()->count()
        ]);
    }

    // --- NEW: REPORT A POST ---
    public function report(Request $request, Post $post)
    {
        // Validate the incoming report data
        $request->validate([
            'reason' => 'required|string|max:255',
            'details' => 'nullable|string|max:1000',
        ]);

        // Prevent spamming: Check if this user already reported this post and it's still pending
        $existingReport = Report::where('user_id', Auth::id())
                            ->where('post_id', $post->id)
                            ->where('status', 'pending')
                            ->first();

        if ($existingReport) {
            return back()->with('error', 'You have already reported this post. Our admins are looking into it.');
        }

        // Create the report in the database
        Report::create([
            'user_id' => Auth::id(),
            'post_id' => $post->id,
            'reason' => $request->reason,
            'details' => $request->details,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Post reported successfully. Thank you for keeping our community safe.');
    }
}