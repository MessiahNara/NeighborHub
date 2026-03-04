<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatController; 
use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;

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

    // API for Popups (With User Name Fix)
    Route::get('/api/post/{id}', function ($id) {
        return Post::with('user')->findOrFail($id);
    });

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