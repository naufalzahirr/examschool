<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QuestionBankItem extends Model
{
    use HasFactory;

    public const VISIBILITY_SCHOOL = 'school';

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITIES = [
        self::VISIBILITY_SCHOOL => 'Dibagikan untuk semua guru',
        self::VISIBILITY_PRIVATE => 'Pribadi / hanya pembuat',
    ];

    protected $fillable = [
        'teacher_id', 'question_code', 'subject', 'grade_level', 'topic', 'difficulty', 'visibility', 'type',
        'title', 'description', 'points', 'options', 'answer_key', 'correct_text', 'is_active', 'meta',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'options' => 'array',
        'answer_key' => 'array',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (QuestionBankItem $item) {
            if (! $item->question_code) {
                $item->question_code = self::generateQuestionCode();
            }
        });
    }

    public function scopeVisibleToUser(Builder $query, User $user, bool $forSelection = false): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('teacher_id', $user->id)
                ->orWhere(function (Builder $shared) {
                    $shared->where('visibility', self::VISIBILITY_SCHOOL)
                        ->where('is_active', true);
                });
        });
    }

    public function canBeManagedBy(User $user): bool
    {
        return $user->isAdmin() || (int) $this->teacher_id === (int) $user->id;
    }

    public function isSharedToSchool(): bool
    {
        return ($this->visibility ?: self::VISIBILITY_SCHOOL) === self::VISIBILITY_SCHOOL;
    }

    public static function visibilityLabels(): array
    {
        return self::VISIBILITIES;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public static function generateQuestionCode(): string
    {
        do {
            $code = 'QB-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (self::where('question_code', $code)->exists());

        return $code;
    }

    public static function typeLabels(): array
    {
        return [
            Question::TYPE_MULTIPLE_CHOICE => 'Pilihan Ganda',
            Question::TYPE_MULTIPLE_CHOICE_COMPLEX => 'Pilihan Ganda Kompleks',
            Question::TYPE_TRUE_FALSE => 'Benar / Salah',
            Question::TYPE_MATCHING => 'Menjodohkan',
            Question::TYPE_SHORT_ANSWER => 'Uraian Jawaban Singkat',
        ];
    }

    public function optionsPreview(): string
    {
        if ($this->type === Question::TYPE_MATCHING) {
            return collect($this->options ?? [])
                ->map(fn ($o) => ($o['label'] ?? '').' = '.($o['match'] ?? ($o['meta']['match'] ?? '')))
                ->filter()
                ->implode(' | ');
        }

        return collect($this->options ?? [])
            ->pluck('label')
            ->filter()
            ->implode(' | ');
    }

    public function answerPreview(): string
    {
        if (in_array($this->type, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_MULTIPLE_CHOICE_COMPLEX], true)) {
            return collect($this->options ?? [])
                ->filter(fn ($o) => (bool) ($o['is_correct'] ?? false))
                ->pluck('label')
                ->filter()
                ->implode(' | ') ?: '-';
        }

        if ($this->type === Question::TYPE_TRUE_FALSE) {
            return ($this->answer_key['answer'] ?? false) ? 'Benar' : 'Salah';
        }

        if ($this->type === Question::TYPE_MATCHING) {
            return $this->optionsPreview() ?: '-';
        }

        if ($this->type === Question::TYPE_SHORT_ANSWER) {
            return collect($this->answer_key['accepted'] ?? [])->filter()->implode(' | ') ?: ($this->correct_text ?: '-');
        }

        return $this->correct_text ?: '-';
    }

    public function toQuestionPayload(): array
    {
        return [
            'question_code' => null,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'required' => true,
            'points' => (float) $this->points,
            'correct_text' => $this->correct_text,
            'answer_key' => $this->answer_key,
            'options' => $this->options ?: [],
            'settings' => ['source_bank_item_id' => $this->id],
        ];
    }
}
