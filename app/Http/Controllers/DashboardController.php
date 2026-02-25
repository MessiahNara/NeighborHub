<?php

namespace App\Http\Controllers;

use App\Models\Post;
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
            // Admins see everyone else's NEWEST posts. (Ordered by created_at)
            // This prevents them from being notified about their own status updates!
            $recentUpdatesQuery->where('user_id', '!=', $userId)
                               ->orderBy('created_at', 'desc');
        } else {
            // Regular users see: public posts from others OR status updates on their own requests.
            // Ordered by updated_at so they see the Admin's response!
            $recentUpdatesQuery->where(function($query) use ($userId) {
                $query->where('user_id', '!=', $userId)
                      ->whereNotIn('category', ['complaints', 'requests']);
            })->orWhere(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->whereIn('category', ['complaints', 'requests']);
            })->orderBy('updated_at', 'desc');
        }

        // Fetch the final 5 posts
        $recentUpdates = $recentUpdatesQuery->take(5)->get();

        return view('dashboard', compact('counts', 'recentUpdates'));
    }

    /**
     * Show a specific category with search and tag filtering.
     * UPDATE: Added logic to make complaints and requests private to the sender and admins.
     */
    public function show(Request $request, $type)
    {
        $categoryMap = [
            'announce' => 'announcements',
            'buy_sell' => 'buy-sell',
            'request'  => 'requests',
        ];

        $dbCategory = $categoryMap[$type] ?? $type;
        
        // Start building the query
        $query = Post::where('category', $dbCategory);

        // Privacy control for requests and complaints
        $privateCategories = ['requests', 'complaints'];
        if (in_array($dbCategory, $privateCategories)) {
            // If the user is NOT an admin, they can only see their own private posts
            if (Auth::user()->role !== 'admin') {
                $query->where('user_id', Auth::id());
            }
        }

        // Search by title if the user typed in the search bar
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filter by tag if selected in the category search bar
        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        $posts = $query->latest()->get();
        
        return view('category', compact('posts', 'type'));
    }

    /**
     * Save a new post to the MySQL database.
     * UPDATE: Restricts announcements and events to Admins using all possible form variations.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required',
            'description' => 'nullable',
            'price'       => 'nullable|numeric',
            'event_date'  => 'nullable|date',
            'tags'        => 'nullable|array', 
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // We added all possible variations the HTML form might send
        $adminOnlyCategories = ['announce', 'announcements', 'event', 'events'];
        
        if (in_array($request->category, $adminOnlyCategories) && Auth::user()->role !== 'admin') {
            return back()->with('error', 'Only administrators can post in the Announcements or Events category.');
        }

        $data = $request->only(['title', 'category', 'description', 'price', 'event_date', 'tags']);
        
        // Ensure the database always gets the proper full category name for consistency
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
     * NEW: Update the status and appointment date of a request (Admin Only)
     */
    public function updateStatus(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        if (Auth::user()->role !== 'admin') {
            return abort(403, 'Unauthorized action.');
        }

        $post->status = $request->status;
        
        if ($request->filled('event_date')) {
            $post->event_date = $request->event_date;
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

    /**
     * API: Fetch a single post's details for the modal popup.
     */
    public function getPostDetails($id)
    {
        $post = Post::with('user')->findOrFail($id);
        
        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'description' => $post->description,
            'price' => $post->price,
            'event_date' => $post->event_date,
            'tags' => $post->tags, 
            'category' => $post->category,
            'status' => $post->status, 
            'created_at' => $post->created_at,
            'user' => $post->user,
            'image' => $post->image
        ]);
    }
}