<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BottleCollection extends Model
{
    protected $fillable = [
        'student_id', 'lrn', 'collection_date', 'collection_time',
        'bottle_count', 'points_earned', 'created_by'
    ];

    protected $casts = [
        'collection_date' => 'date',
        'collection_time' => 'string',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
