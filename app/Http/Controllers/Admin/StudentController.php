<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query()->with('classroom')->latest();

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('class_name', 'like', "%{$search}%")
                    ->orWhere('classroom_id', 'like', "%{$search}%")
                    ->orWhereHas('classroom', fn ($qq) => $qq->where('nama_kelas', 'like', "%{$search}%"));
            });
        }

        if ($classroomId = $request->get('classroom_id')) {
            $query->where('classroom_id', $classroomId);
        }

        $students = $query->paginate(20)->withQueryString();
        $classrooms = $this->availableClassrooms();

        return view('students.index', compact('students', 'classrooms'));
    }

    public function importPage()
    {
        return view('students.import', [
            'classrooms' => $this->availableClassrooms(),
        ]);
    }

    public function create()
    {
        return view('students.form', [
            'student' => new Student(),
            'classrooms' => $this->availableClassrooms(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data = $this->normalizeStudentPayload($request, $data);

        Student::create($data);

        return redirect()->route('students.index')->with('success', 'Akun siswa berhasil dibuat.');
    }

    public function edit(Student $student)
    {
        return view('students.form', [
            'student' => $student,
            'classrooms' => $this->availableClassrooms(),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $data = $this->validated($request, $student);
        $data = $this->normalizeStudentPayload($request, $data);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $student->tokens()->delete();
        }

        $student->update($data);

        return redirect()->route('students.index')->with('success', 'Akun siswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Akun siswa dihapus.');
    }

    public function importSimple(Request $request)
    {
        $data = $request->validate([
            'students_text' => ['required', 'string'],
        ]);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        foreach (preg_split('/\r\n|\r|\n/', $data['students_text']) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            [$nis, $name, $password, $className, $classroomId] = array_pad(array_map('trim', explode(';', $line, 5)), 5, null);
            if (!$classroomId && $className && is_numeric($className)) {
                $classroomId = $className;
                $className = null;
            }
            if (!$className && $classroomId) {
                $className = Classroom::find($classroomId)?->nama_kelas;
            }

            if (!$nis || !$name || !$password) {
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
                'password' => $password,
                'is_active' => true,
            ];

            if ($student) {
                $student->update($payload);
                $student->tokens()->delete();
                $updated++;
            } else {
                Student::create($payload);
                $created++;
            }
        }

        return back()->with('success', "Import selesai. Baru: {$created}, diperbarui: {$updated}, dilewati: {$skipped}.");
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        $passwordRule = $student ? ['nullable', 'confirmed', Password::min(6)] : ['required', 'confirmed', Password::min(6)];

        return $request->validate([
            'nis' => ['required', 'string', 'max:40', Rule::unique('students', 'nis')->ignore($student?->id)],
            'name' => ['required', 'string', 'max:160'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'password' => $passwordRule,
            'is_active' => ['nullable', 'boolean'],
            'jenis_kelamin' => ['nullable', 'string', 'max:10'],
            'kontak' => ['nullable', 'string', 'max:60'],
            'alamat' => ['nullable', 'string'],
        ]);
    }

    private function normalizeStudentPayload(Request $request, array $data): array
    {
        $data['nis'] = strtoupper(trim($data['nis']));
        $data['name'] = trim($data['name']);
        $data['nama_lengkap'] = $data['name'];
        $data['is_active'] = $request->boolean('is_active', true);

        $classroom = !empty($data['classroom_id']) ? Classroom::find($data['classroom_id']) : null;
        $data['class_name'] = $classroom?->nama_kelas;

        return $data;
    }

    private function availableClassrooms()
    {
        return Classroom::query()->orderBy('tingkat')->orderBy('nama_kelas')->get();
    }
}
