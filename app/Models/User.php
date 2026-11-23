<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\CollaborationService;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'position', 'profile_photo', 'status',
        'assigned_teacher_id', 'sub_teacher_access_level', 'sub_teacher_permissions', 'can_print_certificates',
        'email_verification_code', 'email_verification_expires_at', 'email_verified_at',
        'pending_email', 'email_change_otp', 'email_change_otp_expires_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'sub_teacher_permissions' => 'array',
            'can_print_certificates' => 'boolean',
            'email_verified_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'email_change_otp_expires_at' => 'datetime',
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

    public function isSubTeacher(): bool
    {
        return $this->role === 'sub_teacher';
    }

    /**
     * Certificate printing is an explicit, per-account grant.
     *
     * - Admin / Principal (Super Admin) keep their existing printing ability.
     * - Teachers must be explicitly granted printing access.
     * - Sub Teachers never inherit it from their assigned Teacher: they must
     *   be active, have a valid Teacher assignment, already be allowed to
     *   access the Certificate Award module, AND hold an explicit grant.
     */
    public function canPrintCertificates(): bool
    {
        if ($this->isAdminLevel()) {
            return true;
        }

        if (!$this->isActive()) {
            return false;
        }

        if ($this->isSubTeacher()) {
            if (!$this->hasEffectiveAssignment()) {
                return false;
            }
            if (!(new CollaborationService())->can($this, CollaborationService::VIEW_CERTIFICATES)) {
                return false;
            }
        }

        return (bool) $this->can_print_certificates;
    }

    /**
     * The main Teacher this Sub Teacher is assigned to (users table only).
     */
    public function assignedTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_teacher_id');
    }

    /**
     * All Sub Teachers assigned to this Teacher (users table only).
     */
    public function subTeachers(): HasMany
    {
        return $this->hasMany(User::class, 'assigned_teacher_id');
    }

    /**
     * Read the assignment straight from the users row so a reassignment or
     * unassignment is reflected immediately, even for an in-memory user instance.
     *
     * @return array{teacher_id:int,access_level:string,permissions:array}|null
     */
    public function assignmentData(): ?array
    {
        if (!$this->isSubTeacher()) {
            return null;
        }

        $row = static::query()
            ->whereKey($this->id)
            ->first(['assigned_teacher_id', 'sub_teacher_access_level', 'sub_teacher_permissions', 'status']);

        if (!$row || !$row->isActive() || empty($row->assigned_teacher_id)) {
            return null;
        }

        $teacher = static::query()
            ->whereKey($row->assigned_teacher_id)
            ->first(['id', 'role', 'status']);

        if (!$teacher || !$teacher->isTeacher() || !$teacher->isActive()) {
            return null;
        }

        return [
            'teacher_id' => (int) $row->assigned_teacher_id,
            'access_level' => $row->sub_teacher_access_level ?: 'collaborator',
            'permissions' => is_array($row->sub_teacher_permissions) ? $row->sub_teacher_permissions : [],
        ];
    }

    public function hasEffectiveAssignment(): bool
    {
        return $this->assignmentData() !== null;
    }

    /**
     * Teacher whose records this account may work with.
     * Teachers use their own id; Sub Teachers use their assigned Teacher;
     * admins get null (global scope, preserving existing behaviour).
     */
    public function scopeTeacherId(): ?int
    {
        if ($this->isTeacher()) {
            return (int) $this->id;
        }

        $assignment = $this->assignmentData();

        return $assignment ? $assignment['teacher_id'] : null;
    }

    public function hasTeacherScope(): bool
    {
        return $this->scopeTeacherId() !== null;
    }

    public function accessLevel(): ?string
    {
        $assignment = $this->assignmentData();

        return $assignment ? $assignment['access_level'] : null;
    }

    public function customPermissions(): array
    {
        $assignment = $this->assignmentData();

        return $assignment ? $assignment['permissions'] : [];
    }

    public function assignmentStatus(): string
    {
        if (!$this->isSubTeacher()) {
            return '—';
        }

        $assigned = !empty($this->assigned_teacher_id)
            || !empty(static::query()->whereKey($this->id)->value('assigned_teacher_id'));

        if (!$assigned) {
            return 'Unassigned';
        }

        return $this->hasEffectiveAssignment() ? 'Active' : 'Inactive';
    }

    public static function roleLabel(?string $role): string
    {
        return [
            'super_admin' => 'Principal',
            'sub_teacher' => 'Sub Teacher',
            'teacher' => 'Teacher',
            'admin' => 'Admin',
        ][$role] ?? ucfirst((string) $role);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isRestricted(): bool
    {
        return $this->status === 'restricted';
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'teacher_id');
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'teacher_id');
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
