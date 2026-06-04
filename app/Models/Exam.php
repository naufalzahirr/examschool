<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        self::LOCK_STRICT_KIOSK => 'Ketat / Kiosk: coba kunci aplikasi',
    ];

    public const EXIT_AFTER_SUBMIT = 'after_submit';

    public const EXIT_AFTER_TIME_END = 'after_time_end';

    public const EXIT_PROCTOR_CODE = 'proctor_code';

    public const EXIT_POLICIES = [
        self::EXIT_AFTER_SUBMIT => 'Boleh keluar setelah submit',
        self::EXIT_AFTER_TIME_END => 'Boleh keluar setelah waktu habis',
        self::EXIT_PROCTOR_CODE => 'Harus kode pengawas sampai submit/waktu habis',
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
                $exam->exit_policy = (string) SchoolSetting::getValue('default_exam_exit_policy', self::EXIT_PROCTOR_CODE);
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
        return self::EXIT_POLICIES[$this->exit_policy] ?? $this->exit_policy ?? 'Kode pengawas';
    }

    public function ensureOfflineExitCode(bool $force = false): ?string
    {
        if (! $force && $this->offline_exit_code_hash && $this->offline_exit_code_salt && $this->offline_exit_code_encrypted) {
            return $this->offlineExitCodePlain();
        }

        $code = self::generateOfflineExitCode((int) SchoolSetting::getValue('offline_exit_code_length', 8));
        $salt = Str::random(24);

        $this->forceFill([
            'offline_exit_code_salt' => $salt,
            'offline_exit_code_hash' => self::offlineExitCodeHash($code, $salt, $this->access_code),
            'offline_exit_code_encrypted' => Crypt::encryptString($code),
            'offline_exit_code_generated_at' => now(),
        ])->save();

        return $code;
    }

    public function offlineExitCodePlain(): ?string
    {
        if (! $this->offline_exit_code_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->offline_exit_code_encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function generateOfflineExitCode(int $length = 8): string
    {
        $length = max(6, min(16, $length));
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return collect(range(1, $length))
            ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
            ->implode('');
    }

    public static function offlineExitCodeHash(string $code, string $salt, string $accessCode): string
    {
        return hash('sha256', strtoupper(trim($code)).'|'.$salt.'|'.strtoupper(trim($accessCode)));
    }

    public function offlineLockPayload(): array
    {
        return [
            'lock_mode' => $this->lock_mode ?: self::LOCK_STANDARD,
            'exit_policy' => $this->exit_policy ?: self::EXIT_PROCTOR_CODE,
            'offline_exit_code_required' => ($this->exit_policy ?: self::EXIT_PROCTOR_CODE) === self::EXIT_PROCTOR_CODE,
            'offline_exit_code_salt' => $this->offline_exit_code_salt,
            'offline_exit_code_hash' => $this->offline_exit_code_hash,
            'offline_exit_code_algorithm' => 'SHA256(UPPERCASE_CODE|salt|access_code)',
            'exit_code_generated_at' => optional($this->offline_exit_code_generated_at)->toIso8601String(),
            'airplane_mode_required' => true,
            'internet_active_locks_exam' => true,
            'app_exit_locks_exam' => true,
            'reopen_requires_offline' => true,
            'reopen_requires_proctor_code' => true,
            'timer_continues_when_locked' => true,
            'strict_android_note' => 'Mode utama: siswa wajib mode pesawat/offline setelah unlock key. Android BYOD tidak bisa dijamin 100% anti tombol Home tanpa device owner/kiosk, tetapi mobile akan fullscreen, menolak lanjut saat online, mengunci saat keluar aplikasi, dan mewajibkan kode pengawas.',
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
                'label' => 'Paket soal terenkripsi akan bisa dibuat',
                'ok' => $questionsCount > 0 && (int) $this->package_version > 0,
                'note' => $this->hasGeneratedPackage() ? 'paket tersedia' : 'dibuat otomatis saat publish',
            ],
            [
                'label' => 'Kode keluar offline pengawas tersedia',
                'ok' => $this->exit_policy !== self::EXIT_PROCTOR_CODE || (bool) $this->offline_exit_code_hash,
                'note' => $this->exit_policy === self::EXIT_PROCTOR_CODE ? ($this->offline_exit_code_hash ? 'kode siap' : 'akan dibuat otomatis saat publish') : 'tidak wajib',
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
