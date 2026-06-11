<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAchievement extends Model
{
    protected $table = 'student_achievements';

    protected $fillable = [
        'student_id',
        'achievement_quest_id',
        'awarded_date',
        'awarded_by',
    ];

    protected $casts = [
        'awarded_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_quest_id');
    }

    public function awardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }
}
