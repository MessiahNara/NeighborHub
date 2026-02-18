<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;

// FORCE LOGIN: Send everyone to login first
Route::get('/', function () {
    return redirect()->route('login');
});

// PROTECTED ROUTES
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Main Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Category Pages
    Route::get('/category/{type}', [DashboardController::class, 'show'])->name('category.show');
    
    // Database Actions
    Route::post('/post', [DashboardController::class, 'store'])->name('post.store');
    Route::delete('/post/{id}', [DashboardController::class, 'destroy'])->name('post.destroy');

    // API for Popups (With User Name Fix)
    Route::get('/api/post/{id}', function ($id) {
        return Post::with('user')->findOrFail($id);
    });

    // Profile Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';