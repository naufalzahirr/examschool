<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\SchoolSetting;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParticipantController extends Controller
{
    public function index(Request $request, Exam $exam)
    {
        $this->ensureCanManage($exam);
        $exam->load('classrooms');

        $query = $exam->participants()
            ->with('student.classroom')
            ->latest();

        $this->applyParticipantFilters($query, $request);

        $participants = $query->paginate(30)->withQueryString();

        return view('exams.participants', compact('exam', 'participants'));
    }

    public function importPage(Exam $exam)
    {
        $this->ensureCanManage($exam);
        $exam->load('classrooms');

        return view('exams.participants_import', compact('exam'));
    }

    public function importSimple(Request $request, Exam $exam)
    {
        $this->ensureCanManage($exam);
        $data = $request->validate([
            'students_text' => ['required', 'string'],
        ]);

        $createdStudents = 0;
        $updatedStudents = 0;
        $assigned = 0;
        $alreadyAssigned = 0;
        $skipped = 0;

        DB::transaction(function () use ($data, $exam, &$createdStudents, &$updatedStudents, &$assigned, &$alreadyAssigned, &$skipped) {
            foreach (preg_split('/\r\n|\r|\n/', $data['students_text']) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                [$nis, $name, $password, $className, $classroomId] = array_pad(array_map('trim', explode(';', $line, 5)), 5, null);
                if (! $classroomId && $className && is_numeric($className)) {
                    $classroomId = $className;
                    $className = null;
                }
                if (! $className && $classroomId) {
                    $className = Classroom::find($classroomId)?->nama_kelas;
                }
                if (! $nis || ! $name || ! $password || strlen($password) < 8) {
                    $skipped++;

                    continue;
                }

                $student = Student::where('nis', strtoupper($nis))->first();
                $payload = [
                    'nis' => strtoupper($nis),
                    'name' => $name,
                    'nama_lengkap' => $name,
                    'class_name' => $className ?: null,
                    'classroom_id' => $classroomId ?: null,
                    'is_active' => true,
                ];

                if ($student) {
                    $payload['password'] = $password;
                    $student->update($payload);
                    $student->tokens()->delete();
                    $updatedStudents++;
                } else {
                    $payload['password'] = $password;
                    $student = Student::create($payload);
                    $createdStudents++;
                }

                $participant = $exam->participants()->firstOrCreate(
                    ['student_id' => $student->id],
                    ['status' => 'assigned']
                );

                if ($participant->wasRecentlyCreated) {
                    $assigned++;
                } else {
                    $alreadyAssigned++;
                }
            }
        });

        AuditLog::record('exam.participants_imported', $exam, compact('createdStudents', 'updatedStudents', 'assigned', 'alreadyAssigned', 'skipped'));

        return back()->with('success', "Import peserta selesai. Siswa baru: {$createdStudents}, siswa diperbarui: {$updatedStudents}, ditambahkan ke ujian: {$assigned}, sudah ada: {$alreadyAssigned}, dilewati: {$skipped}.");
    }

    public function assignExisting(Request $request, Exam $exam)
    {
        $this->ensureCanManage($exam);
        $data = $request->validate([
            'nis_text' => ['required', 'string'],
        ]);

        $assigned = 0;
        $notFound = [];
        foreach (preg_split('/\r\n|\r|\n|,/', $data['nis_text']) as $nis) {
            $nis = strtoupper(trim($nis));
            if ($nis === '') {
                continue;
            }

            $student = Student::where('nis', $nis)->first();
            if (! $student) {
                $notFound[] = $nis;

                continue;
            }

            $participant = $exam->participants()->firstOrCreate([
                'student_id' => $student->id,
            ], [
                'status' => 'assigned',
            ]);

            if ($participant->wasRecentlyCreated) {
                $assigned++;
            }
        }

        AuditLog::record('exam.participants_assigned', $exam, ['assigned' => $assigned, 'not_found' => $notFound]);
        $message = "{$assigned} siswa berhasil ditambahkan ke ujian.";
        if ($notFound) {
            $message .= ' NIS tidak ditemukan: '.implode(', ', array_slice($notFound, 0, 20));
        }

        return back()->with('success', $message);
    }

    public function syncClassrooms(Exam $exam)
    {
        $this->ensureCanManage($exam);
        $created = $exam->syncParticipantsFromClassrooms();
        $removed = $exam->status === 'draft' ? $exam->pruneParticipantsOutsideClassrooms() : 0;
        AuditLog::record('exam.participants_synced_from_classrooms', $exam, ['created' => $created, 'removed' => $removed]);

        $message = "Sinkron peserta dari kelas terpilih selesai. Peserta baru ditambahkan: {$created} siswa.";
        if ($removed > 0) {
            $message .= " Peserta draft di luar kelas terpilih dihapus: {$removed} siswa.";
        }

        return back()->with('success', $message);
    }

    public function removeParticipant(Exam $exam, ExamParticipant $participant)
    {
        $this->ensureCanManage($exam);
        abort_unless((int) $participant->exam_id === (int) $exam->id, 404);

        $safeStatuses = ['assigned', 'download_ready'];
        if (! in_array($participant->status, $safeStatuses, true)) {
            return back()->withErrors([
                'remove' => 'Peserta tidak bisa dihapus karena sudah memiliki aktivitas (status: '.$participant->status.'). Gunakan "Ulangi Ujian" untuk mereset, atau arsipkan ujian setelah selesai.',
            ]);
        }

        $studentName = $participant->student?->name ?? 'Siswa';
        $participant->delete();
        AuditLog::record('participant.removed', $participant, ['exam_id' => $exam->id, 'student_id' => $participant->student_id]);

        return back()->with('success', $studentName.' berhasil dihapus dari daftar peserta ujian ini.');
    }

    public function resetDevice(Exam $exam, ExamParticipant $participant)
    {
        $this->ensureCanManage($exam);
        abort_unless((int) $participant->exam_id === (int) $exam->id, 404);

        $participant->forceFill(['device_id' => null])->save();
        $participant->student?->tokens()->delete();
        AuditLog::record('participant.device_reset', $participant, ['exam_id' => $exam->id, 'student_id' => $participant->student_id]);

        return back()->with('success', 'Kunci perangkat siswa berhasil direset.');
    }

    public function resetAttempt(Exam $exam, ExamParticipant $participant)
    {
        $this->ensureCanManage($exam);
        abort_unless((int) $participant->exam_id === (int) $exam->id, 404);

        DB::transaction(function () use ($participant) {
            $participant->attempts()->delete();
            $participant->update([
                'device_id' => null,
                'status' => 'assigned',
                'locked_until' => null,
                'submitted_at' => null,
                'score' => null,
                'package_queue_joined_at' => null,
                'package_queue_started_at' => null,
                'package_queue_expires_at' => null,
                'package_queue_token' => null,
                'package_download_started_at' => null,
                'package_download_finished_at' => null,
                'package_download_attempts_count' => 0,
                'package_unlock_key_issued_at' => null,
            ]);
            $participant->student?->tokens()->delete();
        });

        AuditLog::record('participant.attempt_reset', $participant, ['exam_id' => $exam->id, 'student_id' => $participant->student_id]);

        return back()->with('success', 'Attempt, nilai, submit, dan kunci perangkat siswa berhasil direset.');
    }

    public function monitor(Request $request, Exam $exam)
    {
        abort_unless(auth()->user()->canMonitorExam($exam), 403);
        $exam->load(['classrooms'])->loadCount(['participants', 'questions']);

        $statusCounts = $exam->participants()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $query = $exam->participants()
            ->with(['student.classroom', 'attempts' => fn ($q) => $q->latest()])
            ->latest('updated_at');

        $this->applyParticipantFilters($query, $request);

        $participants = $query->paginate(40)->withQueryString();

        $queueStats = [
            'download_window_open' => $exam->isPackageDownloadWindowOpen(),
            'download_opens_at' => optional($exam->downloadOpensAt())->format('d M Y H:i'),
            'waiting' => $exam->participants()->whereNotNull('package_queue_joined_at')->whereNull('package_queue_token')->whereNull('package_download_finished_at')->count(),
            'active' => $exam->participants()->whereNotNull('package_queue_token')->whereNotNull('package_queue_expires_at')->where('package_queue_expires_at', '>', now())->whereNull('package_download_finished_at')->count(),
            'downloaded' => $exam->participants()->whereNotNull('package_download_finished_at')->count(),
            'unlocked' => $exam->participants()->whereNotNull('package_unlock_key_issued_at')->count(),
            'limit' => (int) SchoolSetting::getValue('package_download_concurrent_limit', 50),
        ];

        $integrityStats = [
            'locked' => (int) ($statusCounts['locked'] ?? 0),
            'events' => 0,
            'internet_active' => 0,
            'app_left' => 0,
        ];

        $exam->participants()->whereNotNull('meta')->get(['id', 'meta'])->each(function ($participant) use (&$integrityStats) {
            $summary = $participant->meta['integrity_summary'] ?? [];
            $integrityStats['events'] += (int) ($summary['total'] ?? 0);
            $integrityStats['internet_active'] += (int) ($summary['internet_active'] ?? 0);
            $integrityStats['app_left'] += (int) ($summary['app_left'] ?? 0);
        });

        return view('exams.monitor', compact('exam', 'statusCounts', 'participants', 'queueStats', 'integrityStats'));
    }

    public function results(Request $request, Exam $exam)
    {
        $this->ensureCanManage($exam);
        $exam->load('classrooms');

        $query = $exam->participants()
            ->with(['student.classroom', 'attempts' => fn ($q) => $q->latest()])
            ->orderBy('id');

        $this->applyParticipantFilters($query, $request);

        $participants = $query->paginate(50)->withQueryString();

        // Statistik dari seluruh peserta (bukan hanya halaman ini)
        $scores = $exam->participants()
            ->where('status', 'submitted')
            ->whereNotNull('score')
            ->pluck('score')
            ->map(fn ($s) => (float) $s);

        $stats = [
            'total' => $exam->participants()->count(),
            'submitted' => $scores->count(),
            'avg_score' => $scores->count() > 0 ? round($scores->avg(), 1) : null,
            'max_score' => $scores->count() > 0 ? round((float) $scores->max(), 1) : null,
            'min_score' => $scores->count() > 0 ? round((float) $scores->min(), 1) : null,
            'not_submitted' => $exam->participants()->whereNotIn('status', ['submitted'])->count(),
            'distribution' => [
                '85-100' => $scores->filter(fn ($s) => $s >= 85)->count(),
                '75-84' => $scores->filter(fn ($s) => $s >= 75 && $s < 85)->count(),
                '60-74' => $scores->filter(fn ($s) => $s >= 60 && $s < 75)->count(),
                '40-59' => $scores->filter(fn ($s) => $s >= 40 && $s < 60)->count(),
                '0-39' => $scores->filter(fn ($s) => $s < 40)->count(),
            ],
        ];

        // Soal paling banyak dijawab salah (dari attempt yang submitted)
        $hardQuestions = AttemptAnswer::query()
            ->whereHas('attempt', fn ($q) => $q->where('exam_id', $exam->id)->where('status', 'submitted'))
            ->select(
                'question_id',
                DB::raw('COUNT(*) as total_answers'),
                DB::raw('SUM(CASE WHEN is_correct = 0 THEN 1 ELSE 0 END) as wrong_count')
            )
            ->groupBy('question_id')
            ->orderByDesc('wrong_count')
            ->limit(8)
            ->with('question:id,title,order_no,type')
            ->get()
            ->filter(fn ($row) => $row->total_answers > 0 && $row->wrong_count > 0)
            ->map(fn ($row) => [
                'no' => $row->question?->order_no ?? '?',
                'title' => $row->question?->title ?? '(soal dihapus)',
                'type' => $row->question?->type ?? '-',
                'total' => (int) $row->total_answers,
                'wrong' => (int) $row->wrong_count,
                'wrong_pct' => $row->total_answers > 0
                    ? round($row->wrong_count / $row->total_answers * 100)
                    : 0,
            ])
            ->values();

        return view('exams.results', compact('exam', 'participants', 'stats', 'hardQuestions'));
    }

    private function applyParticipantFilters($query, Request $request): void
    {
        if ($search = trim((string) $request->get('q'))) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('class_name', 'like', "%{$search}%")
                    ->orWhereHas('classroom', fn ($qq) => $qq->where('nama_kelas', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($classroomId = $request->get('classroom_id')) {
            $query->whereHas('student', fn ($q) => $q->where('classroom_id', $classroomId));
        }
    }

    public function exportResults(Exam $exam)
    {
        $this->ensureCanManage($exam);
        $filename = 'hasil-'.preg_replace('/[^A-Za-z0-9\-]+/', '-', strtolower($exam->access_code.'-'.$exam->title)).'-'.now()->format('Ymd').'.csv';

        $statusLabels = [
            'assigned' => 'Belum login',
            'download_ready' => 'Siap download',
            'downloading' => 'Mengunduh',
            'downloaded' => 'Paket terunduh',
            'unlocked' => 'Soal terbuka',
            'in_progress' => 'Mengerjakan',
            'locked' => 'Terkunci',
            'synced' => 'Tersinkron',
            'submitted' => 'Sudah submit',
        ];

        return response()->streamDownload(function () use ($exam, $statusLabels) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM agar Excel tidak rusak
            fputcsv($handle, ['NIS', 'Nama', 'Kelas', 'Status', 'Nilai', 'Waktu Submit', 'Sinkron Terakhir']);

            $exam->participants()
                ->with(['student.classroom', 'attempts' => fn ($q) => $q->latest()])
                ->orderBy('id')
                ->chunk(200, function ($participants) use ($handle, $statusLabels) {
                    foreach ($participants as $participant) {
                        $lastAttempt = $participant->attempts->first();
                        fputcsv($handle, [
                            $participant->student?->nis ?: '-',
                            $participant->student?->name ?: 'Siswa dihapus',
                            $participant->student?->classroom?->nama_kelas ?: ($participant->student?->class_name ?: '-'),
                            $statusLabels[$participant->status] ?? $participant->status,
                            $participant->score !== null ? number_format((float) $participant->score, 1, '.', '') : '',
                            optional($participant->submitted_at)->format('d/m/Y H:i'),
                            optional($lastAttempt?->last_synced_at)->format('d/m/Y H:i'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function ensureCanManage(Exam $exam): void
    {
        abort_unless(auth()->user()->canManageExam($exam), 403);
    }
}
