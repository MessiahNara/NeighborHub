<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

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

    public function users()
    {
        $users = User::latest()->get();
        return view('admin.users', compact('users'));
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:user,moderator,admin'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified_at' => now(), 
            'is_banned' => false,
        ]);

        return back()->with('success', "Account for {$request->name} has been created successfully!");
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

    public function promote(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'User is already an admin.');
        }

        $user->role = 'admin';
        $user->is_banned = false; 
        $user->save();

        return back()->with('success', "{$user->name} has been promoted to Admin.");
    }

    public function promoteMod(User $user)
    {
        if ($user->role === 'admin' || $user->role === 'moderator') {
            return back()->with('error', 'User already has elevated privileges.');
        }

        $user->role = 'moderator';
        $user->is_banned = false;
        $user->save();

        return back()->with('success', "{$user->name} has been promoted to Moderator.");
    }

    // 👇 NEW: Update Official Barangay Roles 👇
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:user,admin,captain,kagawad,sk_chairman,sk_kagawad'
        ]);

        $user->role = $request->role;
        $user->save();

        return back()->with('success', "Role updated successfully for {$user->name}.");
    }

    // 👇 NEW: Verify User ID Documents 👇
    public function verifyUser(Request $request, User $user)
    {
        $request->validate([
            'is_verified' => 'required|boolean'
        ]);

        $user->is_verified = $request->is_verified;
        $user->save();

        $statusText = $user->is_verified ? 'Verified' : 'Pending/Rejected';
        return back()->with('success', "Verification status for {$user->name} set to {$statusText}.");
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
        return back()->with('success', 'Post deleted.');
    }

    public function reports()
    {
        $reports = \App\Models\Report::with(['post', 'user', 'post.user'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('admin.reports', compact('reports'));
    }

    public function resolveReport(\App\Models\Report $report)
    {
        $post = $report->post;

        if ($post) {
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
        }

        \App\Models\Report::where('post_id', $report->post_id)->update(['status' => 'resolved']);

        return back()->with('success', 'Post deleted and report(s) marked as resolved.');
    }

    public function dismissReport(\App\Models\Report $report)
    {
        $report->update(['status' => 'dismissed']);
        
        return back()->with('success', 'Report dismissed. The post remains visible.');
    }

    // ==========================================
    // --- DYNAMIC TAG MANAGEMENT ---
    // ==========================================

    public function tags()
    {
        $tags = \App\Models\Tag::latest()->get();
        $categories = ['buy-sell', 'borrow', 'events', 'services', 'places', 'announcements', 'complaints', 'requests'];
        
        return view('admin.tags', compact('tags', 'categories'));
    }

    public function storeTag(Request $request)
    {
        $request->validate([
            'category_slug' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        \App\Models\Tag::create([
            'category_slug' => $request->category_slug,
            'name' => $request->name,
        ]);

        return back()->with('success', 'New tag added successfully!');
    }

    public function deleteTag(\App\Models\Tag $tag)
    {
        $tag->delete();
        return back()->with('success', 'Tag removed successfully!');
    }
}