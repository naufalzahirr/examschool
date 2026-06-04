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
    public function __construct(private readonly ExamPackageService $examPackageService)
    {
    }

    public function index(Request $request)
    {
        $query = $this->examQuery()
            ->withCount(['questions', 'participants', 'classrooms'])
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
                'lock_mode' => SchoolSetting::getValue('default_exam_lock_mode', Exam::LOCK_STRICT_AIRPLANE),
                'exit_policy' => SchoolSetting::getValue('default_exam_exit_policy', Exam::EXIT_PROCTOR_CODE),
            ]),
            'classrooms' => $this->availableClassrooms(),
            'selectedClassroomIds' => [],
            'lockModes' => Exam::LOCK_MODES,
            'exitPolicies' => Exam::EXIT_POLICIES,
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

        $message = 'Ujian dibuat. Kode ujian otomatis: ' . $exam->access_code;
        if (($exam->created_participants_count ?? 0) > 0) {
            $message .= ' Peserta otomatis dari kelas terpilih: ' . $exam->created_participants_count . ' siswa.';
        }

        return redirect()->route('exams.builder', $exam)->with('success', $message);
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
            'limit' => (int) \App\Models\SchoolSetting::getValue('package_download_concurrent_limit', 50),
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
            'lockModes' => Exam::LOCK_MODES,
            'exitPolicies' => Exam::EXIT_POLICIES,
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $this->ensureCanManage($exam);
        $data = $this->validated($request);
        $classroomIds = $data['classroom_ids'] ?? [];
        unset($data['classroom_ids']);

        if (in_array($exam->status, [Exam::STATUS_CLOSED, Exam::STATUS_ARCHIVED], true)) {
            return back()->withErrors([
                'exam' => 'Ujian yang sudah ditutup/diarsipkan tidak boleh diedit. Buat ujian baru jika perlu perubahan.',
            ])->withInput();
        }

        if ($exam->hasStartedWork()) {
            return back()->withErrors([
                'exam' => 'Konfigurasi ujian sudah dikunci karena ada aktivitas siswa/download/unlock/attempt. Ini untuk menjaga paket soal, checksum, jadwal, dan aturan perangkat tetap sama di semua HP. Jika perlu perubahan besar, buat ujian baru atau hubungi admin untuk prosedur darurat.',
            ])->withInput();
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

        $message = 'Konfigurasi ujian diperbarui. Kode ujian tetap: ' . $exam->access_code;
        if (($createdParticipants['created'] ?? 0) > 0) {
            $message .= ' Peserta baru dari kelas terpilih: ' . $createdParticipants['created'] . ' siswa.';
        }
        if (($createdParticipants['removed'] ?? 0) > 0) {
            $message .= ' Peserta draft di luar kelas terpilih dihapus: ' . $createdParticipants['removed'] . ' siswa.';
        }

        $exam->refresh();
        if ($exam->status === Exam::STATUS_PUBLISHED && ! $exam->hasStartedWork()) {
            $package = $this->examPackageService->generate($exam->fresh(['questions.options', 'classrooms']));
            $message .= ' Paket soal published digenerate ulang. Checksum: ' . $package['checksum'];
        } elseif ($exam->status === Exam::STATUS_PUBLISHED && ! $exam->hasGeneratedPackage()) {
            $message .= ' Perhatian: paket soal perlu digenerate ulang dari halaman detail ujian sebelum siswa download.';
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

    public function regenerateExitCode(Exam $exam)
    {
        $this->ensureCanManage($exam);
        abort_if($exam->hasStartedWork(), 422, 'Kode keluar offline tidak bisa digenerate ulang setelah ada aktivitas siswa.');

        $code = $exam->ensureOfflineExitCode(force: true);
        $exam->bumpPackageVersion();
        AuditLog::record('exam.exit_code_regenerated', $exam, ['access_code' => $exam->access_code]);

        return back()->with('success', 'Kode keluar offline pengawas berhasil digenerate ulang: ' . $code . '. Simpan kode ini untuk pengawas. Paket soal perlu digenerate/publish ulang.');
    }

    public function regenerateCode(Exam $exam)
    {
        $this->ensureCanManage($exam);
        abort_if(in_array($exam->status, [Exam::STATUS_PUBLISHED, Exam::STATUS_CLOSED, Exam::STATUS_ARCHIVED], true), 422, 'Kode ujian tidak bisa diganti setelah ujian dipublish/ditutup/diarsipkan.');

        $exam->forceFill(['access_code' => Exam::generateAccessCode()])->save();
        if ($exam->exit_policy === Exam::EXIT_PROCTOR_CODE) {
            $exam->ensureOfflineExitCode(force: true);
        }
        AuditLog::record('exam.code_regenerated', $exam, ['access_code' => $exam->access_code]);

        return back()->with('success', 'Kode ujian berhasil digenerate ulang: ' . $exam->access_code);
    }

    public function publish(Exam $exam)
    {
        $this->ensureCanManage($exam);
        if ($exam->participants()->count() < 1) {
            $exam->syncParticipantsFromClassrooms();
        }

        if ($exam->exit_policy === Exam::EXIT_PROCTOR_CODE) {
            $exam->ensureOfflineExitCode();
            $exam->refresh();
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

        return back()->with('success', 'Ujian dipublish dan paket soal statis sudah dibuat. Siswa bisa download paket sebelum jam mulai. Checksum: ' . $package['checksum']);
    }

    public function regeneratePackage(Exam $exam)
    {
        $this->ensureCanManage($exam);
        abort_if(in_array($exam->status, [Exam::STATUS_CLOSED, Exam::STATUS_ARCHIVED], true), 422, 'Paket soal ujian yang sudah ditutup/diarsipkan tidak bisa digenerate ulang.');

        if ($exam->hasStartedWork() && $exam->hasGeneratedPackage()) {
            return back()->withErrors([
                'package' => 'Paket tidak digenerate ulang karena sudah ada aktivitas siswa. Ini untuk menjaga checksum paket soal tetap sama di semua perangkat.',
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

        return back()->with('success', 'Paket soal berhasil dibuat ulang. Checksum: ' . $package['checksum']);
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

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'subject' => ['nullable', 'string', 'max:120'],
            'grade_level' => ['nullable', 'string', 'max:60'],
            'classroom_ids' => ['nullable', 'array'],
            'classroom_ids.*' => ['integer', 'exists:classrooms,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
            'lock_mode' => ['required', Rule::in(array_keys(Exam::LOCK_MODES))],
            'exit_policy' => ['required', Rule::in(array_keys(Exam::EXIT_POLICIES))],
            'status' => ['nullable', Rule::in([Exam::STATUS_DRAFT, Exam::STATUS_READY])],
        ]);

        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_options'] = $request->boolean('shuffle_options');
        $data['lock_mode'] = $data['lock_mode'] ?? Exam::LOCK_STRICT_AIRPLANE;
        $data['exit_policy'] = $data['exit_policy'] ?? Exam::EXIT_PROCTOR_CODE;
        $data['classroom_ids'] = collect($request->input('classroom_ids', []))->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
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
