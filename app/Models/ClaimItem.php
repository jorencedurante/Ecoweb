<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimItem extends Model
{
    protected $fillable = [
        'item_name',
        'description',
        'points_required',
        'quantity',
        'status',
        'created_by',
    ];

    public function claims(): HasMany
    {
        return $this->hasMany(StudentClaim::class, 'claim_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
