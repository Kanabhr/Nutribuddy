<?php

namespace App\Models\Nutrition;

use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'dob',
        'sex',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function dietSubscriptions()
    {
        return $this->hasMany(\App\Models\DietSubscription::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(\App\Models\DietSubscription::class);
    }

    public function assessments()
    {
        return $this->hasMany(ChildAssessment::class);
    }
}
