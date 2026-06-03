<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id', 'participant_id', 'client_attempt_id', 'device_id', 'started_at',
        'submitted_at', 'last_synced_at', 'local_finished_at', 'upload_received_at', 'status',
        'cached_payload_hash', 'submission_checksum', 'idempotency_key',
        'answers_snapshot', 'score', 'meta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'local_finished_at' => 'datetime',
        'upload_received_at' => 'datetime',
        'answers_snapshot' => 'array',
        'score' => 'decimal:2',
        'meta' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(ExamParticipant::class, 'participant_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'attempt_id');
    }
}
