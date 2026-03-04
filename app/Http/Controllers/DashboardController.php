<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    /**
     * Show the Main Dashboard with dynamic post counts.
     */
    public function index()
    {
        // Check if the current user is an admin
        $isAdmin = Auth::check() && Auth::user()->role === 'admin';
        $userId = Auth::id();

        $counts = [
            'buy_sell'   => Post::where('category', 'buy-sell')->count(),
            'borrow'     => Post::where('category', 'borrow')->count(),
            'events'     => Post::where('category', 'events')->count(),
            'services'   => Post::where('category', 'services')->count(),
            'announce'   => Post::where('category', 'announcements')->count(),
            
            // Only count these if the user is an admin. Otherwise, send 'null' (empty).
            'places'     => $isAdmin ? Post::where('category', 'places')->count() : null,
            'complaints' => $isAdmin ? Post::where('category', 'complaints')->count() : null,
            'requests'   => $isAdmin ? Post::where('category', 'requests')->count() : null,
        ];

        // Start building the recent updates query
        $recentUpdatesQuery = Post::with('user');

        if ($isAdmin) {
            $recentUpdatesQuery->where('user_id', '!=', $userId)->orderBy('created_at', 'desc');
        } else {
            $recentUpdatesQuery->where(function($query) use ($userId) {
                $query->where('user_id', '!=', $userId)
                      ->whereNotIn('category', ['complaints', 'requests']);
            })->orWhere(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->whereIn('category', ['complaints', 'requests']);
            })->orderBy('updated_at', 'desc');
        }

        $recentUpdates = $recentUpdatesQuery->take(5)->get();

        // --- BULLETPROOF FIX: CATCH ALL UNREAD FORMATS (FALSE, 0, OR NULL) ---
        $chatNotifications = \App\Models\Message::where(function($query) {
                // This guarantees we catch unread messages no matter how your database saves them
                $query->where('is_read', false)
                      ->orWhere('is_read', 0)
                      ->orWhereNull('is_read');
            })
            ->where('user_id', '!=', $userId) // Messages NOT sent by me
            ->whereHas('conversation', function($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->orWhere('receiver_id', $userId);
            })
            ->with(['user', 'conversation.post']) // Load the sender and the post it belongs to
            ->latest()
            ->get();

        return view('dashboard', compact('counts', 'recentUpdates', 'chatNotifications'));
    }

    /**
     * Show a specific category with search and tag filtering.
     */
    public function show(Request $request, $type)
    {
        $categoryMap = [
            'announce' => 'announcements',
            'buy_sell' => 'buy-sell',
            'request'  => 'requests',
        ];

        $dbCategory = $categoryMap[$type] ?? $type;
        
        $query = Post::where('category', $dbCategory);

        // Privacy control for requests and complaints
        $privateCategories = ['requests', 'complaints'];
        if (in_array($dbCategory, $privateCategories)) {
            if (Auth::user()->role !== 'admin') {
                $query->where('user_id', Auth::id());
            }
        }

        if ($request->filled('my_posts') && $request->my_posts == '1') {
            $query->where('user_id', Auth::id());
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        $posts = $query->latest()->get();
        
        return view('category', compact('posts', 'type'));
    }

    /**
     * Save a new post to the MySQL database.
     */
    public function store(Request $request)
    {
        $rules = [
            'title'       => 'required|string|max:255',
            'category'    => 'required',
            'description' => 'nullable',
            'price'       => 'nullable|numeric',
            'event_date'  => 'nullable|date',
            'tags'        => 'nullable|array', 
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ];

        if (in_array($request->category, ['buy-sell', 'buy_sell'])) {
            $rules['condition'] = 'required|string';
        } else {
            $rules['condition'] = 'nullable|string';
        }

        $request->validate($rules);

        $adminOnlyCategories = ['announce', 'announcements', 'event', 'events'];
        
        if (in_array($request->category, $adminOnlyCategories) && Auth::user()->role !== 'admin') {
            return back()->with('error', 'Only administrators can post in the Announcements or Events category.');
        }

        $data = $request->only(['title', 'category', 'description', 'price', 'condition', 'event_date', 'tags']);
        
        if ($data['category'] === 'announce') {
            $data['category'] = 'announcements';
        } elseif ($data['category'] === 'event') {
            $data['category'] = 'events';
        }
        
        if ($request->hasFile('images')) {
            $imageNames = [];
            foreach ($request->file('images') as $image) {
                $name = time() . '_' . uniqid() . '.' . $image->extension();
                $image->move(public_path('uploads'), $name);
                $imageNames[] = $name;
            }
            $data['image'] = $imageNames;
        }

        $data['user_id'] = Auth::id();

        Post::create($data);

        return back()->with('success', 'Post created successfully.');
    }

    /**
     * Update the status and appointment date of a request (Admin Only)
     */
    public function updateStatus(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        if (Auth::user()->role !== 'admin') {
            return abort(403, 'Unauthorized action.');
        }

        $post->status = $request->status;
        
        if ($request->filled('event_date')) {
            $post->event_date = Carbon::parse($request->event_date)->format('Y-m-d H:i:s');
        } else {
            $post->event_date = null;
        }

        $post->save();

        return back()->with('success', 'Appointment scheduled/updated successfully!');
    }

    /**
     * Delete a post.
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        if (Auth::user()->role === 'admin' || Auth::id() === $post->user_id) {
            if ($post->image) {
                $images = is_array($post->image) ? $post->image : json_decode($post->image, true);
                if (is_array($images)) {
                    foreach ($images as $img) {
                        $filePath = public_path('uploads/' . $img);
                        if (File::exists($filePath)) {
                            File::delete($filePath);
                        }
                    }
                }
            }

            $post->delete();
            return back()->with('success', 'Post deleted.');
        }

        return abort(403, 'Unauthorized action.');
    }
}