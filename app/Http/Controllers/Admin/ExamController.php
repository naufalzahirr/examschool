<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\SchoolSetting;
use App\Services\ExamPackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    public function __construct(private readonly ExamPackageService $examPackageService) {}

    public function index(Request $request)
    {
        $query = $this->examQuery()
            ->withCount([
                'questions',
                'classrooms',
                'participants',
                'participants as submitted_count' => fn ($q) => $q->where('status', 'submitted'),
            ])
            ->with('classrooms')
            ->latest();

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('access_code', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('grade_level', 'like', "%{$search}%")
                    ->orWhereHas('classrooms', fn ($qq) => $qq->where('nama_kelas', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($classroomId = $request->get('classroom_id')) {
            $query->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroomId));
        }

        $exams = $query->paginate(15)->withQueryString();
        $classrooms = $this->availableClassrooms();
        $statuses = Exam::STATUSES;

        return view('exams.index', compact('exams', 'classrooms', 'statuses'));
    }

    public function create()
    {
        return view('exams.form', [
            'exam' => new Exam([
                'duration_minutes' => 90,
                'status' => Exam::STATUS_DRAFT,
                'schedule_mode' => Exam::MODE_MANUAL,
                'lock_mode' => Exam::LOCK_STRICT_AIRPLANE,
                'exit_policy' => Exam::EXIT_AFTER_SUBMIT,
            ]),
            'classrooms' => $this->availableClassrooms(),
            'selectedClassroomIds' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $classroomIds = $data['classroom_ids'] ?? [];
        unset($data['classroom_ids']);

        $data['teacher_id'] = Auth::id();
        $data['status'] = Exam::STATUS_DRAFT;
        $data['access_code'] = Exam::generateAccessCode();

        $exam = DB::transaction(function () use ($data, $classroomIds) {
            $exam = Exam::create($data);
            $exam->classrooms()->sync($classroomIds);
            $createdParticipants = $exam->syncParticipantsFromClassrooms();
            $exam->bumpPackageVersion();
            $exam->setAttribute('created_participants_count', $createdParticipants);
            AuditLog::record('exam.created', $exam, ['access_code' => $exam->access_code]);

            return $exam;
        });

        $message = 'Ujian dibuat. Kode ujian otomatis: '.$exam->access_code;
        if (($exam->created_participants_count ?? 0) > 0) {
            $message .= ' Peserta otomatis dari kelas terpilih: '.$exam->created_participants_count.' siswa.';
        }

        return redirect()->route('exams.question-bank.select', $exam)->with('success', $message.' Sekarang pilih soal dari Bank Soal untuk ujian ini.');
    }

    public function show(Exam $exam)
    {
        $this->ensureCanManage($exam);
        $exam->load(['classrooms'])->loadCount(['questions', 'participants', 'attempts', 'classrooms']);
        $readiness = $exam->readinessChecklist();
        $queueStats = [
            'download_window_open' => $exam->isPackageDownloadWindowOpen(),
            'download_opens_at' => optional($exam->downloadOpensAt())->format('d M Y H:i'),
            'waiting' => $exam->participants()->whereNotNull('package_queue_joined_at')->whereNull('package_queue_token')->whereNull('package_download_finished_at')->count(),
            'active' => $exam->participants()->whereNotNull('package_queue_token')->whereNotNull('package_queue_expires_at')->where('package_queue_expires_at', '>', now())->whereNull('package_download_finished_at')->count(),
            'downloaded' => $exam->participants()->whereNotNull('package_download_finished_at')->count(),
            'unlocked' => $exam->participants()->whereNotNull('package_unlock_key_issued_at')->count(),
            'limit' => (int) SchoolSetting::getValue('package_download_concurrent_limit', 50),
        ];

        return view('exams.show', compact('exam', 'readiness', 'queueStats'));
    }

    public function edit(Exam $exam)
    {
        $this->ensureCanManage($exam);
        $exam->load('classrooms');

        return view('exams.form', [
            'exam' => $exam,
            'classrooms' => $this->availableClassrooms(),
            'selectedClassroomIds' => $exam->classrooms->pluck('id')->map(fn ($id) => (string) $id)->all(),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $this->ensureCanManage($exam);
        $data = $this->validated($request, $exam);
        $classroomIds = $data['classroom_ids'] ?? [];
        unset($data['classroom_ids']);

        if (in_array($exam->status, [Exam::STATUS_CLOSED, Exam::STATUS_ARCHIVED], true)) {
            return back()->withErrors([
                'exam' => 'Ujian yang sudah ditutup/diarsipkan tidak boleh diedit. Buat ujian baru jika perlu perubahan.',
            ])->withInput();
        }

        if ($exam->hasStartedWork()) {
            // Partial update: hanya field yang aman (tidak mempengaruhi soal, kelas, atau penilaian)
            $safeData = $request->validate([
                'title' => ['required', 'string', 'max:180'],
                'description' => ['nullable', 'string'],
                'subject' => ['nullable', 'string', 'max:120'],
                'grade_level' => ['nullable', 'string', 'max:60'],
            ]);
            $exam->update($safeData);
            AuditLog::record('exam.updated', $exam, ['access_code' => $exam->access_code, 'partial' => true]);

            return redirect()->route('exams.show', $exam)
                ->with('success', 'Judul dan keterangan ujian berhasil diperbarui. Jadwal, kelas, dan durasi tidak berubah.');
        }

        $createdParticipants = DB::transaction(function () use ($exam, $data, $classroomIds) {
            $exam->update($data);
            $exam->classrooms()->sync($classroomIds);
            $createdParticipants = $exam->syncParticipantsFromClassrooms();
            $removedParticipants = in_array($exam->status, [Exam::STATUS_DRAFT, Exam::STATUS_READY], true) ? $exam->pruneParticipantsOutsideClassrooms() : 0;
            $exam->bumpPackageVersion();
            AuditLog::record('exam.updated', $exam, ['access_code' => $exam->access_code]);

            return ['created' => $createdParticipants, 'removed' => $removedParticipants];
        });

        $message = 'Konfigurasi ujian diperbarui. Kode ujian tetap: '.$exam->access_code;
        if (($createdParticipants['created'] ?? 0) > 0) {
            $message .= ' Peserta baru dari kelas terpilih: '.$createdParticipants['created'].' siswa.';
        }
        if (($createdParticipants['removed'] ?? 0) > 0) {
            $message .= ' Peserta draft di luar kelas terpilih dihapus: '.$createdParticipants['removed'].' siswa.';
        }

        $exam->refresh();
        if ($exam->status === Exam::STATUS_PUBLISHED && ! $exam->hasStartedWork()) {
            $this->examPackageService->generate($exam->fresh(['questions.options', 'classrooms']));
            $message .= ' Soal untuk aplikasi siswa sudah disiapkan ulang.';
        } elseif ($exam->status === Exam::STATUS_PUBLISHED && ! $exam->hasGeneratedPackage()) {
            $message .= ' Perhatian: soal untuk aplikasi siswa perlu disiapkan ulang dari halaman detail ujian.';
        }

        return redirect()->route('exams.show', $exam)->with('success', $message);
    }

    public function destroy(Exam $exam)
    {
        $this->ensureCanManage($exam);
        abort_if($exam->hasStartedWork(), 422, 'Ujian yang sudah memiliki aktivitas siswa tidak boleh dihapus. Gunakan arsipkan.');
        AuditLog::record('exam.deleted', $exam, ['access_code' => $exam->access_code]);
        $exam->delete();

        return redirect()->route('exams.index')->with('success', 'Ujian dihapus.');
    }

    public function regenerateCode(Exam $exam)
    {
        $this->ensureCanManage($exam);
        abort_if(in_array($exam->status, [Exam::STATUS_PUBLISHED, Exam::STATUS_CLOSED, Exam::STATUS_ARCHIVED], true), 422, 'Kode ujian tidak bisa diganti setelah ujian dipublish/ditutup/diarsipkan.');

        $exam->forceFill(['access_code' => Exam::generateAccessCode()])->save();
        AuditLog::record('exam.code_regenerated', $exam, ['access_code' => $exam->access_code]);

        return back()->with('success', 'Kode ujian berhasil dibuat ulang: '.$exam->access_code);
    }

    public function publish(Exam $exam)
    {
        $this->ensureCanManage($exam);
        if ($exam->participants()->count() < 1) {
            $exam->syncParticipantsFromClassrooms();
        }

        if (! $exam->isReadyToPublish()) {
            return back()->withErrors(['publish' => 'Ujian belum siap dipublish. Cek checklist kesiapan di halaman detail ujian.']);
        }

        $package = DB::transaction(function () use ($exam) {
            $exam->forceFill([
                'status' => Exam::STATUS_PUBLISHED,
                'published_at' => now(),
                'closed_at' => null,
            ])->save();

            $package = $this->examPackageService->generate($exam->fresh(['questions.options', 'classrooms']));
            AuditLog::record('exam.published', $exam, [
                'access_code' => $exam->access_code,
                'package_checksum' => $package['checksum'],
                'package_size_bytes' => $package['size_bytes'],
            ]);

            return $package;
        });

        $message = 'Ujian dipublish dan soal sudah siap untuk aplikasi siswa.';
        $message .= $exam->isManualMode()
            ? ' Ujian masih tertutup. Nyalakan "menerima jawaban" saat siswa siap mulai.'
            : ' Ujian akan terbuka otomatis sesuai jadwal.';

        return back()->with('success', $message);
    }

    public function toggleManual(Request $request, Exam $exam)
    {
        $this->ensureCanManage($exam);

        $data = $request->validate([
            'target' => ['required', Rule::in(['download', 'exam'])],
            'state' => ['required', 'boolean'],
        ]);

        abort_unless($exam->status === Exam::STATUS_PUBLISHED, 422, 'Ujian harus dipublish dulu.');
        abort_unless($exam->isManualMode(), 422, 'Ujian ini memakai jadwal otomatis, bukan mode manual.');

        $state = (bool) $data['state'];

        if ($data['target'] === 'exam') {
            // Membuka ujian otomatis membuka download juga; menutup ujian tidak menutup download
            $exam->manual_exam_open = $state;
            if ($state) {
                $exam->manual_download_open = true;
            }
            $msg = $state ? 'Menerima jawaban dinyalakan. Siswa sekarang bisa membuka soal dan mulai mengerjakan.' : 'Menerima jawaban dimatikan. Siswa tidak bisa membuka soal baru.';
        } else {
            $exam->manual_download_open = $state;
            // Menutup download juga menutup ujian (tidak mungkin ujian terbuka tanpa download)
            if (! $state) {
                $exam->manual_exam_open = false;
            }
            $msg = $state ? 'Download soal dibuka. Siswa bisa mengunduh paket soal lebih dulu.' : 'Download soal ditutup.';
        }

        $exam->save();
        AuditLog::record('exam.manual_toggle', $exam, [
            'target' => $data['target'],
            'state' => $state,
            'download_open' => $exam->manual_download_open,
            'exam_open' => $exam->manual_exam_open,
        ]);

        return back()->with('success', $msg);
    }

    public function regeneratePackage(Exam $exam)
    {
        $this->ensureCanManage($exam);
        abort_if(in_array($exam->status, [Exam::STATUS_CLOSED, Exam::STATUS_ARCHIVED], true), 422, 'Soal ujian yang sudah ditutup/diarsipkan tidak bisa disiapkan ulang.');

        if ($exam->hasStartedWork() && $exam->hasGeneratedPackage()) {
            return back()->withErrors([
                'package' => 'Soal tidak disiapkan ulang karena sudah ada aktivitas siswa. Ini untuk menjaga soal tetap konsisten di semua perangkat.',
            ]);
        }

        if (! $exam->questions()->exists()) {
            return back()->withErrors(['package' => 'Belum ada soal. Buat soal dulu sebelum generate paket.']);
        }

        $package = $this->examPackageService->generate($exam->fresh(['questions.options', 'classrooms']));
        AuditLog::record('exam.package_regenerated', $exam, [
            'access_code' => $exam->access_code,
            'package_checksum' => $package['checksum'],
            'package_size_bytes' => $package['size_bytes'],
        ]);

        return back()->with('success', 'Soal untuk aplikasi siswa berhasil disiapkan ulang.');
    }

    public function unpublish(Exam $exam)
    {
        $this->ensureCanManage($exam);
        if ($exam->hasStartedWork()) {
            return back()->withErrors(['unpublish' => 'Ujian tidak bisa dikembalikan ke draft karena sudah ada siswa yang login/mulai mengerjakan.']);
        }

        $exam->forceFill([
            'status' => Exam::STATUS_DRAFT,
            'published_at' => null,
            'closed_at' => null,
        ])->save();

        AuditLog::record('exam.unpublished', $exam, ['access_code' => $exam->access_code]);

        return back()->with('success', 'Ujian dikembalikan ke draft. Soal dan konfigurasi bisa diedit kembali.');
    }

    public function close(Exam $exam)
    {
        $this->ensureCanManage($exam);
        $exam->forceFill([
            'status' => Exam::STATUS_CLOSED,
            'closed_at' => now(),
        ])->save();

        AuditLog::record('exam.closed', $exam, ['access_code' => $exam->access_code]);

        return back()->with('success', 'Ujian ditutup. Siswa tidak bisa download paket atau submit baru kecuali dibuka ulang oleh admin/guru terkait.');
    }

    public function archive(Exam $exam)
    {
        $this->ensureCanManage($exam);
        $exam->forceFill([
            'status' => Exam::STATUS_ARCHIVED,
            'closed_at' => $exam->closed_at ?: now(),
        ])->save();
        AuditLog::record('exam.archived', $exam, ['access_code' => $exam->access_code]);

        return back()->with('success', 'Ujian diarsipkan. Data hasil tetap bisa dibuka dari riwayat.');
    }

    private function validated(Request $request, ?Exam $exam = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'subject' => ['nullable', 'string', 'max:120'],
            'grade_level' => ['nullable', 'string', 'max:60'],
            'schedule_mode' => ['nullable', Rule::in([Exam::MODE_SCHEDULED, Exam::MODE_MANUAL])],
            'classroom_ids' => ['nullable', 'array'],
            'classroom_ids.*' => ['integer', 'exists:classrooms,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in([Exam::STATUS_DRAFT, Exam::STATUS_READY])],
        ]);

        $data['schedule_mode'] = $data['schedule_mode'] ?? ($exam?->schedule_mode ?: Exam::MODE_MANUAL);

        // Mode manual: abaikan jadwal jam, kosongkan starts_at/ends_at
        if ($data['schedule_mode'] === Exam::MODE_MANUAL) {
            $data['starts_at'] = null;
            $data['ends_at'] = null;
        }

        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_options'] = $request->boolean('shuffle_options');

        $data['lock_mode'] = $exam?->lock_mode ?: Exam::LOCK_STRICT_AIRPLANE;
        $data['exit_policy'] = Exam::normalizeExitPolicy($exam?->exit_policy ?? Exam::EXIT_AFTER_SUBMIT);

        $data['classroom_ids'] = collect($request->input('classroom_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        unset($data['status']);

        return $data;
    }

    private function availableClassrooms()
    {
        return Classroom::query()->orderBy('tingkat')->orderBy('nama_kelas')->get();
    }

    private function examQuery()
    {
        $query = Exam::query();
        if (auth()->user()->isTeacher()) {
            $query->where('teacher_id', auth()->id());
        }

        return $query;
    }

    private function ensureCanManage(Exam $exam): void
    {
        abort_unless(auth()->user()->canManageExam($exam), 403);
    }
}
