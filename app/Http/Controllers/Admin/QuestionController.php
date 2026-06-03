<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

class QuestionController extends Controller
{
    public function builder(Exam $exam)
    {
        abort_unless(auth()->user()->canManageExam($exam), 403);
        $exam->load(['questions.options'])->loadCount('attempts');
        $canEdit = $exam->canEditQuestions();

        return view('exams.builder', compact('exam', 'canEdit'));
    }

    public function bulkSave(Request $request, Exam $exam)
    {
        abort_unless(auth()->user()->canManageExam($exam), 403);
        if (! $exam->canEditQuestions()) {
            return back()->withErrors([
                'questions_json' => 'Soal sudah dikunci. Edit soal hanya boleh saat status draft dan belum ada siswa yang login/mulai ujian.',
            ]);
        }

        $data = $request->validate([
            'questions_json' => ['required', 'json'],
        ]);

        $questions = json_decode($data['questions_json'], true);
        if (!is_array($questions)) {
            return back()->withErrors(['questions_json' => 'Format soal tidak valid.']);
        }

        $errors = $this->validateQuestions($questions);
        if ($errors->isNotEmpty()) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use ($exam, $questions) {
            $exam->questions()->delete();

            foreach (array_values($questions) as $index => $item) {
                $type = $item['type'] ?? Question::TYPE_MULTIPLE_CHOICE;
                if (!in_array($type, Question::TYPES, true)) {
                    $type = Question::TYPE_MULTIPLE_CHOICE;
                }

                $answerKey = $this->answerKeyFor($type, $item);
                $question = $exam->questions()->create([
                    'question_code' => $item['question_code'] ?? null,
                    'type' => $type,
                    'title' => trim((string) ($item['title'] ?? '')),
                    'description' => trim((string) ($item['description'] ?? '')) ?: null,
                    'required' => (bool) ($item['required'] ?? true),
                    'points' => max(0, (float) ($item['points'] ?? 1)),
                    'order_no' => $index + 1,
                    'correct_text' => trim((string) ($item['correct_text'] ?? '')) ?: null,
                    'answer_key' => $answerKey,
                    'settings' => $item['settings'] ?? null,
                ]);

                if (in_array($type, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_MULTIPLE_CHOICE_COMPLEX], true)) {
                    $this->saveChoiceOptions($question, $item);
                }

                if ($type === Question::TYPE_MATCHING) {
                    $this->saveMatchingOptions($question, $item);
                }
            }

            $exam->bumpPackageVersion();
            AuditLog::record('exam.questions_saved', $exam, ['questions_count' => count($questions)]);
        });

        return redirect()->route('exams.builder', $exam)->with('success', 'Soal berhasil disimpan. Kode tiap soal dan versi paket otomatis diperbarui.');
    }

    private function validateQuestions(array $questions): MessageBag
    {
        $errors = new MessageBag();
        if (count($questions) < 1) {
            $errors->add('questions_json', 'Minimal buat 1 soal.');
            return $errors;
        }

        foreach (array_values($questions) as $index => $item) {
            $no = $index + 1;
            $type = $item['type'] ?? Question::TYPE_MULTIPLE_CHOICE;
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                $errors->add('q' . $no, "Pertanyaan {$no}: judul soal wajib diisi.");
            }

            if (!in_array($type, Question::TYPES, true)) {
                $errors->add('q' . $no, "Pertanyaan {$no}: jenis soal tidak valid.");
                continue;
            }

            $options = collect($item['options'] ?? [])->filter(fn ($o) => trim((string) ($o['label'] ?? '')) !== '')->values();
            if ($type === Question::TYPE_MULTIPLE_CHOICE) {
                if ($options->count() < 2) {
                    $errors->add('q' . $no, "Pertanyaan {$no}: pilihan ganda minimal punya 2 opsi.");
                }
                if ($options->where('is_correct', true)->count() !== 1) {
                    $errors->add('q' . $no, "Pertanyaan {$no}: pilihan ganda harus punya tepat 1 kunci jawaban.");
                }
            }

            if ($type === Question::TYPE_MULTIPLE_CHOICE_COMPLEX) {
                if ($options->count() < 2) {
                    $errors->add('q' . $no, "Pertanyaan {$no}: pilihan ganda kompleks minimal punya 2 opsi.");
                }
                if ($options->where('is_correct', true)->count() < 1) {
                    $errors->add('q' . $no, "Pertanyaan {$no}: pilihan ganda kompleks minimal punya 1 kunci jawaban.");
                }
            }

            if ($type === Question::TYPE_MATCHING) {
                $pairs = collect($item['options'] ?? [])->filter(function ($o) {
                    return trim((string) ($o['label'] ?? '')) !== '' && trim((string) ($o['match'] ?? ($o['meta']['match'] ?? ''))) !== '';
                });
                if ($pairs->count() < 2) {
                    $errors->add('q' . $no, "Pertanyaan {$no}: menjodohkan minimal punya 2 pasangan lengkap.");
                }
            }

            if ($type === Question::TYPE_SHORT_ANSWER) {
                $accepted = $item['answer_key']['accepted'] ?? [];
                if (!is_array($accepted)) {
                    $accepted = preg_split('/[;\n]/', (string) $accepted);
                }
                $accepted = collect($accepted)->map(fn ($v) => trim((string) $v))->filter();
                if ($accepted->isEmpty() && trim((string) ($item['correct_text'] ?? '')) === '') {
                    $errors->add('q' . $no, "Pertanyaan {$no}: uraian jawaban singkat wajib punya minimal 1 kunci jawaban.");
                }
            }
        }

        return $errors;
    }

    private function answerKeyFor(string $type, array $item): ?array
    {
        if ($type === Question::TYPE_TRUE_FALSE) {
            return ['answer' => filter_var($item['answer_key']['answer'] ?? $item['true_false_answer'] ?? true, FILTER_VALIDATE_BOOLEAN)];
        }

        if ($type === Question::TYPE_SHORT_ANSWER) {
            $accepted = $item['answer_key']['accepted'] ?? [];
            if (!is_array($accepted)) {
                $accepted = preg_split('/[;\n]/', (string) $accepted);
            }
            $accepted = collect($accepted)
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->values()
                ->all();

            if (!$accepted && !empty($item['correct_text'])) {
                $accepted = [trim((string) $item['correct_text'])];
            }

            return ['accepted' => $accepted];
        }

        return null;
    }

    private function saveChoiceOptions(Question $question, array $item): void
    {
        foreach (array_values($item['options'] ?? []) as $optionIndex => $option) {
            $label = trim((string) ($option['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $question->options()->create([
                'label' => $label,
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'order_no' => $optionIndex + 1,
                'meta' => $option['meta'] ?? null,
            ]);
        }
    }

    private function saveMatchingOptions(Question $question, array $item): void
    {
        foreach (array_values($item['options'] ?? []) as $optionIndex => $option) {
            $left = trim((string) ($option['label'] ?? ''));
            $right = trim((string) ($option['match'] ?? ($option['meta']['match'] ?? '')));
            if ($left === '' || $right === '') {
                continue;
            }

            $question->options()->create([
                'label' => $left,
                'is_correct' => true,
                'order_no' => $optionIndex + 1,
                'meta' => ['match' => $right],
            ]);
        }
    }
}
