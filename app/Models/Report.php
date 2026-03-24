<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'post_id', 'reason', 'details', 'status'];

    // The user who submitted the report
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // The post that was reported
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}