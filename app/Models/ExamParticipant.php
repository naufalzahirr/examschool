<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id', 'student_id', 'device_id', 'status', 'locked_until', 'submitted_at', 'score', 'meta',
        'package_queue_joined_at', 'package_queue_started_at', 'package_queue_expires_at',
        'package_queue_token', 'package_download_started_at', 'package_download_finished_at',
        'package_download_attempts_count', 'package_unlock_key_issued_at',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
        'submitted_at' => 'datetime',
        'package_queue_joined_at' => 'datetime',
        'package_queue_started_at' => 'datetime',
        'package_queue_expires_at' => 'datetime',
        'package_download_started_at' => 'datetime',
        'package_download_finished_at' => 'datetime',
        'package_unlock_key_issued_at' => 'datetime',
        'score' => 'decimal:2',
        'meta' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'participant_id');
    }
}
