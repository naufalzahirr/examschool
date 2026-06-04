<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionBankItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionBankController extends Controller
{
    public function index(Request $request)
    {
        $query = QuestionBankItem::query()
            ->visibleToUser(auth()->user())
            ->with('teacher')
            ->latest();

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('question_code', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('grade_level', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%");
            });
        }
        foreach (['subject', 'grade_level', 'type', 'difficulty', 'visibility'] as $field) {
            if ($value = $request->get($field)) {
                $query->where($field, $value);
            }
        }

        $items = $query->paginate(25)->withQueryString();
        $filters = $this->filterOptions();

        return view('question_bank.index', compact('items', 'filters'));
    }

    public function create()
    {
        return view('question_bank.form', [
            'item' => new QuestionBankItem(['type' => Question::TYPE_MULTIPLE_CHOICE, 'points' => 1, 'difficulty' => 'sedang', 'is_active' => true, 'visibility' => QuestionBankItem::VISIBILITY_SCHOOL]),
            'typeLabels' => QuestionBankItem::typeLabels(),
            'visibilityLabels' => QuestionBankItem::visibilityLabels(),
            'initialQuestions' => [[
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'title' => '',
                'description' => '',
                'points' => 1,
                'correct_text' => '',
                'answer_key' => [],
                'options' => [
                    ['label' => 'Opsi 1', 'is_correct' => true],
                    ['label' => 'Opsi 2', 'is_correct' => false],
                ],
            ]],
            'initialQuestion' => [
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'title' => '',
                'description' => '',
                'points' => 1,
                'correct_text' => '',
                'answer_key' => [],
                'options' => [
                    ['label' => 'Opsi 1', 'is_correct' => true],
                    ['label' => 'Opsi 2', 'is_correct' => false],
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, multiple: true);
        $questions = $this->questionPayloadsFromRequest($request);

        $created = 0;
        DB::transaction(function () use ($request, $data, $questions, &$created) {
            foreach ($questions as $question) {
                $payload = $this->normalizeQuestionPayload($request, $data, $question);
                $payload['teacher_id'] = auth()->id();
                QuestionBankItem::create($payload);
                $created++;
            }
        });

        AuditLog::record('question_bank.created_many', null, ['created' => $created]);

        return redirect()->route('question-bank.index')->with('success', "{$created} soal berhasil ditambahkan ke bank soal.");
    }

    public function edit(QuestionBankItem $questionBank)
    {
        $this->ensureCanManage($questionBank);

        return view('question_bank.form', [
            'item' => $questionBank,
            'typeLabels' => QuestionBankItem::typeLabels(),
            'visibilityLabels' => QuestionBankItem::visibilityLabels(),
            'initialQuestion' => $this->bankQuestionPayload($questionBank),
        ]);
    }

    public function update(Request $request, QuestionBankItem $questionBank)
    {
        $this->ensureCanManage($questionBank);
        $data = $this->validated($request);
        $data = $this->normalizePayload($request, $data);
        $questionBank->update($data);
        AuditLog::record('question_bank.updated', $questionBank, ['type' => $questionBank->type]);

        return redirect()->route('question-bank.index')->with('success', 'Bank soal berhasil diperbarui.');
    }

    public function destroy(QuestionBankItem $questionBank)
    {
        $this->ensureCanManage($questionBank);
        AuditLog::record('question_bank.deleted', $questionBank, ['code' => $questionBank->question_code]);
        $questionBank->delete();

        return redirect()->route('question-bank.index')->with('success', 'Soal bank berhasil dihapus.');
    }

    public function importPage()
    {
        return view('question_bank.import');
    }

    public function importSimple(Request $request)
    {
        $data = $request->validate([
            'questions_text' => ['required', 'string'],
            'subject' => ['nullable', 'string', 'max:120'],
            'grade_level' => ['nullable', 'string', 'max:80'],
            'visibility' => ['required', Rule::in(array_keys(QuestionBankItem::VISIBILITIES))],
        ]);

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach (preg_split('/\r\n|\r|\n/', $data['questions_text']) as $lineNo => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode(';', $line));
            [$type, $title, $points, $answer, $options] = array_pad($parts, 5, null);
            if (! $type || ! $title) {
                $skipped++;

                continue;
            }
            if (! in_array($type, Question::TYPES, true)) {
                $errors[] = 'Baris '.($lineNo + 1).': jenis soal tidak valid.';

                continue;
            }

            try {
                $payload = [
                    'teacher_id' => auth()->id(),
                    'subject' => $data['subject'] ?: null,
                    'grade_level' => $data['grade_level'] ?: null,
                    'type' => $type,
                    'title' => $title,
                    'points' => $points ?: 1,
                    'difficulty' => 'sedang',
                    'visibility' => $data['visibility'],
                    'is_active' => true,
                ];

                [$payload['options'], $payload['answer_key'], $payload['correct_text']] = $this->parseImportAnswer($type, (string) $answer, (string) $options);
                QuestionBankItem::create($payload);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = 'Baris '.($lineNo + 1).': '.$e->getMessage();
            }
        }

        $message = "Import bank soal selesai. Tersimpan: {$created}, dilewati: {$skipped}.";
        if ($errors) {
            $message .= ' Error: '.implode(' | ', array_slice($errors, 0, 5));
        }

        AuditLog::record('question_bank.imported', null, ['created' => $created, 'skipped' => $skipped]);

        return back()->with('success', $message);
    }

    public function addToExam(Request $request, Exam $exam)
    {
        abort_unless(auth()->user()->canManageExam($exam), 403);
        if (! $exam->canEditQuestions()) {
            return back()->withErrors(['bank' => 'Soal ujian sudah dikunci. Import dari bank soal hanya boleh saat draft dan belum ada aktivitas siswa.']);
        }

        $data = $request->validate([
            'question_bank_ids' => ['required', 'array'],
            'question_bank_ids.*' => ['integer', 'exists:question_bank_items,id'],
        ]);

        $existingBankItemIds = $this->examBankItemIds($exam);
        $items = QuestionBankItem::query()
            ->visibleToUser(auth()->user(), forSelection: true)
            ->whereIn('id', $data['question_bank_ids'])
            ->whereNotIn('id', $existingBankItemIds)
            ->where('is_active', true)
            ->get();

        if ($items->isEmpty()) {
            return back()->withErrors(['bank' => 'Belum ada soal baru yang bisa ditambahkan. Soal mungkin sudah ada di ujian atau tidak aktif.']);
        }

        $added = 0;
        DB::transaction(function () use ($exam, $items, &$added) {
            $order = (int) $exam->questions()->max('order_no');
            foreach ($items as $item) {
                $payload = $item->toQuestionPayload();
                $question = $exam->questions()->create([
                    'question_code' => $payload['question_code'],
                    'type' => $payload['type'],
                    'title' => $payload['title'],
                    'description' => $payload['description'],
                    'required' => true,
                    'points' => $payload['points'],
                    'order_no' => ++$order,
                    'correct_text' => $payload['correct_text'],
                    'answer_key' => $payload['answer_key'],
                    'settings' => $payload['settings'],
                ]);

                foreach (($payload['options'] ?? []) as $i => $option) {
                    $question->options()->create([
                        'label' => $option['label'] ?? '',
                        'is_correct' => (bool) ($option['is_correct'] ?? false),
                        'order_no' => $i + 1,
                        'meta' => $option['meta'] ?? (isset($option['match']) ? ['match' => $option['match']] : null),
                    ]);
                }
                $added++;
            }
            $exam->bumpPackageVersion();
        });

        AuditLog::record('exam.questions_added_from_bank', $exam, ['added' => $added]);
        $skipped = count($data['question_bank_ids']) - $added;
        $message = "{$added} soal berhasil ditambahkan dari bank soal.";
        if ($skipped > 0) {
            $message .= " {$skipped} soal dilewati karena sudah ada di ujian atau tidak bisa dipakai.";
        }

        return redirect()->route('exams.show', $exam)->with('success', $message.' Cek checklist kesiapan lalu publish ujian jika sudah lengkap.');
    }

    public function selectForExam(Request $request, Exam $exam)
    {
        abort_unless(auth()->user()->canManageExam($exam), 403);
        $query = QuestionBankItem::query()
            ->visibleToUser(auth()->user(), forSelection: true)
            ->where('is_active', true)
            ->with('teacher')
            ->latest();

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('question_code', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('grade_level', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%");
            });
        }

        foreach (['subject', 'grade_level', 'type', 'difficulty', 'visibility'] as $field) {
            if ($value = $request->get($field)) {
                $query->where($field, $value);
            }
        }

        if ($owner = $request->get('owner')) {
            if ($owner === 'mine') {
                $query->where('teacher_id', auth()->id());
            } elseif ($owner === 'shared') {
                $query->where('visibility', QuestionBankItem::VISIBILITY_SCHOOL)
                    ->where('teacher_id', '!=', auth()->id());
            }
        }

        $items = $query->paginate(30)->withQueryString();
        $filters = $this->selectionFilterOptions();
        $existingBankItemIds = $this->examBankItemIds($exam);

        return view('question_bank.select_for_exam', compact('exam', 'items', 'filters', 'existingBankItemIds'));
    }

    private function validated(Request $request, bool $multiple = false): array
    {
        $rules = [
            'subject' => ['nullable', 'string', 'max:120'],
            'grade_level' => ['nullable', 'string', 'max:80'],
            'topic' => ['nullable', 'string', 'max:120'],
            'difficulty' => ['required', Rule::in(['mudah', 'sedang', 'sulit'])],
            'visibility' => ['required', Rule::in(array_keys(QuestionBankItem::VISIBILITIES))],
            'is_active' => ['nullable', 'boolean'],
        ];

        $rules[$multiple ? 'questions_json' : 'question_json'] = ['required', 'json'];

        return $request->validate($rules);
    }

    private function normalizePayload(Request $request, array $data): array
    {
        $question = json_decode((string) ($data['question_json'] ?? ''), true);
        unset($data['question_json']);

        return $this->normalizeQuestionPayload($request, $data, $question);
    }

    private function questionPayloadsFromRequest(Request $request): array
    {
        $questions = json_decode((string) $request->input('questions_json', ''), true);
        if (! is_array($questions)) {
            throw ValidationException::withMessages(['questions_json' => 'Format bank soal tidak valid.']);
        }

        $questions = array_values($questions);
        if (count($questions) < 1) {
            throw ValidationException::withMessages(['questions_json' => 'Minimal buat 1 soal.']);
        }

        return $questions;
    }

    private function normalizeQuestionPayload(Request $request, array $data, mixed $question): array
    {
        if (! is_array($question)) {
            throw ValidationException::withMessages(['question_json' => 'Format bank soal tidak valid.']);
        }

        $type = $question['type'] ?? Question::TYPE_MULTIPLE_CHOICE;
        if (! in_array($type, Question::TYPES, true)) {
            throw ValidationException::withMessages(['question_json' => 'Jenis soal tidak valid.']);
        }

        $errors = $this->validateBankQuestion($question);
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $options = $this->optionsFor($type, $question);
        $answerKey = $this->answerKeyFor($type, $question);

        $data['type'] = $type;
        $data['title'] = trim((string) ($question['title'] ?? ''));
        $data['description'] = trim((string) ($question['description'] ?? '')) ?: null;
        $data['points'] = max(0, (float) ($question['points'] ?? 1));
        $data['options'] = $options ?: null;
        $data['answer_key'] = $answerKey;
        $data['correct_text'] = $this->correctTextFor($type, $question, $options, $answerKey);
        $data['visibility'] = $data['visibility'] ?? QuestionBankItem::VISIBILITY_SCHOOL;
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    private function validateBankQuestion(array $question): array
    {
        $errors = [];
        $type = $question['type'] ?? Question::TYPE_MULTIPLE_CHOICE;
        $title = trim((string) ($question['title'] ?? ''));

        if ($title === '') {
            $errors['question_json'] = 'Pertanyaan wajib diisi.';

            return $errors;
        }

        $options = collect($question['options'] ?? [])
            ->filter(fn ($o) => trim((string) ($o['label'] ?? '')) !== '')
            ->values();

        if ($type === Question::TYPE_MULTIPLE_CHOICE) {
            if ($options->count() < 2) {
                $errors['question_json'] = 'Pilihan ganda minimal punya 2 opsi.';
            } elseif ($options->where('is_correct', true)->count() !== 1) {
                $errors['question_json'] = 'Pilihan ganda harus punya tepat 1 kunci jawaban.';
            }
        }

        if ($type === Question::TYPE_MULTIPLE_CHOICE_COMPLEX) {
            if ($options->count() < 2) {
                $errors['question_json'] = 'Pilihan ganda kompleks minimal punya 2 opsi.';
            } elseif ($options->where('is_correct', true)->count() < 1) {
                $errors['question_json'] = 'Pilihan ganda kompleks minimal punya 1 kunci jawaban.';
            }
        }

        if ($type === Question::TYPE_MATCHING) {
            $pairs = collect($question['options'] ?? [])->filter(function ($o) {
                return trim((string) ($o['label'] ?? '')) !== ''
                    && trim((string) ($o['match'] ?? ($o['meta']['match'] ?? ''))) !== '';
            });
            if ($pairs->count() < 2) {
                $errors['question_json'] = 'Menjodohkan minimal punya 2 pasangan lengkap.';
            }
        }

        if ($type === Question::TYPE_SHORT_ANSWER) {
            $accepted = $question['answer_key']['accepted'] ?? [];
            if (! is_array($accepted)) {
                $accepted = preg_split('/[;\n]/', (string) $accepted);
            }
            $accepted = collect($accepted)->map(fn ($v) => trim((string) $v))->filter();
            if ($accepted->isEmpty() && trim((string) ($question['correct_text'] ?? '')) === '') {
                $errors['question_json'] = 'Jawaban singkat wajib punya minimal 1 kunci jawaban.';
            }
        }

        return $errors;
    }

    private function optionsFor(string $type, array $question): ?array
    {
        if (in_array($type, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_MULTIPLE_CHOICE_COMPLEX], true)) {
            return collect($question['options'] ?? [])
                ->map(fn ($o) => [
                    'label' => trim((string) ($o['label'] ?? '')),
                    'is_correct' => (bool) ($o['is_correct'] ?? false),
                ])
                ->filter(fn ($o) => $o['label'] !== '')
                ->values()
                ->all();
        }

        if ($type === Question::TYPE_MATCHING) {
            return collect($question['options'] ?? [])
                ->map(function ($o) {
                    $left = trim((string) ($o['label'] ?? ''));
                    $right = trim((string) ($o['match'] ?? ($o['meta']['match'] ?? '')));

                    return [
                        'label' => $left,
                        'is_correct' => true,
                        'meta' => ['match' => $right],
                    ];
                })
                ->filter(fn ($o) => $o['label'] !== '' && ($o['meta']['match'] ?? '') !== '')
                ->values()
                ->all();
        }

        return null;
    }

    private function answerKeyFor(string $type, array $question): ?array
    {
        if ($type === Question::TYPE_TRUE_FALSE) {
            return ['answer' => filter_var($question['answer_key']['answer'] ?? $question['true_false_answer'] ?? true, FILTER_VALIDATE_BOOLEAN)];
        }

        if ($type === Question::TYPE_SHORT_ANSWER) {
            $accepted = $question['answer_key']['accepted'] ?? [];
            if (! is_array($accepted)) {
                $accepted = preg_split('/[;\n]/', (string) $accepted);
            }
            $accepted = collect($accepted)
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->values()
                ->all();

            if (! $accepted && ! empty($question['correct_text'])) {
                $accepted = [trim((string) $question['correct_text'])];
            }

            return ['accepted' => $accepted];
        }

        return null;
    }

    private function correctTextFor(string $type, array $question, ?array $options, ?array $answerKey): ?string
    {
        if (in_array($type, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_MULTIPLE_CHOICE_COMPLEX], true)) {
            $correct = collect($options ?? [])->where('is_correct', true)->pluck('label')->values()->all();

            return $correct ? implode(' | ', $correct) : null;
        }

        if ($type === Question::TYPE_TRUE_FALSE) {
            return ($answerKey['answer'] ?? false) ? 'Benar' : 'Salah';
        }

        if ($type === Question::TYPE_MATCHING) {
            return collect($options ?? [])
                ->map(fn ($o) => ($o['label'] ?? '').' = '.($o['meta']['match'] ?? ''))
                ->implode(' | ') ?: null;
        }

        if ($type === Question::TYPE_SHORT_ANSWER) {
            return collect($answerKey['accepted'] ?? [])->implode(' | ') ?: null;
        }

        return null;
    }

    private function parseImportAnswer(string $type, string $answerText, string $optionsText): array
    {
        $options = [];
        $answerKey = null;
        $correctText = null;

        if (in_array($type, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_MULTIPLE_CHOICE_COMPLEX], true)) {
            $correctLabels = collect(preg_split('/[|,]/', $answerText))->map(fn ($v) => trim((string) $v))->filter()->values()->all();
            foreach (preg_split('/\r\n|\r|\n|\|/', $optionsText) as $line) {
                $label = trim($line);
                if ($label === '') {
                    continue;
                }
                $options[] = ['label' => $label, 'is_correct' => in_array($label, $correctLabels, true)];
            }
            $correctText = collect($options)->where('is_correct', true)->pluck('label')->implode(' | ') ?: null;
        } elseif ($type === Question::TYPE_TRUE_FALSE) {
            $answerKey = ['answer' => in_array(strtolower(trim($answerText)), ['1', 'true', 'benar', 'ya'], true)];
            $correctText = $answerKey['answer'] ? 'Benar' : 'Salah';
        } elseif ($type === Question::TYPE_MATCHING) {
            foreach (preg_split('/\r\n|\r|\n|\|/', $optionsText) as $line) {
                if (! str_contains($line, '=')) {
                    continue;
                }
                [$left, $right] = array_map('trim', explode('=', $line, 2));
                if ($left !== '' && $right !== '') {
                    $options[] = ['label' => $left, 'is_correct' => true, 'meta' => ['match' => $right]];
                }
            }
            $correctText = collect($options)->map(fn ($o) => ($o['label'] ?? '').' = '.($o['meta']['match'] ?? ''))->implode(' | ') ?: null;
        } elseif ($type === Question::TYPE_SHORT_ANSWER) {
            $accepted = collect(preg_split('/[|;\n]/', $answerText))->map(fn ($v) => trim((string) $v))->filter()->values()->all();
            $answerKey = ['accepted' => $accepted];
            $correctText = $accepted ? implode(' | ', $accepted) : null;
        }

        return [$options ?: null, $answerKey, $correctText];
    }

    private function bankQuestionPayload(QuestionBankItem $item): array
    {
        $options = collect($item->options ?? [])->map(function ($option) {
            return [
                'label' => $option['label'] ?? '',
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'match' => $option['match'] ?? ($option['meta']['match'] ?? ''),
                'meta' => $option['meta'] ?? null,
            ];
        })->values()->all();

        return [
            'type' => $item->type ?: Question::TYPE_MULTIPLE_CHOICE,
            'title' => $item->title ?: '',
            'description' => $item->description ?: '',
            'points' => (float) ($item->points ?: 1),
            'correct_text' => $item->correct_text ?: '',
            'answer_key' => $item->answer_key ?: [],
            'options' => $options,
        ];
    }

    private function filterOptions(): array
    {
        $base = QuestionBankItem::query()->visibleToUser(auth()->user());

        return [
            'subjects' => (clone $base)->select('subject')->whereNotNull('subject')->distinct()->orderBy('subject')->pluck('subject'),
            'grades' => (clone $base)->select('grade_level')->whereNotNull('grade_level')->distinct()->orderBy('grade_level')->pluck('grade_level'),
            'types' => QuestionBankItem::typeLabels(),
            'difficulties' => ['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit'],
            'visibilities' => QuestionBankItem::visibilityLabels(),
        ];
    }

    private function selectionFilterOptions(): array
    {
        $base = QuestionBankItem::query()
            ->visibleToUser(auth()->user(), forSelection: true)
            ->where('is_active', true);

        return [
            'subjects' => (clone $base)->select('subject')->whereNotNull('subject')->distinct()->orderBy('subject')->pluck('subject'),
            'grades' => (clone $base)->select('grade_level')->whereNotNull('grade_level')->distinct()->orderBy('grade_level')->pluck('grade_level'),
            'types' => QuestionBankItem::typeLabels(),
            'difficulties' => ['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit'],
            'visibilities' => QuestionBankItem::visibilityLabels(),
            'owners' => ['mine' => 'Soal saya', 'shared' => 'Dibagikan guru lain'],
        ];
    }

    private function examBankItemIds(Exam $exam): array
    {
        return $exam->questions()
            ->get(['settings'])
            ->map(fn ($question) => (int) ($question->settings['source_bank_item_id'] ?? 0))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function ensureCanManage(QuestionBankItem $item): void
    {
        abort_unless($item->canBeManagedBy(auth()->user()), 403);
    }
}
