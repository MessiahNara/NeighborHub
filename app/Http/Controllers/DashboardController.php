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
        $counts = [
            'buy_sell'   => Post::where('category', 'buy-sell')->count(),
            'borrow'     => Post::where('category', 'borrow')->count(),
            'events'     => Post::where('category', 'events')->count(),
            'services'   => Post::where('category', 'services')->count(),
            'places'     => Post::where('category', 'places')->count(),
            'announce'   => Post::where('category', 'announcements')->count(),
            'complaints' => Post::where('category', 'complaints')->count(),
            'requests'   => Post::where('category', 'requests')->count(),
        ];

        return view('dashboard', compact('counts'));
    }

    /**
     * Show a specific category.
     */
    public function show($type)
    {
        $categoryMap = [
            'announce' => 'announcements',
            'buy_sell' => 'buy-sell',
            'request'  => 'requests',
        ];

        $dbCategory = $categoryMap[$type] ?? $type;
        $posts = Post::where('category', $dbCategory)->latest()->get();
        
        return view('category', compact('posts', 'type'));
    }

    /**
     * Save a new post to the MySQL database.
     */
    public function store(Request $request)
    {
        // 1. UPDATE: Added 'tags' to validation
        $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required',
            'description' => 'nullable',
            'price'       => 'nullable|numeric',
            'event_date'  => 'nullable|date',
            'tags'        => 'nullable|string', // Validates the new tag dropdown
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // 2. UPDATE: Added 'tags' to the list of saved fields
        $data = $request->only(['title', 'category', 'description', 'price', 'event_date', 'tags']);
        
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
            'tags' => $post->tags, // 3. UPDATE: Send tags to the frontend
            'category' => $post->category,
            'created_at' => $post->created_at,
            'user' => $post->user,
            'image' => $post->image
        ]);
    }
}