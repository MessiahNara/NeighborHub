<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        // Gather stats for the dashboard graphs and overviews
        $stats = [
            'total_users' => User::count(),
            'total_posts' => Post::count(),
            'total_complaints' => Post::where('category', 'complaints')->count(),
            'total_requests' => Post::where('category', 'requests')->count(),
        ];

        // Get users and recent posts for the data tables
        $users = User::latest()->get();
        $recentPosts = Post::with('user')->latest()->take(10)->get();

        // Prepare data for Chart.js (Posts by Category)
        $categories = ['buy-sell', 'borrow', 'events', 'services', 'places', 'announcements', 'complaints', 'requests'];
        $chartData = [];
        foreach ($categories as $cat) {
            $chartData[] = Post::where('category', $cat)->count();
        }

        return view('admin.dashboard', compact('stats', 'users', 'recentPosts', 'chartData', 'categories'));
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

    public function deletePost(Post $post)
    {
        // Delete image logic if exists (same as your DashboardController)
        if ($post->image) {
            $images = is_array($post->image) ? $post->image : json_decode($post->image, true);
            if (is_array($images)) {
                foreach ($images as $img) {
                    $filePath = public_path('uploads/' . $img);
                    if (\Illuminate\Support\Facades\File::exists($filePath)) {
                        \Illuminate\Support\Facades\File::delete($filePath);
                    }
                }
            }
        }
        
        $post->delete();
        return back()->with('success', 'Post deleted by Admin.');
    }
}