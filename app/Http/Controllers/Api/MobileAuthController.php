<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Services\ExamPackageService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    public function __construct(private readonly ExamPackageService $examPackageService) {}

    public function login(Request $request)
    {
        $data = $request->validate([
            'access_code' => ['required', 'string'],
            'nis' => ['required', 'string', 'max:40'],
            'password' => ['required', 'string'],
            'device_id' => ['required', 'string', 'max:120'],
        ]);

        $exam = Exam::where('access_code', strtoupper($data['access_code']))->first();
        if (! $exam) {
            return response()->json(['message' => 'Kode ujian tidak ditemukan.'], 404);
        }

        if ($exam->status !== Exam::STATUS_PUBLISHED) {
            return response()->json(['message' => 'Ujian belum dipublish atau sudah ditutup.'], 403);
        }

        $student = Student::where('nis', strtoupper($data['nis']))->first();
        if (! $student || ! Hash::check($data['password'], $student->password)) {
            return response()->json(['message' => 'NIS atau password tidak sesuai.'], 422);
        }

        if (! $student->is_active) {
            return response()->json(['message' => 'Akun siswa tidak aktif. Hubungi pengawas.'], 403);
        }

        $participant = $exam->participants()->where('student_id', $student->id)->first();
        if (! $participant && $student->classroom_id && $exam->classrooms()->whereKey($student->classroom_id)->exists()) {
            $participant = $exam->participants()->firstOrCreate(
                ['student_id' => $student->id],
                ['status' => 'assigned']
            );
        }

        if (! $participant) {
            return response()->json(['message' => 'Akun siswa belum terdaftar sebagai peserta ujian ini atau kelas siswa tidak termasuk target ujian.'], 403);
        }

        if ($participant->submitted_at) {
            return response()->json(['message' => 'Peserta ini sudah submit ujian.'], 409);
        }

        $this->ensureParticipantDevice($participant, $data['device_id']);
        if ($participant->status === 'assigned') {
            $participant->forceFill(['status' => 'download_ready'])->save();
        }

        $student->tokens()->where('name', 'mobile-exam-'.$exam->id)->delete();
        $plainToken = $student->createToken('mobile-exam-'.$exam->id, ['exam:'.$exam->id])->plainTextToken;

        $settings = $this->packageSettings();
        $downloadOpensAt = $exam->downloadOpensAt($settings['open_hours']);

        return response()->json([
            'message' => 'Login berhasil.',
            'token_type' => 'Bearer',
            'access_token' => $plainToken,
            'server_time' => now()->toIso8601String(),
            'student' => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'class_name' => $student->classroom?->nama_kelas ?: $student->class_name,
                'classroom_id' => $student->classroom_id,
            ],
            'participant' => [
                'id' => $participant->id,
                'status' => $participant->status,
                'device_id_locked' => (bool) $participant->device_id,
                'download_attempts_count' => (int) $participant->package_download_attempts_count,
                'download_finished_at' => optional($participant->package_download_finished_at)->toIso8601String(),
                'unlock_key_issued_at' => optional($participant->package_unlock_key_issued_at)->toIso8601String(),
            ],
            'exam' => [
                'id' => $exam->id,
                'access_code' => $exam->access_code,
                'title' => $exam->title,
                'description' => $exam->description,
                'subject' => $exam->subject,
                'status' => $exam->status,
                'schedule_mode' => $exam->schedule_mode,
                'starts_at' => optional($exam->starts_at)->toIso8601String(),
                'ends_at' => optional($exam->ends_at)->toIso8601String(),
                'duration_minutes' => $exam->duration_minutes,
                'package_version' => $exam->package_version,
                'lock_mode' => $exam->lock_mode,
                'exit_policy' => $exam->exit_policy,
            ],
            'package' => [
                'ready' => $exam->hasGeneratedPackage(),
                'encrypted' => (bool) $exam->package_is_encrypted,
                'checksum' => $exam->package_checksum,
                'plain_checksum' => $exam->package_plain_checksum,
                'size_bytes' => (int) $exam->package_size_bytes,
                'generated_at' => optional($exam->package_generated_at)->toIso8601String(),
                'download_window_open' => $exam->isPackageDownloadWindowOpen($settings['open_hours']),
                'download_opens_at' => optional($downloadOpensAt)->toIso8601String(),
                'download_open_hours_before_start' => $settings['open_hours'],
                'queue_endpoint' => '/api/mobile/exam-package/queue',
                'download_endpoint' => '/api/mobile/exam-package',
                'download_complete_endpoint' => '/api/mobile/exam-package/download-complete',
                'unlock_endpoint' => '/api/mobile/exam-package/unlock',
                'download_limit' => $settings['concurrent_limit'],
            ],
            'offline_lock' => [
                'lock_mode' => $exam->lock_mode,
                'exit_policy' => $exam->exit_policy,
                'airplane_mode_required' => true,
                'internet_active_locks_exam' => true,
                'app_exit_locks_exam' => true,
                'note' => $exam->lock_mode === Exam::LOCK_STRICT_KIOSK
                    ? 'Mobile akan mencoba mode kiosk/lock-task jika perangkat mendukung, tetap wajib offline saat menjawab.'
                    : 'Mobile memakai Strict Airplane Exam Mode: setelah unlock key siswa wajib mode pesawat/offline. Jika internet aktif atau aplikasi ditinggalkan, mobile mengunci layar ujian dan membunyikan alarm/notifikasi pelanggaran tanpa input pengawas.',
            ],
        ]);
    }

    public function queue(Request $request)
    {
        $data = $request->validate([
            'access_code' => ['required', 'string'],
            'device_id' => ['required', 'string', 'max:120'],
            'needs_package' => ['nullable', 'boolean'],
        ]);

        [$exam, $participant] = $this->resolveAuthenticatedParticipant($request, $data['access_code']);
        $settings = $this->packageSettings();

        if ($exam->status !== Exam::STATUS_PUBLISHED) {
            return response()->json(['message' => 'Ujian belum dipublish atau sudah ditutup.'], 403);
        }

        if (! $exam->hasGeneratedPackage()) {
            return response()->json(['message' => 'Paket soal belum dibuat.'], 409);
        }

        if (! $exam->isPackageDownloadWindowOpen($settings['open_hours'])) {
            $opensAt = $exam->downloadOpensAt($settings['open_hours']);
            $isEnded = $exam->ends_at && now()->greaterThan($exam->ends_at);
            $isEarly = $opensAt && now()->lessThan($opensAt);

            $message = match (true) {
                $isEnded => 'Waktu ujian sudah berakhir. Download paket tidak lagi tersedia.',
                $isEarly => 'Jendela download belum dibuka. Download tersedia mulai '.$opensAt->format('d M Y H:i').'.',
                default => 'Jendela download paket soal belum tersedia. Hubungi pengawas.',
            };

            return response()->json([
                'message' => $message,
                'download_opens_at' => optional($opensAt)->toIso8601String(),
                'ends_at' => optional($exam->ends_at)->toIso8601String(),
            ], 403);
        }

        $needsPackage = (bool) ($data['needs_package'] ?? false);
        $deviceId = $data['device_id'];

        $result = DB::transaction(function () use ($exam, $participant, $settings, $needsPackage, $deviceId) {
            $now = now();
            $lockedExam = Exam::whereKey($exam->id)->lockForUpdate()->firstOrFail();

            ExamParticipant::where('exam_id', $lockedExam->id)
                ->whereNotNull('package_queue_token')
                ->whereNotNull('package_queue_expires_at')
                ->where('package_queue_expires_at', '<', $now)
                ->whereNull('package_download_finished_at')
                ->update([
                    'package_queue_token' => null,
                    'package_queue_started_at' => null,
                    'package_queue_expires_at' => null,
                    'status' => DB::raw("CASE WHEN status = 'downloading' THEN 'download_ready' ELSE status END"),
                ]);

            /** @var ExamParticipant $fresh */
            $fresh = ExamParticipant::whereKey($participant->id)->lockForUpdate()->firstOrFail();
            $this->ensureParticipantDevice($fresh, $deviceId);

            if ((int) $fresh->package_download_attempts_count >= $settings['max_attempts'] && ! $fresh->package_download_finished_at) {
                return [
                    'granted' => false,
                    'blocked' => true,
                    'queue_token' => null,
                    'expires_at' => null,
                    'active' => $this->activeDownloadCount($lockedExam),
                    'limit' => $settings['concurrent_limit'],
                    'position' => null,
                    'total_waiting' => 0,
                    'message' => 'Batas percobaan download paket tercapai. Hubungi pengawas untuk reset attempt/download.',
                ];
            }

            if ($fresh->package_download_finished_at && ! $needsPackage) {
                return [
                    'granted' => true,
                    'already_downloaded' => true,
                    'queue_token' => $fresh->package_queue_token,
                    'expires_at' => optional($fresh->package_queue_expires_at)->toIso8601String(),
                    'active' => $this->activeDownloadCount($lockedExam),
                    'limit' => $settings['concurrent_limit'],
                    'position' => 0,
                ];
            }

            // Aplikasi mobile mungkin kehilangan cache lokal walaupun server pernah mencatat download selesai
            // (contoh: reinstall aplikasi, clear data, atau bug versi lama yang menandai complete tanpa file lokal).
            // Jika mobile mengirim needs_package=true, beri slot download ulang dengan tetap melewati antrean normal.
            if ($fresh->package_download_finished_at && $needsPackage) {
                $fresh->forceFill([
                    'package_download_finished_at' => null,
                    'package_queue_token' => null,
                    'package_queue_started_at' => null,
                    'package_queue_expires_at' => null,
                    'package_download_started_at' => null,
                    'package_queue_joined_at' => $now,
                    'status' => in_array($fresh->status, ['downloaded', 'unlocked'], true) ? 'download_ready' : $fresh->status,
                ])->save();
            }

            if (! $fresh->package_queue_joined_at) {
                $fresh->forceFill(['package_queue_joined_at' => $now])->save();
            }

            if ($fresh->package_queue_token && $fresh->package_queue_expires_at && $fresh->package_queue_expires_at->greaterThan($now)) {
                return [
                    'granted' => true,
                    'queue_token' => $fresh->package_queue_token,
                    'expires_at' => $fresh->package_queue_expires_at->toIso8601String(),
                    'active' => $this->activeDownloadCount($lockedExam),
                    'limit' => $settings['concurrent_limit'],
                    'position' => 0,
                ];
            }

            $active = $this->activeDownloadCount($lockedExam);
            $limit = max(1, $settings['concurrent_limit']);
            $available = max(0, $limit - $active);

            $ahead = ExamParticipant::where('exam_id', $lockedExam->id)
                ->whereNotNull('package_queue_joined_at')
                ->whereNull('package_download_finished_at')
                ->where(function ($query) use ($now) {
                    $query->whereNull('package_queue_token')
                        ->orWhereNull('package_queue_expires_at')
                        ->orWhere('package_queue_expires_at', '<=', $now);
                })
                ->where(function ($query) use ($fresh) {
                    $query->where('package_queue_joined_at', '<', $fresh->package_queue_joined_at)
                        ->orWhere(function ($nested) use ($fresh) {
                            $nested->where('package_queue_joined_at', $fresh->package_queue_joined_at)
                                ->where('id', '<', $fresh->id);
                        });
                })
                ->count();

            if ($available > 0 && $ahead < $available) {
                $token = Str::random(64);
                $fresh->forceFill([
                    'package_queue_started_at' => $now,
                    'package_queue_expires_at' => $now->copy()->addMinutes($settings['slot_ttl_minutes']),
                    'package_queue_token' => $token,
                    'package_download_started_at' => $now,
                    'package_download_attempts_count' => ((int) $fresh->package_download_attempts_count) + 1,
                    'status' => 'downloading',
                ])->save();

                return [
                    'granted' => true,
                    'queue_token' => $token,
                    'expires_at' => $fresh->package_queue_expires_at->toIso8601String(),
                    'active' => $this->activeDownloadCount($lockedExam),
                    'limit' => $limit,
                    'position' => 0,
                ];
            }

            $totalWaiting = ExamParticipant::where('exam_id', $lockedExam->id)
                ->whereNotNull('package_queue_joined_at')
                ->whereNull('package_download_finished_at')
                ->count();

            return [
                'granted' => false,
                'queue_token' => null,
                'expires_at' => null,
                'active' => $active,
                'limit' => $limit,
                'position' => $ahead + 1,
                'total_waiting' => $totalWaiting,
            ];
        });

        $statusCode = ($result['blocked'] ?? false) ? 429 : ($result['granted'] ? 200 : 202);

        return response()->json(array_merge([
            'message' => $result['message'] ?? ($result['granted'] ? 'Slot download tersedia.' : 'Masuk antrean download paket soal.'),
            'access_code' => $exam->access_code,
            'total_participants' => $exam->participants()->count(),
            'download_url' => $result['granted'] ? $this->absolutePackageUrl($exam) : null,
            'download_endpoint' => '/api/mobile/exam-package',
            'package_checksum' => $exam->package_checksum,
            'package_version' => (int) $exam->package_version,
            'encrypted' => (bool) $exam->package_is_encrypted,
        ], $result), $statusCode);
    }

    public function package(Request $request)
    {
        $data = $request->validate([
            'access_code' => ['required', 'string'],
            'queue_token' => ['required', 'string', 'max:120'],
            'device_id' => ['required', 'string', 'max:120'],
            'package_checksum' => ['nullable', 'string', 'max:80'],
        ]);

        [$exam, $participant] = $this->resolveAuthenticatedParticipant($request, $data['access_code']);
        $this->ensureValidDownloadSlot($exam, $participant, $data['queue_token'], $data['device_id']);

        $clientChecksum = trim((string) ($data['package_checksum'] ?? $request->header('If-None-Match', '')), '"');
        if ($clientChecksum !== '' && hash_equals((string) $exam->package_checksum, $clientChecksum)) {
            return response('', 304)->withHeaders($this->packageHeaders($exam));
        }

        $contents = $this->examPackageService->read($exam);
        if ($contents === null) {
            return response()->json([
                'message' => 'File paket soal tidak ditemukan di storage. Generate ulang paket soal dari halaman detail ujian.',
                'package_ready' => false,
            ], 409);
        }

        return response($contents, 200, array_merge([
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$exam->access_code.'-v'.$exam->package_version.'.enc.json"',
            'Cache-Control' => 'private, max-age=86400',
        ], $this->packageHeaders($exam)));
    }

    public function downloadComplete(Request $request)
    {
        $data = $request->validate([
            'access_code' => ['required', 'string'],
            'queue_token' => ['required', 'string', 'max:120'],
            'package_checksum' => ['required', 'string', 'max:80'],
            'device_id' => ['required', 'string', 'max:120'],
        ]);

        [$exam, $participant] = $this->resolveAuthenticatedParticipant($request, $data['access_code']);
        $this->ensureValidDownloadSlot($exam, $participant, $data['queue_token'], $data['device_id']);

        if (! hash_equals((string) $exam->package_checksum, (string) $data['package_checksum'])) {
            return response()->json(['message' => 'Checksum file paket tidak sesuai. Download ulang paket soal.'], 409);
        }

        Exam::whereKey($exam->id)->increment('package_downloads_count');
        $meta = $participant->meta ?: [];
        $meta['package_downloaded_at'] = now()->toIso8601String();
        $meta['package_checksum'] = $exam->package_checksum;
        $meta['package_plain_checksum'] = $exam->package_plain_checksum;
        $meta['package_version'] = (int) $exam->package_version;

        $participant->forceFill([
            'package_download_finished_at' => now(),
            'status' => in_array($participant->status, ['assigned', 'download_ready', 'downloading'], true) ? 'downloaded' : $participant->status,
            'meta' => $meta,
        ])->save();

        return response()->json([
            'message' => 'Download paket soal tercatat.',
            'package_checksum' => $exam->package_checksum,
            'download_finished_at' => $participant->package_download_finished_at->toIso8601String(),
        ]);
    }

    public function unlock(Request $request)
    {
        $data = $request->validate([
            'access_code' => ['required', 'string'],
            'device_id' => ['required', 'string', 'max:120'],
            'package_checksum' => ['required', 'string', 'max:80'],
        ]);

        [$exam, $participant] = $this->resolveAuthenticatedParticipant($request, $data['access_code']);

        if (! $exam->isOpenNow()) {
            $message = $exam->isManualMode()
                ? 'Ujian belum dibuka oleh pengawas. Tunggu pengawas membuka ujian, lalu coba lagi.'
                : 'Unlock key baru diberikan saat jadwal ujian dibuka.';

            return response()->json([
                'message' => $message,
                'server_time' => now()->toIso8601String(),
                'starts_at' => optional($exam->starts_at)->toIso8601String(),
                'ends_at' => optional($exam->ends_at)->toIso8601String(),
            ], 403);
        }

        if (! hash_equals((string) $exam->package_checksum, (string) $data['package_checksum'])) {
            return response()->json(['message' => 'Paket soal di perangkat tidak sesuai dengan paket aktif server.'], 409);
        }

        $this->ensureParticipantDevice($participant, $data['device_id']);

        $participant->forceFill([
            'package_unlock_key_issued_at' => now(),
            'status' => in_array($participant->status, ['assigned', 'download_ready', 'downloading', 'downloaded'], true) ? 'unlocked' : $participant->status,
        ])->save();

        return response()->json([
            'message' => 'Unlock key tersedia. Aplikasi boleh membuka paket soal lokal.',
            'unlock_key_hex' => $this->examPackageService->unlockKey($exam),
            'cipher' => $exam->package_cipher ?: 'AES-256-GCM',
            'package_checksum' => $exam->package_checksum,
            'plain_checksum' => $exam->package_plain_checksum,
            'offline_lock' => $exam->offlineLockPayload(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function resolveAuthenticatedParticipant(Request $request, string $accessCode): array
    {
        /** @var Student|null $student */
        $student = $request->user();
        abort_unless($student instanceof Student, 401, 'Token siswa tidak valid.');

        $exam = Exam::where('access_code', strtoupper($accessCode))->firstOrFail();
        abort_unless($student->tokenCan('exam:'.$exam->id), 403, 'Token siswa tidak berlaku untuk ujian ini.');

        $participant = ExamParticipant::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        return [$exam, $participant, $student];
    }

    private function ensureValidDownloadSlot(Exam $exam, ExamParticipant $participant, string $queueToken, string $deviceId): void
    {
        $this->ensureParticipantDevice($participant, $deviceId);
        abort_unless($exam->status === Exam::STATUS_PUBLISHED, 403, 'Ujian belum dipublish atau sudah ditutup.');
        abort_unless($exam->hasGeneratedPackage(), 409, 'Paket soal belum dibuat.');
        abort_if(! $participant->package_queue_token || ! hash_equals($participant->package_queue_token, $queueToken), 429, 'Slot download tidak valid. Minta antrean download ulang.');
        abort_if(! $participant->package_queue_expires_at || now()->greaterThan($participant->package_queue_expires_at), 429, 'Slot download sudah kedaluwarsa. Minta antrean download ulang.');
    }

    private function ensureParticipantDevice(ExamParticipant $participant, string $deviceId): void
    {
        $deviceId = trim($deviceId);
        abort_if($deviceId === '', 422, 'ID perangkat wajib dikirim aplikasi.');

        // 1) Akun ini sudah terkunci di HP lain
        abort_if(
            $participant->device_id && ! hash_equals((string) $participant->device_id, $deviceId),
            423,
            'Akun ini sudah terkunci di perangkat lain. Hubungi pengawas untuk reset perangkat.'
        );

        // 2) HP ini sudah dipakai siswa (NIS) lain untuk ujian yang sama
        $usedByOther = ExamParticipant::where('exam_id', $participant->exam_id)
            ->where('device_id', $deviceId)
            ->where('id', '!=', $participant->id)
            ->with('student:id,nis,name')
            ->first();

        abort_if(
            $usedByOther !== null,
            423,
            'HP ini sudah dipakai untuk login siswa lain (NIS '.($usedByOther->student?->nis ?? '-').') di ujian ini. Satu HP hanya bisa untuk satu siswa per ujian. Hubungi pengawas jika perlu reset perangkat.'
        );

        if (! $participant->device_id) {
            try {
                $participant->forceFill(['device_id' => $deviceId])->save();
            } catch (QueryException) {
                abort(423, 'HP ini sudah dipakai siswa lain di ujian ini. Satu HP hanya bisa untuk satu siswa per ujian. Hubungi pengawas jika perlu reset perangkat.');
            }
            $participant->refresh();
        }
    }

    private function activeDownloadCount(Exam $exam): int
    {
        return ExamParticipant::where('exam_id', $exam->id)
            ->whereNotNull('package_queue_token')
            ->whereNotNull('package_queue_expires_at')
            ->where('package_queue_expires_at', '>', now())
            ->whereNull('package_download_finished_at')
            ->count();
    }

    private function packageSettings(): array
    {
        return [
            'open_hours' => max(0, (int) SchoolSetting::getValue('package_download_open_hours', 12)),
            'concurrent_limit' => max(1, (int) SchoolSetting::getValue('package_download_concurrent_limit', 50)),
            'slot_ttl_minutes' => max(1, (int) SchoolSetting::getValue('package_queue_slot_ttl_minutes', 10)),
            'max_attempts' => max(1, (int) SchoolSetting::getValue('package_download_max_attempts', 3)),
        ];
    }

    private function absolutePackageUrl(Exam $exam): ?string
    {
        $url = $this->examPackageService->publicUrl($exam);
        if (! $url) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim(config('app.url'), '/').'/'.ltrim($url, '/');
    }

    private function packageHeaders(Exam $exam): array
    {
        return [
            'ETag' => '"'.$exam->package_checksum.'"',
            'X-Exam-Package-Version' => (string) $exam->package_version,
            'X-Exam-Package-Checksum' => (string) $exam->package_checksum,
            'X-Exam-Package-Plain-Checksum' => (string) $exam->package_plain_checksum,
            'X-Exam-Package-Encrypted' => $exam->package_is_encrypted ? '1' : '0',
            'X-Exam-Starts-At' => optional($exam->starts_at)->toIso8601String() ?: '',
            'X-Exam-Ends-At' => optional($exam->ends_at)->toIso8601String() ?: '',
        ];
    }
}
