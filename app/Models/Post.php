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
        'condition',
        'tags',
        'image',
        'status',
        'location',  // <--- ADDED for specific place names (e.g. Zone 1)
        'latitude',  // <--- ADDED for the interactive map pin
        'longitude', // <--- ADDED for the interactive map pin
        'transaction_status',
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

    // <--- ADDED THIS RELATIONSHIP FOR THE NEW LIKES FEATURE
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function reports()
    {   
        return $this->hasMany(Report::class);
    }   
}