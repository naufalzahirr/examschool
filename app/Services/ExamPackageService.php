<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ExamPackageService
{
    public function generate(Exam $exam): array
    {
        $exam->load(['questions.options', 'classrooms']);

        $plainPayload = $this->buildPayload($exam);
        $encodedPlain = json_encode($plainPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $plainChecksum = hash('sha256', $encodedPlain ?: '');

        $unlockKeyHex = bin2hex(random_bytes(32));
        $encryptedEnvelope = $this->encryptPayload($plainPayload, $unlockKeyHex, $exam, $plainChecksum);

        $json = json_encode($encryptedEnvelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Gagal encode paket soal terenkripsi.');
        }

        $checksum = hash('sha256', $json);
        $encryptedEnvelope['package']['checksum'] = $checksum;
        $encryptedEnvelope['package']['generated_at'] = now()->toIso8601String();
        $encryptedEnvelope['package']['public_url'] = null;

        $json = json_encode($encryptedEnvelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Gagal encode paket soal terenkripsi final.');
        }

        $path = $this->pathFor($exam, $checksum);
        $disk = (string) config('filesystems.exam_package_disk', 'local');
        Storage::disk($disk)->put($path, $json);

        $publicUrl = $disk === 'public' ? Storage::disk($disk)->url($path) : null;
        $size = strlen($json);

        $exam->forceFill([
            'package_disk' => $disk,
            'package_path' => $path,
            'package_public_url' => $publicUrl,
            'package_is_encrypted' => true,
            'package_cipher' => 'AES-256-GCM',
            'package_unlock_key_encrypted' => Crypt::encryptString($unlockKeyHex),
            'package_checksum' => $checksum,
            'package_plain_checksum' => $plainChecksum,
            'package_generated_at' => now(),
            'package_size_bytes' => $size,
        ])->save();

        return [
            'path' => $path,
            'disk' => $disk,
            'public_url' => $publicUrl,
            'checksum' => $checksum,
            'plain_checksum' => $plainChecksum,
            'size_bytes' => $size,
            'questions_count' => $exam->questions->count(),
            'encrypted' => true,
        ];
    }

    public function read(Exam $exam): ?string
    {
        $disk = $exam->package_disk ?: 'local';
        if (! $exam->package_path || ! Storage::disk($disk)->exists($exam->package_path)) {
            return null;
        }

        return Storage::disk($disk)->get($exam->package_path);
    }

    public function exists(Exam $exam): bool
    {
        $disk = $exam->package_disk ?: 'local';

        return (bool) $exam->package_path && Storage::disk($disk)->exists($exam->package_path);
    }

    public function publicUrl(Exam $exam): ?string
    {
        if (! $this->exists($exam)) {
            return null;
        }

        $disk = $exam->package_disk ?: 'local';
        if ($disk !== 'public') {
            return null;
        }

        return $exam->package_public_url ?: Storage::disk($disk)->url($exam->package_path);
    }

    public function unlockKey(Exam $exam): string
    {
        if (! $exam->package_unlock_key_encrypted) {
            throw new RuntimeException('Unlock key paket soal belum tersedia. Generate ulang paket soal.');
        }

        return Crypt::decryptString($exam->package_unlock_key_encrypted);
    }

    public function pathFor(Exam $exam, ?string $checksum = null): string
    {
        $safeCode = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($exam->access_code));
        $hash = $checksum ? substr($checksum, 0, 16) : Str::lower(Str::random(16));

        return 'exam-packages/'.$safeCode.'/v'.(int) $exam->package_version.'/'.$safeCode.'-v'.(int) $exam->package_version.'-'.$hash.'.enc.json';
    }

    private function encryptPayload(array $plainPayload, string $unlockKeyHex, Exam $exam, string $plainChecksum): array
    {
        $key = hex2bin($unlockKeyHex);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('Panjang unlock key tidak valid.');
        }

        $iv = random_bytes(12);
        $tag = '';
        $plainJson = json_encode($plainPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($plainJson === false) {
            throw new RuntimeException('Gagal encode plaintext paket soal.');
        }

        $aad = $exam->access_code.'|v'.(int) $exam->package_version.'|'.$plainChecksum;
        $cipherText = openssl_encrypt($plainJson, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
        if ($cipherText === false) {
            throw new RuntimeException('Gagal mengenkripsi paket soal.');
        }

        return [
            'schema' => 'school-exam-encrypted-package-v3',
            'package' => [
                'version' => (int) $exam->package_version,
                'checksum' => null,
                'plain_checksum' => $plainChecksum,
                'generated_at' => null,
                'content_type' => 'application/json',
                'encrypted' => true,
                'cipher' => 'AES-256-GCM',
                'key_format' => 'hex-32-byte',
                'iv' => base64_encode($iv),
                'tag' => base64_encode($tag),
                'aad' => $aad,
                'public_url' => null,
                'note' => 'Paket ini terenkripsi dan tidak berisi kunci jawaban. Aplikasi membuka paket memakai unlock key dari server saat waktu ujian dimulai.',
            ],
            'server' => [
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
            ],
            'exam_preview' => [
                'access_code' => $exam->access_code,
                'title' => $exam->title,
                'subject' => $exam->subject,
                'starts_at' => optional($exam->starts_at)->toIso8601String(),
                'ends_at' => optional($exam->ends_at)->toIso8601String(),
                'duration_minutes' => (int) $exam->duration_minutes,
            ],
            'encrypted_payload' => base64_encode($cipherText),
        ];
    }

    private function buildPayload(Exam $exam): array
    {
        return [
            'schema' => 'school-exam-package-v2',
            'package' => [
                'version' => (int) $exam->package_version,
                'plain_checksum' => null,
                'generated_at' => now()->toIso8601String(),
                'content_type' => 'application/json',
                'answer_key_included' => false,
                'note' => 'Paket plaintext hasil dekripsi tidak berisi kunci jawaban. Kunci jawaban tetap hanya di server.',
            ],
            'server' => [
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
            ],
            'exam' => [
                'id' => $exam->id,
                'access_code' => $exam->access_code,
                'title' => $exam->title,
                'description' => $exam->description,
                'subject' => $exam->subject,
                'grade_level' => $exam->grade_level,
                'status' => $exam->status,
                'starts_at' => optional($exam->starts_at)->toIso8601String(),
                'ends_at' => optional($exam->ends_at)->toIso8601String(),
                'duration_minutes' => (int) $exam->duration_minutes,
            ],
            'rules' => [
                'shuffle_questions' => (bool) $exam->shuffle_questions,
                'shuffle_options' => (bool) $exam->shuffle_options,
                'randomization' => 'Aplikasi mobile boleh mengacak lokal memakai seed participant_id + checksum paket. ID soal/opsi tetap dikirim untuk penilaian.',
                'download_allowed_after_publish' => true,
                'unlock_key_required_at_start' => true,
                'answer_key_included' => false,
                'offline_mode' => true,
                'local_progress_is_source_of_truth' => true,
                'offline_lock' => $exam->offlineLockPayload(),
            ],
            'classrooms' => $exam->classrooms->map(fn ($classroom) => [
                'id' => $classroom->id,
                'nama_kelas' => $classroom->nama_kelas,
                'tingkat' => $classroom->tingkat,
            ])->values()->all(),
            'questions' => $exam->questions->map(fn (Question $question) => $this->questionPayload($question, $exam))->values()->all(),
        ];
    }

    private function questionPayload(Question $question, Exam $exam): array
    {
        $options = $question->options->map(fn ($option) => [
            'id' => $option->id,
            'label' => $option->label,
            'order_no' => $option->order_no,
        ])->values();

        $payload = [
            'id' => $question->id,
            'question_code' => $question->question_code,
            'type' => $question->type,
            'title' => $question->title,
            'description' => $question->description,
            'required' => (bool) $question->required,
            'points' => (float) $question->points,
            'order_no' => (int) $question->order_no,
            'options' => $options->all(),
        ];

        if ($question->type === Question::TYPE_TRUE_FALSE) {
            $payload['options'] = [
                ['id' => 'true', 'label' => 'Benar', 'order_no' => 1],
                ['id' => 'false', 'label' => 'Salah', 'order_no' => 2],
            ];
        }

        if ($question->type === Question::TYPE_MATCHING) {
            $bank = $question->options
                ->map(fn ($option) => ['value' => (string) ($option->meta['match'] ?? '')])
                ->filter(fn ($item) => $item['value'] !== '')
                ->values();

            $payload['matching_bank'] = $this->deterministicShuffle($bank, 'matching:'.$exam->id.':'.$question->id.':'.$exam->package_version)->all();
        }

        return $payload;
    }

    private function deterministicShuffle(Collection $items, string $seed): Collection
    {
        return $items->sortBy(function ($item, $index) use ($seed) {
            return hash('sha256', $seed.':'.$index.':'.json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        })->values();
    }
}
