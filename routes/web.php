<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatController; 
use App\Http\Controllers\PostController; 
use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Models\Message; // <--- ADDED MESSAGE MODEL

// FORCE LOGIN: Send everyone to login first
Route::get('/', function () {
    return redirect()->route('login');
});

// Temporary Route for debugging
Route::get('/check-role', function () {
    if (Auth::check()) {
        return 'You are logged in! Your current role in the system is: <b>' . Auth::user()->role . '</b>';
    }
    return 'You are not logged in right now.';
});

// PROTECTED ROUTES (For regular logged-in users)
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Main Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Category Pages
    Route::get('/category/{type}', [DashboardController::class, 'show'])->name('category.show');
    
    // Database Actions
    Route::post('/post', [DashboardController::class, 'store'])->name('post.store');
    Route::delete('/post/{id}', [DashboardController::class, 'destroy'])->name('post.destroy');

    // Update Appointment Status (Admin Scheduling)
    Route::patch('/post/{id}/status', [DashboardController::class, 'updateStatus'])->name('post.updateStatus');

    // --- NEW: API for Popups and Likes ---
    Route::get('/api/post/{id}', [PostController::class, 'show']);
    Route::post('/post/{post}/like', [PostController::class, 'toggleLike'])->name('post.like');

    // Profile Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- NEW: CHAT & INBOX ROUTES ---
    // These routes handle the background data for the floating chat box
    Route::get('/api/chat/{post}', [ChatController::class, 'fetchMessages'])->name('api.chat.fetch');
    Route::post('/api/chat/{post}', [ChatController::class, 'sendMessage'])->name('api.chat.send');
    
    // NEW: Background route for the slide-out category-specific inbox!
    Route::get('/api/inbox', [ChatController::class, 'fetchInbox'])->name('api.chat.inbox');
    
    // Full-page Inbox Route (Kept just in case you ever want a main page for it)
    Route::get('/inbox', [ChatController::class, 'index'])->name('chat.index');

    // --- FIXED: NOTIFICATIONS CLEAR ROUTE ---
    // This safely marks all messages sent to you as "read" without needing a notifications table!
    Route::post('/notifications/clear', function() {
        $userId = Auth::id();
        
        Message::where('is_read', false)
            ->where('user_id', '!=', $userId) // Messages NOT sent by the current user
            ->whereHas('conversation', function($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->orWhere('receiver_id', $userId);
            })
            ->update(['is_read' => true]);

        return response()->json(['status' => 'cleared']);
    })->name('notifications.clear');
});

// ADMIN ROUTES (Strictly for users with the 'admin' role)
Route::middleware(['auth', 'verified', IsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard View
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    
    // Manage Users View
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    
    // Ban/Unban Users
    Route::post('/users/{user}/toggle-ban', [AdminController::class, 'toggleBan'])->name('users.toggleBan');
    
    // Promote User to Admin
    Route::post('/users/{user}/promote', [AdminController::class, 'promote'])->name('users.promote');
    
    // Admin Post Deletion
    Route::delete('/posts/{post}', [AdminController::class, 'deletePost'])->name('posts.destroy');
});

require __DIR__.'/auth.php';