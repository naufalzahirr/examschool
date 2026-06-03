<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Question extends Model
{
    use HasFactory;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_MULTIPLE_CHOICE_COMPLEX = 'multiple_choice_complex';
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_MATCHING = 'matching';
    public const TYPE_SHORT_ANSWER = 'short_answer';

    public const TYPES = [
        self::TYPE_MULTIPLE_CHOICE,
        self::TYPE_MULTIPLE_CHOICE_COMPLEX,
        self::TYPE_TRUE_FALSE,
        self::TYPE_MATCHING,
        self::TYPE_SHORT_ANSWER,
    ];

    protected $fillable = [
        'exam_id', 'question_code', 'type', 'title', 'description', 'required', 'points', 'order_no',
        'correct_text', 'answer_key', 'settings',
    ];

    protected $casts = [
        'required' => 'boolean',
        'points' => 'decimal:2',
        'answer_key' => 'array',
        'settings' => 'array',
    ];


    protected static function booted(): void
    {
        static::creating(function (Question $question) {
            if (!$question->question_code) {
                $question->question_code = self::generateQuestionCode();
            }
        });
    }

    public static function generateQuestionCode(): string
    {
        do {
            $code = 'Q-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        } while (self::where('question_code', $code)->exists());

        return $code;
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_no');
    }
}
