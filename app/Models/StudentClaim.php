<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClaim extends Model
{
    protected $table = 'student_claims';

    protected $fillable = [
        'student_id',
        'claim_item_id',
        'item_name',
        'points_deducted',
        'points_before',
        'points_after',
        'claim_date',
        'claimed_by',
        'remarks',
    ];

    protected $casts = [
        'claim_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ClaimItem::class, 'claim_item_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }
}
