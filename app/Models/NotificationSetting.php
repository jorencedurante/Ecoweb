<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    protected $table = 'notification_settings';

    protected $fillable = [
        'user_id', 'bottle_collection_reports', 'system_alerts', 'email_notifications'
    ];

    protected $casts = [
        'bottle_collection_reports' => 'boolean',
        'system_alerts' => 'boolean',
        'email_notifications' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
