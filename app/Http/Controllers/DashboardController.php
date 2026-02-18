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
        // This pulls the count of posts for each category bubble on your grid
        $counts = Post::selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('dashboard', compact('counts'));
    }

    /**
     * Show a specific category (e.g., Buy & Sell, Complaints).
     */
    public function show($type)
    {
        // Get posts for this category, newest first
        $posts = Post::where('category', $type)->latest()->get();
        
        return view('category', compact('posts', 'type'));
    }

    /**
     * Save a new post to the MySQL database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required',
            'description' => 'nullable',
            'price' => 'nullable|numeric',
            'event_date' => 'nullable|date',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048' // Max 2MB per image
        ]);

        $data = $request->only(['title', 'category', 'description', 'price', 'event_date']);
        
        // Handle multiple image uploads
        if ($request->hasFile('images')) {
            $imageNames = [];
            foreach ($request->file('images') as $image) {
                $name = time() . '_' . uniqid() . '.' . $image->extension();
                $image->move(public_path('uploads'), $name);
                $imageNames[] = $name;
            }
            $data['image'] = $imageNames; // Saved as JSON array in MySQL
        }

        // Automatically link the post to the neighbor who is logged in
        $data['user_id'] = Auth::id();

        Post::create($data);

        return back()->with('success', 'Post created successfully!');
    }

    /**
     * Delete a post (Admin or Owner only).
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        // THE MASTER PERMISSION CHECK
        // Check if user is 'admin' in phpMyAdmin OR if they own the post
        if (Auth::user()->role === 'admin' || Auth::id() === $post->user_id) {
            
            // Delete actual image files from the 'uploads' folder first
            if ($post->image) {
                foreach ($post->image as $img) {
                    $filePath = public_path('uploads/' . $img);
                    if (File::exists($filePath)) {
                        File::delete($filePath);
                    }
                }
            }

            $post->delete();
            return back()->with('success', 'Post deleted.');
        }

        // If not admin/owner, block them
        return abort(403, 'Unauthorized action.');
    }
}