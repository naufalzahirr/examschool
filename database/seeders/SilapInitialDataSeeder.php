<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SilapInitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $classrooms = $this->loadJson('silap_classrooms.json');
        $teachers = $this->loadJson('silap_teachers.json');
        $students = $this->loadJson('silap_students.json');

        foreach ($classrooms as $row) {
            Classroom::syncFromSilap($row);
        }

        foreach ($teachers as $row) {
            Teacher::syncFromSilap($row);
        }

        $defaultPassword = trim((string) env('SILAP_DEFAULT_STUDENT_PASSWORD', ''));
        $defaultPassword = $defaultPassword !== '' ? $defaultPassword : null;

        foreach ($students as $row) {
            // Jika SILAP_DEFAULT_STUDENT_PASSWORD kosong, model Student otomatis memakai NIS sebagai password awal.
            Student::syncFromSilap($row, $defaultPassword);
        }
    }

    private function loadJson(string $filename): array
    {
        $path = database_path('seeders/data/' . $filename);

        if (! File::exists($path)) {
            throw new \RuntimeException("File seed data tidak ditemukan: {$path}");
        }

        $json = File::get($path);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new \RuntimeException("Format JSON seed data tidak valid: {$path}");
        }

        return $data;
    }
}
