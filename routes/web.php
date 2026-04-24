<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PostController;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsModerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Models\Message;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/check-role', function () {
    if (Auth::check()) {
        return 'You are logged in! Your current role in the system is: <b>' . Auth::user()->role . '</b>';
    }
    return 'You are not logged in right now.';
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/category/{type}', [DashboardController::class, 'show'])->name('category.show');
    Route::post('/post', [DashboardController::class, 'store'])->name('post.store');
    Route::delete('/post/{id}', [DashboardController::class, 'destroy'])->name('post.destroy');
    Route::patch('/post/{id}/status', [DashboardController::class, 'updateStatus'])->name('post.updateStatus');
    Route::get('/api/post/{id}', [PostController::class, 'show']);
    Route::post('/post/{post}/like', [PostController::class, 'toggleLike'])->name('post.like');
    Route::post('/post/{post}/report', [PostController::class, 'report'])->name('post.report');
    
    // 👇 NEW: Seller Transaction Status Route 👇
    Route::patch('/api/post/{post}/transaction-status', [PostController::class, 'updateTransactionStatus']);
    
    // Wishlist Routes
    Route::post('/post/{post}/wishlist', [PostController::class, 'toggleWishlist'])->name('post.wishlist');
    Route::get('/wishlist', [PostController::class, 'myWishlist'])->name('wishlist.index');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Route for uploading Profile Pic and ID Verification
    Route::post('/profile/upload-docs', [ProfileController::class, 'uploadDocs'])->name('profile.upload-docs');

    Route::get('/api/chat/{post}', [ChatController::class, 'fetchMessages'])->name('api.chat.fetch');
    Route::post('/api/chat/{post}', [ChatController::class, 'sendMessage'])->name('api.chat.send');
    Route::get('/api/inbox', [ChatController::class, 'fetchInbox'])->name('api.chat.inbox');
    Route::get('/inbox', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/notifications/clear', function() {
        $userId = Auth::id();
        Message::where('is_read', false)
            ->where('user_id', '!=', $userId)
            ->whereHas('conversation', function($q) use ($userId) {
                $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->update(['is_read' => true]);
        return response()->json(['status' => 'cleared']);
    })->name('notifications.clear');
});

// ---------------------------------------------------------
// ADMIN-ONLY (Dashboard & Users)
// ---------------------------------------------------------
Route::middleware(['auth', 'verified', IsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users/{user}/toggle-ban', [AdminController::class, 'toggleBan'])->name('users.toggleBan');
    Route::post('/users/{user}/promote', [AdminController::class, 'promote'])->name('users.promote');
    Route::post('/users/{user}/promote-mod', [AdminController::class, 'promoteMod'])->name('users.promoteMod');
    
    // Update Roles and Verify Users
    Route::post('/users/{user}/role', [AdminController::class, 'updateRole'])->name('role');
    Route::post('/users/{user}/verify', [AdminController::class, 'verifyUser'])->name('verify');
    
    // Route for manually creating users
    Route::post('/users/create', [AdminController::class, 'createUser'])->name('users.create');

    // Manage Dynamic Tags
    Route::get('/tags', [AdminController::class, 'tags'])->name('tags');
    Route::post('/tags', [AdminController::class, 'storeTag'])->name('tags.store');
    Route::delete('/tags/{tag}', [AdminController::class, 'deleteTag'])->name('tags.destroy');
});

// ---------------------------------------------------------
// MODERATORS & ADMINS (Content Moderation)
// ---------------------------------------------------------
Route::middleware(['auth', 'verified', IsModerator::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::patch('/reports/{report}/resolve', [AdminController::class, 'resolveReport'])->name('reports.resolve');
    Route::patch('/reports/{report}/dismiss', [AdminController::class, 'dismissReport'])->name('reports.dismiss');
    
    Route::delete('/posts/{post}', [AdminController::class, 'deletePost'])->name('posts.destroy');
});

require __DIR__.'/auth.php';