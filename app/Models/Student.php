<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'student_id', 'lrn', 'first_name', 'middle_name', 'last_name', 'full_name', 'gender',
        'grade_level', 'qr_code', 'total_points', 'status'
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name]);
        return implode(' ', $parts);
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
}
