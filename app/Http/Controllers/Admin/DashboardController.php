<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\QuestionBankItem;
use App\Models\Student;
use App\Models\Teacher;

class DashboardController extends Controller
{
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
            'recentAuditLogs' => $user->isAdmin() ? AuditLog::with('user')->latest()->take(8)->get() : collect(),
        ]);
    }
}
