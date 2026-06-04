<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\Question;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolSetting;

class AttemptController extends Controller
{
    public function start(Request $request)
    {
        $data = $request->validate([
            'access_code' => ['required', 'string'],
            'client_attempt_id' => ['required', 'string', 'max:80'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'started_at' => ['nullable', 'date'],
            'cached_payload_hash' => ['nullable', 'string', 'max:100'],
        ]);

        [$exam, $participant] = $this->resolve($request, $data['access_code']);
        $this->ensureCanContinue($exam, $participant, $data['device_id'] ?? null);
        $this->ensurePackageHashMatches($exam, $data['cached_payload_hash'] ?? null);

        $attempt = ExamAttempt::firstOrCreate(
            [
                'participant_id' => $participant->id,
                'client_attempt_id' => $data['client_attempt_id'],
            ],
            [
                'exam_id' => $exam->id,
                'device_id' => $data['device_id'] ?? null,
                'started_at' => $data['started_at'] ?? now(),
                'status' => 'started',
                'cached_payload_hash' => $data['cached_payload_hash'] ?? null,
            ]
        );

        $participant->forceFill(['status' => 'in_progress'])->save();

        return response()->json([
            'message' => 'Attempt aktif.',
            'attempt_id' => $attempt->id,
            'status' => $attempt->status,
        ]);
    }

    public function sync(Request $request)
    {
        $data = $request->validate([
            'access_code' => ['required', 'string'],
            'client_attempt_id' => ['required', 'string', 'max:80'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'answers' => ['required', 'array'],
            'cached_payload_hash' => ['nullable', 'string'],
        ]);

        [$exam, $participant] = $this->resolve($request, $data['access_code']);
        $this->ensureCanContinue($exam, $participant, $data['device_id'] ?? null, allowSubmitted: true);
        $this->ensurePackageHashMatches($exam, $data['cached_payload_hash'] ?? null);

        $attempt = ExamAttempt::firstOrCreate(
            [
                'participant_id' => $participant->id,
                'client_attempt_id' => $data['client_attempt_id'],
            ],
            [
                'exam_id' => $exam->id,
                'device_id' => $data['device_id'] ?? null,
                'started_at' => now(),
            ]
        );

        if ($attempt->status === 'submitted') {
            return response()->json(['message' => 'Ujian sudah disubmit, progress tidak diubah.'], 409);
        }

        $attempt->update([
            'answers_snapshot' => $data['answers'],
            'cached_payload_hash' => $data['cached_payload_hash'] ?? null,
            'last_synced_at' => now(),
            'status' => 'synced',
        ]);

        return response()->json([
            'message' => 'Progress tersinkron.',
            'last_synced_at' => $attempt->last_synced_at->toIso8601String(),
        ]);
    }


    public function integrityEvent(Request $request)
    {
        $data = $request->validate([
            'access_code' => ['required', 'string'],
            'client_attempt_id' => ['required', 'string', 'max:80'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'event_type' => ['required', 'string', 'max:80'],
            'reason' => ['nullable', 'string', 'max:120'],
            'at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);

        [$exam, $participant] = $this->resolve($request, $data['access_code']);
        $this->ensureCanContinue($exam, $participant, $data['device_id'] ?? null, allowSubmitted: true, allowUploadGrace: true);

        $event = [
            'type' => $data['event_type'],
            'reason' => $data['reason'] ?? null,
            'at' => $data['at'] ?? now()->toIso8601String(),
            'meta' => $data['meta'] ?? [],
            'received_at' => now()->toIso8601String(),
        ];

        DB::transaction(function () use ($exam, $participant, $data, $event) {
            $attempt = ExamAttempt::firstOrCreate(
                [
                    'participant_id' => $participant->id,
                    'client_attempt_id' => $data['client_attempt_id'],
                ],
                [
                    'exam_id' => $exam->id,
                    'device_id' => $data['device_id'] ?? null,
                    'started_at' => now(),
                    'status' => 'started',
                ]
            );

            $meta = $attempt->meta ?: [];
            $events = $meta['integrity_events'] ?? [];
            $events[] = $event;
            $meta['integrity_events'] = array_slice($events, -100);
            $meta['integrity_summary'] = $this->summarizeIntegrityEvents($meta['integrity_events']);

            $attempt->update([
                'last_synced_at' => now(),
                'status' => $attempt->status === 'submitted' ? 'submitted' : 'locked',
                'meta' => $meta,
            ]);

            $participantMeta = $participant->meta ?: [];
            $participantEvents = $participantMeta['integrity_events'] ?? [];
            $participantEvents[] = $event;
            $participantMeta['integrity_events'] = array_slice($participantEvents, -100);
            $participantMeta['integrity_summary'] = $this->summarizeIntegrityEvents($participantMeta['integrity_events']);
            $participantMeta['last_integrity_event'] = $event;

            $participant->update([
                'status' => $participant->submitted_at ? 'submitted' : 'locked',
                'meta' => $participantMeta,
            ]);
        });

        return response()->json([
            'message' => 'Event integritas ujian tercatat.',
            'status' => $participant->fresh()->status,
        ]);
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'access_code' => ['required', 'string'],
            'client_attempt_id' => ['required', 'string', 'max:80'],
            'device_id' => ['nullable', 'string', 'max:120'],
            'answers' => ['required', 'array'],
            'cached_payload_hash' => ['nullable', 'string'],
            'submitted_at' => ['nullable', 'date'],
            'local_finished_at' => ['nullable', 'date'],
            'submission_checksum' => ['nullable', 'string', 'max:128'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
            'exit_events' => ['nullable', 'array'],
        ]);

        [$exam, $participant] = $this->resolve($request, $data['access_code']);
        $this->ensureCanContinue($exam, $participant, $data['device_id'] ?? null, allowUploadGrace: true);
        $this->ensurePackageHashMatches($exam, $data['cached_payload_hash'] ?? null);

        $incomingChecksum = $data['submission_checksum'] ?? hash('sha256', json_encode($data['answers'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $incomingIdempotency = $data['idempotency_key'] ?? ($data['client_attempt_id'] . ':' . $incomingChecksum);

        if ($participant->submitted_at) {
            $existingAttempt = $participant->attempts()
                ->where('client_attempt_id', $data['client_attempt_id'])
                ->latest()
                ->first();

            if ($existingAttempt && (
                hash_equals((string) $existingAttempt->submission_checksum, (string) $incomingChecksum)
                || hash_equals((string) $existingAttempt->idempotency_key, (string) $incomingIdempotency)
            )) {
                return response()->json([
                    'message' => 'Jawaban sudah diterima sebelumnya. Tidak disimpan ulang.',
                    'attempt_id' => $existingAttempt->id,
                    'score' => (float) $participant->score,
                    'submitted_at' => optional($participant->submitted_at)->toIso8601String(),
                    'idempotent' => true,
                ]);
            }

            return response()->json([
                'message' => 'Ujian sudah pernah disubmit.',
                'score' => (float) $participant->score,
            ], 409);
        }

        [$attempt, $score] = DB::transaction(function () use ($exam, $participant, $data, $incomingChecksum, $incomingIdempotency) {
            $attempt = ExamAttempt::firstOrCreate(
                [
                    'participant_id' => $participant->id,
                    'client_attempt_id' => $data['client_attempt_id'],
                ],
                [
                    'exam_id' => $exam->id,
                    'device_id' => $data['device_id'] ?? null,
                    'started_at' => now(),
                ]
            );

            $score = $this->grade($exam, $attempt, $data['answers']);

            $attempt->update([
                'answers_snapshot' => $data['answers'],
                'cached_payload_hash' => $data['cached_payload_hash'] ?? null,
                'last_synced_at' => now(),
                'local_finished_at' => $data['local_finished_at'] ?? $data['submitted_at'] ?? now(),
                'upload_received_at' => now(),
                'submitted_at' => $data['submitted_at'] ?? now(),
                'submission_checksum' => $incomingChecksum,
                'idempotency_key' => $incomingIdempotency,
                'status' => 'submitted',
                'score' => $score,
                'meta' => array_merge($attempt->meta ?: [], [
                    'exit_events' => $data['exit_events'] ?? [],
                    'offline_submission' => true,
                    'upload_received_at' => now()->toIso8601String(),
                ]),
            ]);

            $participantMeta = $participant->meta ?: [];
            $submittedEvents = $data['exit_events'] ?? [];
            if ($submittedEvents) {
                $participantMeta['submitted_exit_events'] = $submittedEvents;
                $participantMeta['integrity_summary'] = $this->summarizeIntegrityEvents(array_merge($participantMeta['integrity_events'] ?? [], $submittedEvents));
                $participantMeta['last_integrity_event'] = collect($submittedEvents)->last();
            }

            $participant->update([
                'status' => 'submitted',
                'submitted_at' => $attempt->submitted_at,
                'score' => $score,
                'meta' => $participantMeta,
            ]);

            return [$attempt, $score];
        });

        return response()->json([
            'message' => 'Jawaban berhasil dikirim.',
            'attempt_id' => $attempt->id,
            'score' => (float) $score,
            'submitted_at' => optional($attempt->submitted_at)->toIso8601String(),
            'upload_received_at' => optional($attempt->upload_received_at)->toIso8601String(),
            'submission_checksum' => $attempt->submission_checksum,
        ]);
    }


    private function summarizeIntegrityEvents(array $events): array
    {
        $summary = [
            'total' => count($events),
            'locked' => 0,
            'app_left' => 0,
            'internet_active' => 0,
            'unlock_failed' => 0,
            'unlocked_by_proctor' => 0,
            'last_reason' => null,
            'last_at' => null,
        ];

        foreach ($events as $event) {
            $type = (string) ($event['type'] ?? '');
            $reason = (string) ($event['reason'] ?? ($event['meta']['reason'] ?? ''));
            if (str_contains($type, 'locked') || str_contains($reason, 'locked')) {
                $summary['locked']++;
            }
            if (str_contains($type, 'app_') || str_contains($reason, 'app_left') || str_contains($reason, 'exit_button')) {
                $summary['app_left']++;
            }
            if (str_contains($reason, 'internet') || str_contains($type, 'internet')) {
                $summary['internet_active']++;
            }
            if (str_contains($type, 'failed')) {
                $summary['unlock_failed']++;
            }
            if (str_contains($type, 'unlocked_by_proctor')) {
                $summary['unlocked_by_proctor']++;
            }
            $summary['last_reason'] = $reason ?: $type;
            $summary['last_at'] = $event['at'] ?? $event['received_at'] ?? $summary['last_at'];
        }

        return $summary;
    }

    private function resolve(Request $request, string $accessCode): array
    {
        /** @var Student|null $student */
        $student = $request->user();
        abort_unless($student instanceof Student, 401, 'Token siswa tidak valid.');

        $exam = Exam::where('access_code', strtoupper($accessCode))->firstOrFail();
        abort_unless($student->tokenCan('exam:' . $exam->id), 403, 'Token siswa tidak berlaku untuk ujian ini.');

        $participant = ExamParticipant::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        return [$exam, $participant];
    }

    private function ensureCanContinue(Exam $exam, ExamParticipant $participant, ?string $deviceId = null, bool $allowSubmitted = false, bool $allowUploadGrace = false): void
    {
        if ($allowUploadGrace) {
            $uploadGraceMinutes = max(0, (int) SchoolSetting::getValue('upload_grace_minutes', 10));
            $now = now();
            $withinUploadWindow = $exam->status === Exam::STATUS_PUBLISHED
                && (!$exam->starts_at || $now->greaterThanOrEqualTo($exam->starts_at))
                && (!$exam->ends_at || $now->lessThanOrEqualTo($exam->ends_at->copy()->addMinutes($uploadGraceMinutes)));

            abort_unless($withinUploadWindow, 403, 'Waktu upload jawaban sudah ditutup. Hubungi pengawas.');
        } else {
            abort_unless($exam->isOpenNow(), 403, 'Ujian belum dibuka atau sudah ditutup.');
        }

        if (!$allowSubmitted) {
            abort_if($participant->submitted_at, 409, 'Ujian sudah pernah disubmit.');
        }

        if ($participant->device_id && $deviceId && $participant->device_id !== $deviceId) {
            abort(423, 'Akun ini sudah terkunci di perangkat lain.');
        }
    }


    private function ensurePackageHashMatches(Exam $exam, ?string $cachedPayloadHash): void
    {
        if ($cachedPayloadHash && $exam->package_checksum && !hash_equals((string) $exam->package_checksum, (string) $cachedPayloadHash)) {
            abort(409, 'Paket soal di perangkat tidak sesuai dengan paket aktif di server. Siswa perlu download ulang paket soal.');
        }
    }

    private function grade(Exam $exam, ExamAttempt $attempt, array $answers): float
    {
        $questions = $exam->questions()->with('options')->get()->keyBy('id');
        $total = 0.0;

        foreach ($answers as $answer) {
            $questionId = $answer['question_id'] ?? null;
            if (!$questionId || !$questions->has($questionId)) {
                continue;
            }

            /** @var Question $question */
            $question = $questions[$questionId];
            $value = $answer['value'] ?? null;
            $isCorrect = null;
            $score = 0.0;

            if ($question->type === Question::TYPE_MULTIPLE_CHOICE) {
                [$isCorrect, $score] = $this->gradeSingleChoice($question, $value);
            } elseif ($question->type === Question::TYPE_MULTIPLE_CHOICE_COMPLEX) {
                [$isCorrect, $score] = $this->gradeComplexChoice($question, $value);
            } elseif ($question->type === Question::TYPE_TRUE_FALSE) {
                [$isCorrect, $score] = $this->gradeTrueFalse($question, $value);
            } elseif ($question->type === Question::TYPE_MATCHING) {
                [$isCorrect, $score] = $this->gradeMatching($question, $value);
            } elseif ($question->type === Question::TYPE_SHORT_ANSWER) {
                [$isCorrect, $score] = $this->gradeShortAnswer($question, $value);
            }

            $attempt->answers()->updateOrCreate(
                ['question_id' => $question->id],
                [
                    'value' => is_array($value) ? $value : ['text' => $value],
                    'is_correct' => $isCorrect,
                    'score' => $score,
                    'answered_at' => $answer['answered_at'] ?? now(),
                ]
            );

            $total += $score;
        }

        return $total;
    }

    private function gradeSingleChoice(Question $question, mixed $value): array
    {
        $correctIds = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
        $given = is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
        $isCorrect = count($correctIds) > 0 && in_array($given, $correctIds, true);

        return [$isCorrect, $isCorrect ? (float) $question->points : 0.0];
    }

    private function gradeComplexChoice(Question $question, mixed $value): array
    {
        $correctIds = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (string) $id)->sort()->values()->all();
        $given = collect(is_array($value) ? $value : [])->map(fn ($id) => (string) $id)->sort()->values()->all();
        $isCorrect = $correctIds === $given;

        return [$isCorrect, $isCorrect ? (float) $question->points : 0.0];
    }

    private function gradeTrueFalse(Question $question, mixed $value): array
    {
        $answer = (bool) ($question->answer_key['answer'] ?? true);
        $given = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $isCorrect = $given !== null && $given === $answer;

        return [$isCorrect, $isCorrect ? (float) $question->points : 0.0];
    }

    private function gradeMatching(Question $question, mixed $value): array
    {
        $given = is_array($value) ? $value : [];
        $correct = [];
        foreach ($question->options as $option) {
            $correct[(string) $option->id] = trim((string) ($option->meta['match'] ?? ''));
        }

        $normalizedGiven = [];
        foreach ($given as $key => $item) {
            $normalizedGiven[(string) $key] = trim((string) $item);
        }

        ksort($correct);
        ksort($normalizedGiven);
        $isCorrect = $correct === $normalizedGiven;

        return [$isCorrect, $isCorrect ? (float) $question->points : 0.0];
    }

    private function gradeShortAnswer(Question $question, mixed $value): array
    {
        $accepted = collect($question->answer_key['accepted'] ?? [$question->correct_text])
            ->filter()
            ->map(fn ($v) => $this->normalizeText((string) $v))
            ->values()
            ->all();
        $given = $this->normalizeText(is_array($value) ? (string) ($value['text'] ?? '') : (string) $value);
        $isCorrect = $given !== '' && in_array($given, $accepted, true);

        return [$isCorrect, $isCorrect ? (float) $question->points : 0.0];
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?: '';

        return $value;
    }
}
