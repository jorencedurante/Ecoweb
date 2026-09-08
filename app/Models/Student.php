<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Student extends Model
{
    protected $fillable = [
        'student_id', 'lrn', 'first_name', 'middle_name', 'last_name', 'full_name', 'gender',
        'birth_date', 'age', 'mother_tongue', 'ip_ethnic_group', 'religion',
        'house_street', 'barangay', 'municipality_city', 'province',
        'father_name', 'mother_maiden_name', 'guardian_name', 'guardian_relationship',
        'contact_number', 'learning_modality', 'remarks',
        'grade_level', 'qr_code', 'total_points', 'status', 'teacher_id',
    ];

    protected $appends = ['full_name'];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name]);
        return implode(' ', $parts);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id');
    }

    public function bottleCollections(): HasMany
    {
        return $this->hasMany(BottleCollection::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    public function earnedAchievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class);
    }

    public function certificateAwards(): HasMany
    {
        return $this->hasMany(CertificateAward::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(StudentClaim::class);
    }

    public function awards(): HasMany
    {
        return $this->certificateAwards();
    }

    public function certificates(): HasMany
    {
        return $this->certificateAwards();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isTeacher()) {
            return $query->whereHas('enrollments', function ($q) use ($user) {
                $q->where('teacher_id', $user->id)->where('status', 'active');
            });
        }

        return $query;
    }

    public function scopeWhereTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->whereHas('enrollments', function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId)->where('status', 'active');
        });
    }

    /**
     * Create StudentAchievement records for every active quest the student
     * already qualifies for based on points / bottle count.
     *
     * Uses firstOrCreate so existing records are never duplicated.
     */
    public function syncEarnedAchievements(?int $awardedBy = null): int
    {
        $totalBottles = $this->bottleCollections()->sum('bottle_count');
        $totalPoints = $this->total_points ?? $totalBottles;

        $quests = Achievement::whereNull('student_id')
            ->where('status', 'Active')
            ->get();

        $earnedCount = 0;

        foreach ($quests as $quest) {
            $completedByBottles = $quest->required_bottles > 0 && $totalBottles >= $quest->required_bottles;
            $completedByPoints = $quest->points_required > 0 && $totalPoints >= $quest->points_required;

            if ($completedByBottles || $completedByPoints) {
                StudentAchievement::firstOrCreate(
                    ['student_id' => $this->id, 'achievement_quest_id' => $quest->id],
                    ['awarded_date' => now()->toDateString(), 'awarded_by' => $awardedBy]
                );
                $earnedCount++;
            }
        }

        return $earnedCount;
    }

    /**
     * Calculate progress toward the next achievement quest.
     *
     * Returns:
     *  - totalBottles / totalPoints
     *  - nextAchievement (null when everything is completed)
     *  - nextGoalValue / metricLabel / previousGoalValue
     *  - progress (0-100, range based between previous and next goal)
     *  - pointsNeeded
     *  - allAchievementsCompleted
     */
    public function achievementProgressData(): array
    {
        $totalBottles = $this->bottleCollections()->sum('bottle_count');
        $totalPoints = $this->total_points ?? $totalBottles;

        $quests = Achievement::whereNull('student_id')
            ->where('status', 'Active')
            ->get();

        $earnedQuestIds = $this->earnedAchievements->pluck('achievement_quest_id')->all();

        $evaluated = $quests
            ->filter(function ($quest) {
                return $quest->required_bottles > 0 || $quest->points_required > 0;
            })
            ->map(function ($quest) use ($totalBottles, $totalPoints, $earnedQuestIds) {
                $usesBottles = $quest->required_bottles > 0;
                $required = $usesBottles ? $quest->required_bottles : $quest->points_required;

                $isCompleted = in_array($quest->id, $earnedQuestIds)
                    || ($quest->required_bottles > 0 && $totalBottles >= $quest->required_bottles)
                    || ($quest->points_required > 0 && $totalPoints >= $quest->points_required);

                return [
                    'quest' => $quest,
                    'required' => $required,
                    'usesBottles' => $usesBottles,
                    'currentValue' => $usesBottles ? $totalBottles : $totalPoints,
                    'isCompleted' => $isCompleted,
                ];
            })
            ->sortBy('required')
            ->values();

        $next = $evaluated->first(fn ($item) => !$item['isCompleted']);

        $completedQuests = $evaluated->filter(fn ($item) => $item['isCompleted']);

        if (!$next) {
            return [
                'totalBottles' => $totalBottles,
                'totalPoints' => $totalPoints,
                'nextAchievement' => null,
                'nextGoalValue' => 0,
                'metricLabel' => 'Points',
                'previousGoalValue' => 0,
                'progress' => 100,
                'pointsNeeded' => 0,
                'allAchievementsCompleted' => true,
            ];
        }

        $previousGoalValue = $completedQuests
            ->filter(fn ($item) => $item['required'] < $next['required'])
            ->max('required') ?? 0;

        $goalRange = max(1, $next['required'] - $previousGoalValue);
        $progress = min(100, max(0, (($next['currentValue'] - $previousGoalValue) / $goalRange) * 100));
        $pointsNeeded = max(0, $next['required'] - $next['currentValue']);

        return [
            'totalBottles' => $totalBottles,
            'totalPoints' => $totalPoints,
            'nextAchievement' => $next['quest'],
            'nextGoalValue' => $next['required'],
            'metricLabel' => $next['usesBottles'] ? 'Bottles' : 'Points',
            'previousGoalValue' => $previousGoalValue,
            'progress' => $progress,
            'pointsNeeded' => $pointsNeeded,
            'allAchievementsCompleted' => false,
        ];
    }
}
