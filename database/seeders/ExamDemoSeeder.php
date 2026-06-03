<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\SchoolSetting;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ExamPackageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ExamDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SchoolSetting::defaults() as $key => $value) {
            SchoolSetting::setValue($key, $value);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@sekolah.test'],
            ['name' => 'Admin Sekolah', 'username' => 'admin', 'password' => Hash::make('password123'), 'role' => User::ROLE_ADMIN, 'is_active' => true]
        );

        User::firstOrCreate(
            ['username' => 'pengawas'],
            ['name' => 'Pengawas Ujian', 'email' => 'pengawas@sekolah.test', 'password' => Hash::make('password123'), 'role' => User::ROLE_PROCTOR, 'is_active' => true]
        );

        // Akun guru awal untuk produksi: username memakai NIP, password awal Guru@123.
        Teacher::query()->where('is_active', true)->whereNull('user_id')->chunkById(100, function ($teachers) {
            foreach ($teachers as $teacher) {
                $username = strtolower(preg_replace('/[^a-z0-9_.-]+/', '', (string) ($teacher->nip ?: ('guru' . $teacher->id))));
                if ($username === '') {
                    continue;
                }

                $user = User::firstOrCreate(
                    ['username' => $username],
                    [
                        'name' => $teacher->name,
                        'email' => $username . '@guru.local',
                        'password' => Hash::make('Guru@123'),
                        'role' => User::ROLE_TEACHER,
                        'is_active' => true,
                        'must_change_password' => true,
                    ]
                );

                $teacher->forceFill(['user_id' => $user->id])->save();
            }
        });

        $exam = Exam::firstOrCreate(
            ['access_code' => 'DEMO01'],
            [
                'teacher_id' => $admin->id,
                'title' => 'Demo Ujian Sekolah',
                'subject' => 'Simulasi',
                'grade_level' => 'Ujian Sekolah',
                'description' => 'Ini ujian demo untuk mencoba akun siswa SILAP, download paket soal, offline, dan submit.',
                'status' => Exam::STATUS_PUBLISHED,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addWeek(),
                'duration_minutes' => 30,
                'shuffle_questions' => false,
                'shuffle_options' => false,
            ]
        );

        // Pilih X ANM dan X PSPT dari data SILAP agar akun demo NIS 4728 langsung bisa login.
        $classroomIds = Classroom::query()
            ->whereIn('id', [31, 30])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if (count($classroomIds) < 1) {
            $classroomIds = Classroom::query()
                ->orderByDesc('tingkat')
                ->orderBy('nama_kelas')
                ->limit(2)
                ->pluck('id')
                ->all();
        }

        if ($classroomIds) {
            $exam->classrooms()->sync($classroomIds);
            $exam->syncParticipantsFromClassrooms();
        }

        if ($exam->questions()->count() === 0) {
            $q1 = $exam->questions()->create([
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'title' => 'Laravel digunakan terutama untuk membuat aplikasi berbasis apa?',
                'required' => true,
                'points' => 10,
                'order_no' => 1,
            ]);
            $q1->options()->createMany([
                ['label' => 'Web', 'is_correct' => true, 'order_no' => 1],
                ['label' => 'Desain vektor', 'is_correct' => false, 'order_no' => 2],
                ['label' => 'Editing video', 'is_correct' => false, 'order_no' => 3],
            ]);

            $q2 = $exam->questions()->create([
                'type' => Question::TYPE_MULTIPLE_CHOICE_COMPLEX,
                'title' => 'Mana yang termasuk fitur aplikasi ujian semi-online?',
                'required' => true,
                'points' => 10,
                'order_no' => 2,
            ]);
            $q2->options()->createMany([
                ['label' => 'Download soal saat awal', 'is_correct' => true, 'order_no' => 1],
                ['label' => 'Progress tersimpan lokal di HP', 'is_correct' => true, 'order_no' => 2],
                ['label' => 'Harus online setiap detik', 'is_correct' => false, 'order_no' => 3],
            ]);

            $exam->questions()->create([
                'type' => Question::TYPE_TRUE_FALSE,
                'title' => 'Kunci jawaban ikut dikirim ke aplikasi siswa.',
                'required' => true,
                'points' => 10,
                'order_no' => 3,
                'answer_key' => ['answer' => false],
            ]);

            $q4 = $exam->questions()->create([
                'type' => Question::TYPE_MATCHING,
                'title' => 'Jodohkan istilah dengan pengertiannya.',
                'required' => true,
                'points' => 10,
                'order_no' => 4,
            ]);
            $q4->options()->createMany([
                ['label' => 'Frontend', 'is_correct' => true, 'order_no' => 1, 'meta' => ['match' => 'Tampilan aplikasi yang dilihat pengguna']],
                ['label' => 'Backend', 'is_correct' => true, 'order_no' => 2, 'meta' => ['match' => 'Logika server dan database']],
            ]);

            $exam->questions()->create([
                'type' => Question::TYPE_SHORT_ANSWER,
                'title' => 'Sebutkan singkatan dari Safe Exam Browser.',
                'required' => true,
                'points' => 10,
                'order_no' => 5,
                'correct_text' => 'SEB',
                'answer_key' => ['accepted' => ['SEB', 'Safe Exam Browser']],
            ]);
        }

        if (! $exam->hasGeneratedPackage()) {
            app(ExamPackageService::class)->generate($exam->fresh(['questions.options', 'classrooms']));
        }

        if (QuestionBankItem::query()->count() === 0) {
            QuestionBankItem::create([
                'teacher_id' => $admin->id,
                'subject' => 'Simulasi',
                'grade_level' => 'Ujian Sekolah',
                'topic' => 'Teknis Ujian',
                'difficulty' => 'mudah',
                'visibility' => QuestionBankItem::VISIBILITY_SCHOOL,
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'title' => 'Sistem semi-online berarti siswa dapat mengerjakan soal setelah paket soal berhasil diunduh.',
                'points' => 10,
                'options' => [
                    ['label' => 'Benar', 'is_correct' => true],
                    ['label' => 'Salah', 'is_correct' => false],
                ],
                'is_active' => true,
            ]);
        }
    }
}
