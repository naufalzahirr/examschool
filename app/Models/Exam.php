<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Exam extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_READY = 'ready';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_ARCHIVED = 'archived';

    public const LOCK_STANDARD = 'standard';

    public const LOCK_STRICT_AIRPLANE = 'strict_airplane';

    public const LOCK_STRICT_KIOSK = 'strict_kiosk';

    public const LOCK_MODES = [
        self::LOCK_STRICT_AIRPLANE => 'Ketat / Mode Pesawat: wajib offline, keluar/internet aktif terkunci',
        self::LOCK_STANDARD => 'Standar / BYOD: deteksi pelanggaran',
        self::LOCK_STRICT_KIOSK => 'Ketat: kunci aplikasi otomatis',
    ];

    public const EXIT_AFTER_SUBMIT = 'after_submit';

    public const EXIT_AFTER_TIME_END = 'after_time_end';

    public const EXIT_PROCTOR_CODE = 'proctor_code';

    public const EXIT_POLICIES = [
        self::EXIT_AFTER_SUBMIT => 'Boleh keluar setelah submit',
        self::EXIT_AFTER_TIME_END => 'Boleh keluar setelah waktu habis',
    ];

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_READY => 'Siap Publish',
        self::STATUS_PUBLISHED => 'Published / Siap Dikerjakan',
        self::STATUS_CLOSED => 'Selesai / Ditutup',
        self::STATUS_ARCHIVED => 'Diarsipkan',
    ];

    protected $fillable = [
        'teacher_id', 'title', 'description', 'subject', 'grade_level', 'status',
        'starts_at', 'ends_at', 'duration_minutes', 'shuffle_questions', 'shuffle_options',
        'lock_mode', 'exit_policy', 'offline_exit_code_salt', 'offline_exit_code_hash',
        'offline_exit_code_encrypted', 'offline_exit_code_generated_at',
        'access_code', 'package_version', 'package_path', 'package_disk', 'package_public_url',
        'package_is_encrypted', 'package_cipher', 'package_unlock_key_encrypted',
        'package_checksum', 'package_plain_checksum', 'package_generated_at',
        'package_size_bytes', 'package_downloads_count', 'settings', 'published_at', 'closed_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'settings' => 'array',
        'package_generated_at' => 'datetime',
        'package_is_encrypted' => 'boolean',
        'offline_exit_code_generated_at' => 'datetime',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Exam $exam) {
            if (! $exam->lock_mode) {
                $exam->lock_mode = (string) SchoolSetting::getValue('default_exam_lock_mode', self::LOCK_STRICT_AIRPLANE);
            }
            if (! $exam->exit_policy) {
                $exam->exit_policy = self::normalizeExitPolicy((string) SchoolSetting::getValue('default_exam_exit_policy', self::EXIT_AFTER_SUBMIT));
            }
            if (! $exam->access_code) {
                $exam->access_code = self::generateAccessCode();
            }
            if (! $exam->package_version) {
                $exam->package_version = 1;
            }
        });
    }

    public function lockModeLabel(): string
    {
        return self::LOCK_MODES[$this->lock_mode] ?? $this->lock_mode ?? 'Standar';
    }

    public function exitPolicyLabel(): string
    {
        $policy = self::normalizeExitPolicy($this->exit_policy);

        return self::EXIT_POLICIES[$policy];
    }

    public static function normalizeExitPolicy(?string $policy): string
    {
        return array_key_exists((string) $policy, self::EXIT_POLICIES)
            ? (string) $policy
            : self::EXIT_AFTER_SUBMIT;
    }

    public function offlineLockPayload(): array
    {
        $exitPolicy = self::normalizeExitPolicy($this->exit_policy);

        return [
            'lock_mode' => $this->lock_mode ?: self::LOCK_STANDARD,
            'exit_policy' => $exitPolicy,
            'offline_exit_code_required' => false,
            'offline_exit_code_salt' => null,
            'offline_exit_code_hash' => null,
            'offline_exit_code_algorithm' => null,
            'exit_code_generated_at' => null,
            'airplane_mode_required' => true,
            'internet_active_locks_exam' => true,
            'app_exit_locks_exam' => true,
            'reopen_requires_offline' => true,
            'reopen_requires_proctor_code' => false,
            'timer_continues_when_locked' => true,
            'strict_android_note' => 'Aplikasi siswa otomatis fullscreen, menolak lanjut saat online, mengunci saat keluar aplikasi, dan membunyikan alarm/notifikasi pelanggaran tanpa input pengawas.',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_no');
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'exam_classrooms')->withTimestamps()->orderBy('tingkat')->orderBy('nama_kelas');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public static function generateAccessCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $suffix = collect(range(1, 6))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
            $code = 'US-'.$suffix;
        } while (self::where('access_code', $code)->exists());

        return $code;
    }

    public function bumpPackageVersion(): void
    {
        if ($this->package_path && Storage::disk($this->package_disk ?: 'local')->exists($this->package_path)) {
            Storage::disk($this->package_disk ?: 'local')->delete($this->package_path);
        }

        $this->forceFill([
            'package_version' => ((int) $this->package_version) + 1,
            'package_path' => null,
            'package_public_url' => null,
            'package_checksum' => null,
            'package_plain_checksum' => null,
            'package_unlock_key_encrypted' => null,
            'package_generated_at' => null,
            'package_size_bytes' => 0,
        ])->save();
    }

    public function hasGeneratedPackage(): bool
    {
        return (bool) $this->package_path
            && (bool) $this->package_checksum
            && (bool) $this->package_generated_at
            && Storage::disk($this->package_disk ?: 'local')->exists($this->package_path);
    }

    public function packageStatusLabel(): string
    {
        if ($this->hasGeneratedPackage()) {
            return 'Siap diunduh';
        }

        if ($this->status === self::STATUS_PUBLISHED) {
            return 'Belum dibuat / perlu generate ulang';
        }

        return 'Akan dibuat saat publish';
    }

    public function syncParticipantsFromClassrooms(): int
    {
        $classroomIds = $this->classrooms()->pluck('classrooms.id')->all();
        if (! $classroomIds) {
            return 0;
        }

        $created = 0;
        Student::query()
            ->where('is_active', true)
            ->whereIn('classroom_id', $classroomIds)
            ->chunkById(200, function ($students) use (&$created) {
                foreach ($students as $student) {
                    $participant = $this->participants()->firstOrCreate(
                        ['student_id' => $student->id],
                        ['status' => 'assigned']
                    );

                    if ($participant->wasRecentlyCreated) {
                        $created++;
                    }
                }
            });

        return $created;
    }

    public function hasStartedWork(): bool
    {
        return $this->attempts()->exists()
            || $this->participants()->whereNotIn('status', ['assigned'])->exists();
    }

    public function canEditQuestions(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_READY], true) && ! $this->hasStartedWork();
    }

    public function readinessChecklist(): array
    {
        $this->loadMissing('classrooms');

        $questionsCount = $this->questions()->count();
        $participantsCount = $this->participants()->count();

        return [
            [
                'label' => 'Minimal ada 1 soal',
                'ok' => $questionsCount > 0,
                'note' => $questionsCount.' soal',
            ],
            [
                'label' => 'Kelas peserta sudah dipilih',
                'ok' => $this->classrooms->isNotEmpty(),
                'note' => $this->classrooms->count().' kelas',
            ],
            [
                'label' => 'Peserta sudah tersinkron',
                'ok' => $participantsCount > 0,
                'note' => $participantsCount.' peserta',
            ],
            [
                'label' => 'Durasi ujian valid',
                'ok' => (int) $this->duration_minutes > 0,
                'note' => $this->duration_minutes.' menit',
            ],
            [
                'label' => 'Soal siap untuk aplikasi siswa',
                'ok' => $questionsCount > 0 && (int) $this->package_version > 0,
                'note' => $this->hasGeneratedPackage() ? 'soal sudah siap' : 'disiapkan otomatis saat publish',
            ],
            [
                'label' => 'Jadwal selesai tidak lebih awal dari jadwal mulai',
                'ok' => ! $this->starts_at || ! $this->ends_at || $this->ends_at->greaterThanOrEqualTo($this->starts_at),
                'note' => $this->starts_at && $this->ends_at ? $this->starts_at->format('d M Y H:i').' - '.$this->ends_at->format('d M Y H:i') : 'jadwal fleksibel',
            ],
        ];
    }

    public function isReadyToPublish(): bool
    {
        return collect($this->readinessChecklist())->every(fn ($item) => (bool) $item['ok']);
    }

    public function pruneParticipantsOutsideClassrooms(): int
    {
        $classroomIds = $this->classrooms()->pluck('classrooms.id')->all();

        if (! $classroomIds) {
            return 0;
        }

        $query = $this->participants()
            ->whereDoesntHave('attempts')
            ->whereHas('student', fn ($q) => $q->whereNotIn('classroom_id', $classroomIds));

        $count = (clone $query)->count();
        $query->delete();

        return $count;
    }

    public function operationalStatus(): string
    {
        if ($this->status === self::STATUS_ARCHIVED) {
            return 'Diarsipkan';
        }
        if ($this->status === self::STATUS_CLOSED) {
            return 'Selesai';
        }
        if ($this->status === self::STATUS_DRAFT) {
            return 'Draft';
        }
        if ($this->status === self::STATUS_READY) {
            return 'Siap Publish';
        }
        if ($this->isOpenNow()) {
            return 'Sedang Berlangsung';
        }
        if ($this->starts_at && now()->lessThan($this->starts_at)) {
            return 'Menunggu Jadwal';
        }
        if ($this->ends_at && now()->greaterThan($this->ends_at)) {
            return 'Lewat Jadwal';
        }

        return 'Published';
    }

    public function isOpenNow(): bool
    {
        $now = now();

        return $this->status === self::STATUS_PUBLISHED
            && (! $this->starts_at || $now->greaterThanOrEqualTo($this->starts_at))
            && (! $this->ends_at || $now->lessThanOrEqualTo($this->ends_at));
    }

    public function downloadOpensAt(?int $hoursBefore = null): ?Carbon
    {
        if (! $this->starts_at) {
            return null;
        }

        $hoursBefore ??= (int) SchoolSetting::getValue('package_download_open_hours', 12);

        return $this->starts_at->copy()->subHours(max(0, $hoursBefore));
    }

    public function isPackageDownloadWindowOpen(?int $hoursBefore = null): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED || $this->status === self::STATUS_CLOSED || $this->status === self::STATUS_ARCHIVED) {
            return false;
        }

        $opensAt = $this->downloadOpensAt($hoursBefore);
        $now = now();

        return (! $opensAt || $now->greaterThanOrEqualTo($opensAt))
            && (! $this->ends_at || $now->lessThanOrEqualTo($this->ends_at));
    }
}
