<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'category', 
        'title', 
        'description', 
        'event_date',
        'price', 
        'tags',
        'image', 
    ];

    protected $casts = [
        'image' => 'array',
    ];

    // THIS IS THE IMPORTANT PART
    // It tells Laravel: "Every post belongs to a User"
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}