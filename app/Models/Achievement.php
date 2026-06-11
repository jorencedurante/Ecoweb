<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $fillable = [
        'student_id', 'title', 'description', 'badge_name',
        'milestone', 'points_required', 'achieved_date',
        'current_milestone', 'next_milestone', 'progress_value', 'status',
        'required_bottles', 'created_by'
    ];

    protected $casts = [
        'achieved_date' => 'date',
        'current_milestone' => 'integer',
        'next_milestone' => 'integer',
        'progress_value' => 'integer',
        'required_bottles' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function studentAchievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class, 'achievement_quest_id');
    }
}
