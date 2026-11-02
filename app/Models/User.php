<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'otp',
        'otp_expires_at',
        'coins_balance',
        'dob',
        'gender',
        'bio',
        'avatar',
        'google_id',
        'google_avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'otp_expires_at',
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
            'is_active' => 'boolean',
            'otp_expires_at' => 'datetime',
            'coins_balance' => 'integer',
        ];
    }

    public function customerAddresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function coinTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CoinTransaction::class);
    }

    public function cart(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function supportTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function blogPosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }

    public function assignedLeads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ContactLead::class, 'assigned_to');
    }

    public function assignedNutritionLeads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NutritionLead::class, 'assigned_to');
    }

    public function assessmentAttempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function dietSubscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DietSubscription::class);
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Nutrition\Child::class);
    }
}
