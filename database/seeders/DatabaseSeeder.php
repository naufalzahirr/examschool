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

        // Demo data tidak boleh ikut otomatis di produksi karena berisi akun/kode contoh.
        if (! app()->environment('production') && (bool) env('SEED_DEMO_DATA', false)) {
            $this->call(ExamDemoSeeder::class);
        }
    }
}
