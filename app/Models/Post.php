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
        'status',
    ];

    protected $casts = [
        'image' => 'array',
        'tags'  => 'array', 
        // 🛑 FIXED: This stops Laravel from erasing the time 🛑
        'event_date' => 'datetime', 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}