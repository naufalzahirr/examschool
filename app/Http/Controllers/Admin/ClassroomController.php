<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $query = Classroom::query()->withCount('students')->orderBy('tingkat')->orderBy('nama_kelas');

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_kelas', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('term_id', 'like', "%{$search}%")
                    ->orWhere('tingkat', 'like', "%{$search}%");
            });
        }

        $classrooms = $query->paginate(30)->withQueryString();

        return view('classrooms.index', compact('classrooms'));
    }

    public function importPage()
    {
        return view('classrooms.import');
    }

    public function importSimple(Request $request)
    {
        $data = $request->validate([
            'classrooms_text' => ['required', 'string'],
        ]);

        $synced = 0;
        $skipped = 0;
        $errors = [];

        foreach (preg_split('/\r\n|\r|\n/', $data['classrooms_text']) as $lineNo => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            [$id, $termId, $namaKelas, $tingkat] = array_pad(array_map('trim', explode(';', $line, 4)), 4, null);
            if (!$id || !$namaKelas) {
                $skipped++;
                continue;
            }

            try {
                Classroom::syncFromSilap([
                    'id' => $id,
                    'term_id' => $termId,
                    'nama_kelas' => $namaKelas,
                    'tingkat' => $tingkat,
                ]);
                $synced++;
            } catch (\Throwable $e) {
                $errors[] = 'Baris ' . ($lineNo + 1) . ': ' . $e->getMessage();
            }
        }

        $message = "Import kelas selesai. Tersimpan: {$synced}, dilewati: {$skipped}.";
        if ($errors) {
            $message .= ' Error: ' . implode(' | ', array_slice($errors, 0, 5));
        }

        return back()->with('success', $message);
    }
}
