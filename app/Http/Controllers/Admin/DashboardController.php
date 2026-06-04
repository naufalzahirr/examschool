<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\SchoolSetting;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\QuestionBankItem;
use App\Models\Student;
use App\Models\Teacher;

class DashboardController extends Controller
{
    public function guide()
    {
        $settings = [
            'download_open_hours' => (int) SchoolSetting::getValue('package_download_open_hours', 12),
            'concurrent_limit'    => (int) SchoolSetting::getValue('package_download_concurrent_limit', 50),
            'late_tolerance'      => (int) SchoolSetting::getValue('late_tolerance_minutes', 15),
            'upload_grace'        => (int) SchoolSetting::getValue('upload_grace_minutes', 30),
        ];

        return view('guide', compact('settings'));
    }

    public function index()
    {
        $user = auth()->user();
        $examQuery = Exam::query();
        if ($user->isTeacher()) {
            $examQuery->where('teacher_id', $user->id);
        }

        $today = now();
        $runningQuery = (clone $examQuery)
            ->where('status', 'published')
            ->where(function ($q) use ($today) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today);
            });

        $readyToPublishExams = (clone $examQuery)
            ->whereIn('status', [Exam::STATUS_DRAFT, Exam::STATUS_READY])
            ->withCount(['questions', 'participants', 'classrooms'])
            ->with('classrooms')
            ->latest()
            ->get()
            ->filter(fn ($e) => collect($e->readinessChecklist())->every(fn ($i) => $i['ok']))
            ->take(5);

        // Ujian yang akan mulai dalam 48 jam ke depan (published, belum dimulai)
        $upcomingExams = (clone $examQuery)
            ->where('status', Exam::STATUS_PUBLISHED)
            ->whereBetween('starts_at', [$today, $today->copy()->addHours(48)])
            ->with('classrooms')
            ->withCount('participants')
            ->orderBy('starts_at')
            ->take(5)
            ->get();

        return view('dashboard', [
            'examCount' => (clone $examQuery)->count(),
            'publishedCount' => (clone $examQuery)->where('status', 'published')->count(),
            'runningCount' => (clone $runningQuery)->count(),
            'draftCount' => (clone $examQuery)->where('status', 'draft')->count(),
            'bankCount' => QuestionBankItem::query()->visibleToUser($user)->count(),
            'studentCount' => $user->isAdmin() ? Student::count() : null,
            'classroomCount' => $user->isAdmin() ? Classroom::count() : null,
            'teacherCount' => $user->isAdmin() ? Teacher::count() : null,
            'participantCount' => $user->isProctor() ? ExamParticipant::count() : (clone $examQuery)->withCount('participants')->get()->sum('participants_count'),
            'submittedCount' => ExamAttempt::where('status', 'submitted')->count(),
            'recentExams' => (clone $examQuery)->with('classrooms')->latest()->take(8)->get(),
            'runningExams' => (clone $runningQuery)->with('classrooms')->latest()->take(8)->get(),
            'readyToPublishExams' => $readyToPublishExams,
            'upcomingExams' => $upcomingExams,
            'recentAuditLogs' => $user->isAdmin() ? AuditLog::with('user')->latest()->take(8)->get() : collect(),
        ]);
    }
}
