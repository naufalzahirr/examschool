<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Data master awal diambil penuh dari dump SILAP yang diberikan:
        // classrooms.sql, guru.sql, dan siswa.sql.
        $this->call(SilapInitialDataSeeder::class);

        // Seeder ini hanya membuat akun admin dan contoh ujian/soal.
        // Data siswa/guru/kelas tetap berasal dari SILAP.
        $this->call(ExamDemoSeeder::class);
    }
}
