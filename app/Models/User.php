<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',                  // <--- Added for Admin/Mod access
        'is_banned',             // <--- Added for Ban functionality
        'barangay',              // <--- Added for Location tracking
        'profile_picture',       // <--- NEW: Added for user avatars
        'verification_document', // <--- NEW: Added for ID uploads
        'is_verified',           // <--- NEW: Added for verification status
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ----------------------------------------------------------------------
    // OFFICIAL TITLE HELPER
    // ----------------------------------------------------------------------
    
    /**
     * Automatically format the user's name with their official Barangay Title.
     */
    public function getOfficialNameAttribute()
    {
        $titles = [
            'captain' => 'Brgy. Captain',
            'kagawad' => 'Kagawad',
            'sk_chairman' => 'SK Chairman',
            'sk_kagawad' => 'SK Kagawad',
            'admin' => 'Admin'
        ];

        if (array_key_exists($this->role, $titles)) {
            return $titles[$this->role] . ' ' . $this->name;
        }

        return $this->name; // If standard user, just return normal name
    }

    // ----------------------------------------------------------------------
    // CHAT SYSTEM RELATIONSHIPS
    // ----------------------------------------------------------------------

    /**
     * Get all conversations the user is a part of (either as sender or receiver).
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'sender_id')
                    ->orWhere('receiver_id', $this->id);
    }

    /**
     * Get all messages sent by this user.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}