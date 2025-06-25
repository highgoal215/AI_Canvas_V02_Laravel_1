<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'subscription',
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

    /**
     * Get the user's projects
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Check if user is premium
     */
    public function getIsPremiumAttribute()
    {
        return $this->subscription === 'premium';
    }

    /**
     * Check if user is standard
     */
    public function getIsStandardAttribute()
    {
        return $this->subscription === 'standard';
    }

    /**
     * Check if user is free
     */
    public function getIsFreeAttribute()
    {
        return $this->subscription === 'free';
    }

    /**
     * Get export limits based on subscription
     */
    public function getExportLimits()
    {
        switch ($this->subscription) {
            case 'premium':
                return [
                    'max_resolution' => '4k',
                    'formats' => ['png', 'jpg', 'jpeg', 'webp', 'gif'],
                    'watermark' => false,
                    'monthly_exports' => -1, // unlimited
                ];
            case 'standard':
                return [
                    'max_resolution' => '1080p',
                    'formats' => ['png', 'jpg', 'jpeg'],
                    'watermark' => false,
                    'monthly_exports' => 50,
                ];
            default: // free
                return [
                    'max_resolution' => '720p',
                    'formats' => ['png', 'jpg'],
                    'watermark' => true,
                    'monthly_exports' => 10,
                ];
        }
    }
}
