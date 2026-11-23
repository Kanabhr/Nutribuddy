<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'event', 'channel', 'enabled', 'subject', 'body',
        'whatsapp_campaign', 'whatsapp_params', 'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'whatsapp_params' => 'array',
    ];
}