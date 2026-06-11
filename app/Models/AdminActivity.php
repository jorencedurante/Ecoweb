<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivity extends Model
{
    protected $table = 'admin_activities';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'action', 'description', 'module'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
