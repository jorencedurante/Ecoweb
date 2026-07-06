<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'position', 'profile_photo', 'status',
        'email_verification_code', 'email_verification_expires_at', 'email_verified_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
        ];
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
        ])->save();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'super_admin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdminLevel(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'teacher_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AdminActivity::class);
    }

    public function notificationSettings(): HasOne
    {
        return $this->hasOne(NotificationSetting::class);
    }
}
