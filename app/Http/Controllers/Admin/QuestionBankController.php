<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionBankItem;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function detail(QuestionBankItem $questionBank)
    {
        abort_unless(
            QuestionBankItem::query()->visibleToUser(auth()->user())->whereKey($questionBank->id)->exists(),
            403
        );

        return response()->json([
            'title' => $questionBank->title,
            'description' => $questionBank->description,
            'type' => $questionBank->type,
            'type_label' => QuestionBankItem::typeLabels()[$questionBank->type] ?? $questionBank->type,
            'points' => (float) $questionBank->points,
            'subject' => $questionBank->subject,
            'grade_level' => $questionBank->grade_level,
            'topic' => $questionBank->topic,
            'difficulty' => ucfirst((string) $questionBank->difficulty),
            'code' => $questionBank->question_code,
            'options' => collect($questionBank->options ?? [])->map(fn ($o) => [
                'label' => $o['label'] ?? '',
                'is_correct' => (bool) ($o['is_correct'] ?? false),
                'match' => $o['match'] ?? ($o['meta']['match'] ?? null),
            ])->values(),
            'answer' => $questionBank->answerPreview(),
            'can_manage' => $questionBank->canBeManagedBy(auth()->user()),
            'edit_url' => $questionBank->canBeManagedBy(auth()->user()) ? route('question-bank.edit', $questionBank) : null,
        ]);
    }

    public function downloadTemplate()
    {
        $filename = 'template-import-bank-soal.csv';
        $rows = [
            ['jenis', 'pertanyaan', 'poin', 'kunci', 'opsi'],
            ['multiple_choice', 'Ibu kota Indonesia?', '10', 'Jakarta', 'Jakarta|Bandung|Surabaya|Medan'],
            ['multiple_choice_complex', 'Pilih bilangan genap', '10', '2|4', '2|3|4|5'],
            ['true_false', 'Matahari terbit dari timur', '5', 'Benar', ''],
            ['matching', 'Jodohkan negara dan ibukota', '10', '', 'Indonesia=Jakarta|Jepang=Tokyo'],
            ['short_answer', 'Singkatan dari Republik Indonesia', '5', 'RI', ''],
        ];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
            'bank_key' => ['required', 'string'],
        ]);

        $group = $this->decodeBankGroupKey($data['bank_key']);
        $existingBankItemIds = $this->examBankItemIds($exam);
        $groupQuery = QuestionBankItem::query()
            ->visibleToUser(auth()->user(), forSelection: true)
            ->where('is_active', true);

        $this->applyBankGroupWhere($groupQuery, $group);

        $selectedCount = (clone $groupQuery)->count();
        $items = $groupQuery->whereNotIn('id', $existingBankItemIds)->orderBy('id')->get();

        if ($items->isEmpty()) {
            return back()->withErrors(['bank' => 'Belum ada soal baru dari bank ini yang bisa ditambahkan. Soal mungkin sudah masuk semua ke ujian atau tidak aktif.']);
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
        $skipped = max(0, $selectedCount - $added);
        $message = "{$added} soal berhasil ditambahkan dari paket Bank Soal.";
        if ($skipped > 0) {
            $message .= " {$skipped} soal dilewati karena sudah ada di ujian atau tidak bisa dipakai.";
        }

        return redirect()->route('exams.show', $exam)->with('success', $message.' Cek checklist kesiapan lalu publish ujian jika sudah lengkap.');
    }

    public function selectForExam(Request $request, Exam $exam)
    {
        abort_unless(auth()->user()->canManageExam($exam), 403);
        if (! $exam->canEditQuestions()) {
            return redirect()->route('exams.show', $exam)
                ->withErrors(['bank' => 'Soal ujian sudah dikunci. Pemilihan dari Bank Soal hanya bisa dilakukan saat ujian masih draft/siap dan belum ada aktivitas siswa.']);
        }

        $exam->loadCount('questions');
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

        $bankGroups = $this->paginateBankGroups(
            $this->bankGroupsFromItems($query->get(), $exam),
            $request
        );
        $filters = $this->selectionFilterOptions();

        return view('question_bank.select_for_exam', compact('exam', 'bankGroups', 'filters'));
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

    private function bankGroupsFromItems($items, Exam $exam)
    {
        $existingBankItemIds = $this->examBankItemIds($exam);

        return $items
            ->groupBy(fn (QuestionBankItem $item) => $this->bankGroupKey($item))
            ->map(function ($groupItems, string $key) use ($existingBankItemIds) {
                /** @var QuestionBankItem $first */
                $first = $groupItems->first();
                $available = $groupItems->reject(fn (QuestionBankItem $item) => in_array($item->id, $existingBankItemIds, true));

                return [
                    'key' => $key,
                    'title' => $this->bankGroupTitle($first),
                    'subject' => $first->subject,
                    'grade_level' => $first->grade_level,
                    'topic' => $first->topic,
                    'visibility' => $first->visibility,
                    'teacher_name' => $first->teacher?->name ?: '-',
                    'questions_count' => $groupItems->count(),
                    'available_count' => $available->count(),
                    'total_points' => $groupItems->sum(fn (QuestionBankItem $item) => (float) $item->points),
                    'types' => $groupItems->pluck('type')->unique()->map(fn ($type) => QuestionBankItem::typeLabels()[$type] ?? $type)->implode(', '),
                    'difficulties' => $groupItems->pluck('difficulty')->filter()->unique()->map(fn ($value) => ucfirst((string) $value))->implode(', '),
                    'updated_at' => $groupItems->max('updated_at'),
                ];
            })
            ->sortBy([
                ['subject', 'asc'],
                ['grade_level', 'asc'],
                ['topic', 'asc'],
                ['teacher_name', 'asc'],
            ])
            ->values();
    }

    private function paginateBankGroups($groups, Request $request): LengthAwarePaginator
    {
        $perPage = 20;
        $page = max(1, (int) $request->get('page', 1));

        return new LengthAwarePaginator(
            $groups->forPage($page, $perPage)->values(),
            $groups->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function bankGroupKey(QuestionBankItem $item): string
    {
        $payload = [
            'teacher_id' => (int) ($item->teacher_id ?: 0),
            'subject' => trim((string) $item->subject),
            'grade_level' => trim((string) $item->grade_level),
            'topic' => trim((string) $item->topic),
            'visibility' => trim((string) ($item->visibility ?: QuestionBankItem::VISIBILITY_SCHOOL)),
        ];

        return rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    }

    private function decodeBankGroupKey(string $key): array
    {
        $decoded = base64_decode(strtr($key, '-_', '+/').str_repeat('=', (4 - strlen($key) % 4) % 4), true);
        $payload = $decoded ? json_decode($decoded, true) : null;
        if (! is_array($payload)) {
            throw ValidationException::withMessages(['bank_key' => 'Paket Bank Soal tidak valid.']);
        }

        return [
            'teacher_id' => (int) ($payload['teacher_id'] ?? 0),
            'subject' => trim((string) ($payload['subject'] ?? '')),
            'grade_level' => trim((string) ($payload['grade_level'] ?? '')),
            'topic' => trim((string) ($payload['topic'] ?? '')),
            'visibility' => trim((string) ($payload['visibility'] ?? QuestionBankItem::VISIBILITY_SCHOOL)),
        ];
    }

    private function applyBankGroupWhere($query, array $group): void
    {
        $group['teacher_id'] > 0
            ? $query->where('teacher_id', $group['teacher_id'])
            : $query->whereNull('teacher_id');

        foreach (['subject', 'grade_level', 'topic'] as $field) {
            $value = $group[$field] ?? '';
            $query->where(function ($nested) use ($field, $value) {
                if ($value === '') {
                    $nested->whereNull($field)->orWhere($field, '');

                    return;
                }

                $nested->where($field, $value);
            });
        }

        $query->where('visibility', $group['visibility'] ?: QuestionBankItem::VISIBILITY_SCHOOL);
    }

    private function bankGroupTitle(QuestionBankItem $item): string
    {
        return collect([$item->subject, $item->grade_level, $item->topic])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->implode(' - ') ?: 'Bank Soal Tanpa Label';
    }

    private function ensureCanManage(QuestionBankItem $item): void
    {
        abort_unless($item->canBeManagedBy(auth()->user()), 403);
    }
}
