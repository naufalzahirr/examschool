<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SilapSyncController extends Controller
{
    public function sync(Request $request)
    {
        $this->authorizeToken($request);

        $data = $request->validate([
            'classrooms' => ['nullable', 'array'],
            'kelas' => ['nullable', 'array'],
            'students' => ['nullable', 'array'],
            'siswa' => ['nullable', 'array'],
            'teachers' => ['nullable', 'array'],
            'guru' => ['nullable', 'array'],
            'default_student_password' => ['nullable', 'string', 'max:80'],
        ]);

        $classroomRows = $data['classrooms'] ?? $data['kelas'] ?? [];
        $studentRows = $data['students'] ?? $data['siswa'] ?? [];
        $teacherRows = $data['teachers'] ?? $data['guru'] ?? [];
        $defaultPassword = $data['default_student_password'] ?? env('SILAP_DEFAULT_STUDENT_PASSWORD');
        if (app()->environment('production') && blank($defaultPassword)) {
            return response()->json([
                'message' => 'Password awal siswa wajib diisi melalui default_student_password atau SILAP_DEFAULT_STUDENT_PASSWORD di produksi.',
            ], 422);
        }

        $result = [
            'classrooms_created_or_updated' => 0,
            'classrooms_failed' => [],
            'students_created_or_updated' => 0,
            'students_failed' => [],
            'teachers_created_or_updated' => 0,
            'teachers_failed' => [],
        ];

        DB::transaction(function () use ($classroomRows, $studentRows, $teacherRows, $defaultPassword, &$result) {
            foreach ($classroomRows as $i => $row) {
                try {
                    Classroom::syncFromSilap((array) $row);
                    $result['classrooms_created_or_updated']++;
                } catch (Throwable $e) {
                    $result['classrooms_failed'][] = ['index' => $i, 'message' => $e->getMessage(), 'row' => $row];
                }
            }

            foreach ($studentRows as $i => $row) {
                try {
                    Student::syncFromSilap((array) $row, $defaultPassword);
                    $result['students_created_or_updated']++;
                } catch (Throwable $e) {
                    $result['students_failed'][] = ['index' => $i, 'message' => $e->getMessage(), 'row' => $row];
                }
            }

            foreach ($teacherRows as $i => $row) {
                try {
                    Teacher::syncFromSilap((array) $row);
                    $result['teachers_created_or_updated']++;
                } catch (Throwable $e) {
                    $result['teachers_failed'][] = ['index' => $i, 'message' => $e->getMessage(), 'row' => $row];
                }
            }
        });

        return response()->json([
            'message' => 'Sinkron SILAP selesai diproses.',
            'result' => $result,
        ]);
    }

    private function authorizeToken(Request $request): void
    {
        $configured = env('SILAP_SYNC_TOKEN');
        if (! filled($configured)) {
            abort_if(app()->environment('production'), 500, 'SILAP_SYNC_TOKEN belum diset. Endpoint sinkron tidak boleh terbuka di produksi.');

            return;
        }

        $bearer = $request->bearerToken();
        abort_unless(hash_equals((string) $configured, (string) $bearer), 401, 'Token sinkron SILAP tidak valid.');
    }
}
