<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id', 'question_id', 'value', 'is_correct', 'score', 'answered_at', 'meta',
    ];

    protected $casts = [
        'value' => 'array',
        'is_correct' => 'boolean',
        'score' => 'decimal:2',
        'answered_at' => 'datetime',
        'meta' => 'array',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
