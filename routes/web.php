<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\QuestionBankController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\SchoolSettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\SilapSyncController;
use App\Http\Controllers\Admin\UserAccountController;
use App\Http\Controllers\Auth\SimpleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/setup', [SimpleAuthController::class, 'showSetup'])->name('setup');
    Route::post('/setup', [SimpleAuthController::class, 'storeSetup'])->middleware('throttle:web-login')->name('setup.store');
    Route::get('/login', [SimpleAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [SimpleAuthController::class, 'login'])->middleware('throttle:web-login')->name('login.store');
});

Route::post('/logout', [SimpleAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::middleware('password.changed')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/panduan', [DashboardController::class, 'guide'])->name('guide');

    Route::middleware('role:admin')->group(function () {
        Route::get('/classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
        Route::get('/classrooms/import', [ClassroomController::class, 'importPage'])->name('classrooms.import');
        Route::post('/classrooms/import-simple', [ClassroomController::class, 'importSimple'])->name('classrooms.importSimple');

        Route::get('/students/import', [StudentController::class, 'importPage'])->name('students.import');
        Route::resource('students', StudentController::class)->except(['show']);
        Route::post('/students/import-simple', [StudentController::class, 'importSimple'])->name('students.importSimple');
        Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::get('/silap-sync', [SilapSyncController::class, 'index'])->name('silap.index');

        Route::resource('accounts', UserAccountController::class)->parameters(['accounts' => 'account'])->except(['show']);
        Route::post('/accounts/generate-teacher-accounts', [UserAccountController::class, 'generateTeacherAccounts'])->name('accounts.generateTeacherAccounts');
        Route::get('/settings/school', [SchoolSettingController::class, 'edit'])->name('settings.school.edit');
        Route::post('/settings/school', [SchoolSettingController::class, 'update'])->name('settings.school.update');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });

    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/question-bank/import', [QuestionBankController::class, 'importPage'])->name('question-bank.import');
        Route::post('/question-bank/import-simple', [QuestionBankController::class, 'importSimple'])->name('question-bank.importSimple');
        Route::resource('question-bank', QuestionBankController::class)->parameters(['question-bank' => 'questionBank'])->except(['show']);

        Route::resource('exams', ExamController::class);
        Route::post('/exams/{exam}/regenerate-code', [ExamController::class, 'regenerateCode'])->name('exams.regenerateCode');
        Route::post('/exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');
        Route::post('/exams/{exam}/package/regenerate', [ExamController::class, 'regeneratePackage'])->name('exams.package.regenerate');
        Route::post('/exams/{exam}/unpublish', [ExamController::class, 'unpublish'])->name('exams.unpublish');
        Route::post('/exams/{exam}/close', [ExamController::class, 'close'])->name('exams.close');
        Route::post('/exams/{exam}/archive', [ExamController::class, 'archive'])->name('exams.archive');
        Route::get('/exams/{exam}/builder', [QuestionController::class, 'builder'])->name('exams.builder');
        Route::post('/exams/{exam}/questions/bulk-save', [QuestionController::class, 'bulkSave'])->name('exams.questions.bulkSave');
        Route::get('/exams/{exam}/question-bank/select', [QuestionBankController::class, 'selectForExam'])->name('exams.question-bank.select');
        Route::post('/exams/{exam}/question-bank/add', [QuestionBankController::class, 'addToExam'])->name('exams.question-bank.add');

        Route::get('/exams/{exam}/participants', [ParticipantController::class, 'index'])->name('exams.participants');
        Route::get('/exams/{exam}/participants/import', [ParticipantController::class, 'importPage'])->name('exams.participants.import');
        Route::post('/exams/{exam}/participants/import-simple', [ParticipantController::class, 'importSimple'])->name('exams.participants.importSimple');
        Route::post('/exams/{exam}/participants/assign-existing', [ParticipantController::class, 'assignExisting'])->name('exams.participants.assignExisting');
        Route::post('/exams/{exam}/participants/sync-classrooms', [ParticipantController::class, 'syncClassrooms'])->name('exams.participants.syncClassrooms');
        Route::post('/exams/{exam}/participants/{participant}/reset-device', [ParticipantController::class, 'resetDevice'])->name('exams.participants.resetDevice');
        Route::post('/exams/{exam}/participants/{participant}/reset-attempt', [ParticipantController::class, 'resetAttempt'])->name('exams.participants.resetAttempt');
        Route::delete('/exams/{exam}/participants/{participant}', [ParticipantController::class, 'removeParticipant'])->name('exams.participants.remove');
        Route::get('/exams/{exam}/results', [ParticipantController::class, 'results'])->name('exams.results');
        Route::get('/exams/{exam}/results/export', [ParticipantController::class, 'exportResults'])->name('exams.results.export');
    });

    Route::middleware('role:admin,teacher,proctor')->group(function () {
        Route::get('/exams/{exam}/monitor', [ParticipantController::class, 'monitor'])->name('exams.monitor');
    });
    });
});
