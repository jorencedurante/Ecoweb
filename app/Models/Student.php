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
}
