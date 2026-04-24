<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',                  
        'is_banned',             
        'barangay',              
        'profile_picture',       
        'verification_document', 
        'is_verified',           
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * 👇 Automatically format the user's name with their official Barangay Title.
     */
    public function getOfficialNameAttribute()
    {
        $titles = [
            'captain' => 'Brgy. Captain',
            'kagawad' => 'Kagawad',
            'sk_chairman' => 'SK Chairman',
            'sk_kagawad' => 'SK Kagawad',
            'barangay_secretary' => 'Brgy. Secretary', // Added
            'barangay_treasurer' => 'Brgy. Treasurer', // Added
            'admin' => 'Admin',
            'moderator' => 'Moderator'
        ];

        if (array_key_exists($this->role, $titles)) {
            return $titles[$this->role] . ' ' . $this->name;
        }

        return $this->name; // If standard user, just return normal name
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'sender_id')
                    ->orWhere('receiver_id', $this->id);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}