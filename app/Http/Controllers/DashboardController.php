<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    public function index()
    {
        $userRole = Auth::check() ? strtolower(trim(Auth::user()->role)) : 'user';
        $hasElevatedAccess = in_array($userRole, ['admin', 'moderator']);
        $userId = Auth::id();

        $counts = [
            'buy_sell'   => Post::where('category', 'buy-sell')->count(),
            'borrow'     => Post::where('category', 'borrow')->count(),
            'events'     => Post::where('category', 'events')->count(),
            'services'   => Post::where('category', 'services')->count(),
            'announce'   => Post::where('category', 'announcements')->count(),
            'places'     => $hasElevatedAccess ? Post::where('category', 'places')->count() : null,
            'complaints' => $hasElevatedAccess ? Post::where('category', 'complaints')->count() : null,
            'requests'   => $hasElevatedAccess ? Post::where('category', 'requests')->count() : null,
        ];

        $recentUpdatesQuery = Post::with('user');

        if ($hasElevatedAccess) {
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

        $chatNotifications = \App\Models\Message::where(function($query) {
                $query->where('is_read', false)
                      ->orWhere('is_read', 0)
                      ->orWhereNull('is_read');
            })
            ->where('user_id', '!=', $userId)
            ->whereHas('conversation', function($q) use ($userId) {
                $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->with(['user', 'conversation.post']) 
            ->latest()
            ->get();

        return view('dashboard', compact('counts', 'recentUpdates', 'chatNotifications'));
    }

    public function show(Request $request, $type)
    {
        $categoryMap = [
            'announce' => 'announcements',
            'buy_sell' => 'buy-sell',
            'request'  => 'requests',
        ];

        $dbCategory = $categoryMap[$type] ?? $type;
        $query = Post::where('category', $dbCategory);

        $privateCategories = ['requests', 'complaints'];
        if (in_array($dbCategory, $privateCategories)) {
            $userRole = Auth::check() ? strtolower(trim(Auth::user()->role)) : 'user';
            if (!in_array($userRole, ['admin', 'moderator'])) {
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

    public function store(Request $request)
    {
        $rules = [
            'title'       => 'required|string|max:255',
            'category'    => 'required',
            'description' => 'nullable',
            'price'       => 'nullable|numeric',
            'event_date'  => 'nullable|date',
            'tags'        => 'nullable|array', 
            'location'    => 'nullable|string|max:255',
            'latitude'    => 'nullable|numeric',
            'longitude'   => 'nullable|numeric',
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ];

        if (in_array($request->category, ['buy-sell', 'buy_sell'])) {
            $rules['condition'] = 'required|string';
        } else {
            $rules['condition'] = 'nullable|string';
        }

        $request->validate($rules);
        $adminOnlyCategories = ['announce', 'announcements', 'event', 'events'];
        $userRole = Auth::user()->role;
        
        if (in_array($request->category, $adminOnlyCategories) && !in_array($userRole, ['admin', 'moderator'])) {
            return back()->with('error', 'Only administrators and moderators can post in the Announcements or Events category.');
        }

        $data = $request->only(['title', 'category', 'description', 'price', 'condition', 'event_date', 'tags', 'location', 'latitude', 'longitude']);
        
        if ($data['category'] === 'announce') $data['category'] = 'announcements';
        elseif ($data['category'] === 'event') $data['category'] = 'events';
        
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

    // 👇 NEW: Edit Post Functionality 👇
    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $userRole = Auth::user()->role;

        // Ensure only owner or admin can edit
        if (Auth::id() !== $post->user_id && !in_array($userRole, ['admin', 'moderator'])) {
            return abort(403, 'Unauthorized action.');
        }

        $post->title = $request->title;
        $post->description = $request->description;
        $post->location = $request->location ?? $post->location;
        $post->latitude = $request->latitude ?? $post->latitude;
        $post->longitude = $request->longitude ?? $post->longitude;
        
        if ($request->has('price')) $post->price = $request->price;
        if ($request->has('condition')) $post->condition = $request->condition;
        if ($request->has('event_date')) $post->event_date = $request->event_date;
        if ($request->has('tags')) $post->tags = json_encode($request->tags);

        if ($request->hasFile('images')) {
            $imageNames = [];
            foreach ($request->file('images') as $image) {
                $name = time() . '_' . uniqid() . '.' . $image->extension();
                $image->move(public_path('uploads'), $name);
                $imageNames[] = $name;
            }
            $post->image = $imageNames;
        }

        $post->save();
        return back()->with('success', 'Post updated successfully!');
    }

    public function updateStatus(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $userRole = Auth::user()->role;

        if (!in_array($userRole, ['admin', 'moderator'])) return abort(403, 'Unauthorized action.');

        $post->status = $request->status;
        if ($request->filled('event_date')) {
            $post->event_date = Carbon::parse($request->event_date)->format('Y-m-d H:i:s');
        } else {
            $post->event_date = null;
        }

        $post->save();
        return back()->with('success', 'Appointment scheduled/updated successfully!');
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $userRole = Auth::user()->role;

        if (in_array($userRole, ['admin', 'moderator']) || Auth::id() === $post->user_id) {
            if ($post->image) {
                $images = is_array($post->image) ? $post->image : json_decode($post->image, true);
                if (is_array($images)) {
                    foreach ($images as $img) {
                        $filePath = public_path('uploads/' . $img);
                        if (File::exists($filePath)) File::delete($filePath);
                    }
                }
            }
            $post->delete();
            return back()->with('success', 'Post deleted.');
        }

        return abort(403, 'Unauthorized action.');
    }
}