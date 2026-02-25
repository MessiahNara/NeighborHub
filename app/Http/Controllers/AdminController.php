<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_posts' => Post::count(),
            'total_complaints' => Post::where('category', 'complaints')->count(),
            'total_requests' => Post::where('category', 'requests')->count(),
        ];

        // Only load recent posts for the dashboard table
        $recentPosts = Post::with('user')->latest()->take(10)->get();

        $categories = ['buy-sell', 'borrow', 'events', 'services', 'places', 'announcements', 'complaints', 'requests'];
        $chartData = [];
        foreach ($categories as $cat) {
            $chartData[] = Post::where('category', $cat)->count();
        }

        return view('admin.dashboard', compact('stats', 'recentPosts', 'chartData', 'categories'));
    }

    // New method for the dedicated Users Tab
    public function users()
    {
        $users = User::latest()->get();
        return view('admin.users', compact('users'));
    }

    public function toggleBan(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'You cannot ban an admin.');
        }

        $user->is_banned = !$user->is_banned;
        $user->save();

        $status = $user->is_banned ? 'banned' : 'unbanned';
        return back()->with('success', "User has been {$status}.");
    }

    // New method to promote users to admin
    public function promote(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'User is already an admin.');
        }

        $user->role = 'admin';
        $user->is_banned = false; // Ensure they aren't banned if promoted
        $user->save();

        return back()->with('success', "{$user->name} has been promoted to Admin.");
    }

    public function deletePost(Post $post)
    {
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
        return back()->with('success', 'Post deleted by Admin.');
    }
}