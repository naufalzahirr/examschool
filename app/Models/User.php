<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_PROCTOR = 'proctor';

    public const ROLES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_TEACHER => 'Guru',
        self::ROLE_PROCTOR => 'Pengawas',
    ];

    protected $fillable = [
        'name', 'email', 'username', 'phone', 'password', 'role', 'is_active',
        'must_change_password', 'last_login_at', 'meta',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isProctor(): bool
    {
        return $this->role === self::ROLE_PROCTOR;
    }

    public function canManageMasterData(): bool
    {
        return $this->isAdmin();
    }

    public function canManageExam(Exam $exam): bool
    {
        return $this->isAdmin() || ((int) $exam->teacher_id === (int) $this->id && $this->isTeacher());
    }

    public function canMonitorExam(Exam $exam): bool
    {
        return $this->isAdmin() || $this->isProctor() || $this->canManageExam($exam);
    }
}
