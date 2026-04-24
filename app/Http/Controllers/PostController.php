<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Like;
use App\Models\Report;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function show($id)
    {
        $post = Post::with(['user:id,name,role,profile_picture', 'likes'])->findOrFail($id);

        $isLiked = false;
        $isWishlisted = false;
        
        if (Auth::check()) {
            $isLiked = $post->likes->contains('user_id', Auth::id());
            $isWishlisted = Wishlist::where('user_id', Auth::id())->where('post_id', $post->id)->exists();
        }

        $post->likes_count = $post->likes->count();
        $post->is_liked_by_user = $isLiked;
        $post->is_wishlisted_by_user = $isWishlisted;

        // Ensure official name is sent to JSON
        $post->user->official_name = $post->user->official_name ?? $post->user->name;

        return response()->json($post);
    }

    public function toggleLike(Post $post)
    {
        $user = Auth::user();
        $like = $post->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            $post->likes()->create(['user_id' => $user->id]);
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'count' => $post->likes()->count()]);
    }

    // 👇 NEW: Update Transaction Status (Available/Sold) 👇
    public function updateTransactionStatus(Request $request, Post $post)
    {
        // Only the owner or an admin can change the status
        if (Auth::id() !== $post->user_id && Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        
        $request->validate([
            'transaction_status' => 'required|in:available,reserved,sold',
        ]);

        $post->update([
            'transaction_status' => $request->transaction_status
        ]);
        
        return response()->json(['success' => true]);
    }

    // --- WISHLIST FUNCTIONS ---
    public function toggleWishlist(Post $post)
    {
        $user = Auth::user();
        $wishlist = Wishlist::where('user_id', $user->id)->where('post_id', $post->id)->first();

        if ($wishlist) {
            $wishlist->delete();
            $saved = false;
        } else {
            Wishlist::create(['user_id' => $user->id, 'post_id' => $post->id]);
            $saved = true;
        }

        return response()->json(['saved' => $saved]);
    }

    public function myWishlist()
    {
        // Fetch posts that the logged-in user has saved
        $wishlists = Wishlist::where('user_id', Auth::id())->with('post.user')->latest()->get();
        $posts = $wishlists->pluck('post'); // Extract just the posts
        
        $type = 'wishlist';
        return view('category', compact('posts', 'type'));
    }

    public function report(Request $request, Post $post)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $existingReport = Report::where('user_id', Auth::id())
                            ->where('post_id', $post->id)
                            ->where('status', 'pending')
                            ->first();

        if ($existingReport) {
            return back()->with('error', 'You have already reported this post. Our admins are looking into it.');
        }

        Report::create([
            'user_id' => Auth::id(),
            'post_id' => $post->id,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Post reported successfully. Thank you for keeping our community safe.');
    }
}